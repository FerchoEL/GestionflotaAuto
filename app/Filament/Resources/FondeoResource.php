<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FondeoResource\Pages;
use App\Filament\Resources\FondeoResource\RelationManagers;
use App\Models\Fondeo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FondeoResource extends Resource
{
    protected static ?string $model = Fondeo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehiculo_id')
            ->relationship('vehiculo', 'placas')
            ->disabled()
            ->required()
            ->searchable(),

        Forms\Components\DatePicker::make('semana_inicio')
            ->disabled()
            ->required(),

        Forms\Components\DatePicker::make('semana_fin')
            ->disabled()
            ->required(),

        Forms\Components\TextInput::make('litros_consumidos')
            ->numeric()
            ->disabled(),

        Forms\Components\TextInput::make('litros_a_fondear')
            ->numeric()
            ->disabled(),

        Forms\Components\Select::make('estatus')
            ->options([
                'Pendiente' => 'Pendiente',
                'Fondeado' => 'Fondeado',
            ])
            ->required()
            ->reactive(),

        Forms\Components\Textarea::make('comentario')
            ->maxLength(255),

        Forms\Components\DateTimePicker::make('fecha_fondeado')
            ->visible(fn ($get) => $get('estatus') === 'Fondeado')
            ->disabled()
            ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehiculo.placas')
                ->label('Vehículo')
                ->searchable(),

                    Tables\Columns\TextColumn::make('semana_inicio')
                        ->date(),

                    Tables\Columns\TextColumn::make('litros_consumidos'),

                    Tables\Columns\TextColumn::make('litros_a_fondear'),

                    Tables\Columns\BadgeColumn::make('estatus')
                        ->colors([
                            'warning' => 'Pendiente',
                            'success' => 'Fondeado',
                        ]),
            ])
            
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('fondear')
                ->visible(fn ($record) => $record->estatus === 'Pendiente')
                ->action(function ($record) {
                    $record->update([
                        'estatus' => 'Fondeado',
                        'fondeado_por_user_id' => auth()->id(),
                        'fecha_fondeado' => now(),
                    ]);
                })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFondeos::route('/'),
            'create' => Pages\CreateFondeo::route('/create'),
            'edit' => Pages\EditFondeo::route('/{record}/edit'),
        ];
    }
}
