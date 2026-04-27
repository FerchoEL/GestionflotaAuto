<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AseguradoraResource\Pages;
use App\Models\Aseguradora;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class AseguradoraResource extends Resource
{
    protected static ?string $model = Aseguradora::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationLabel = 'Aseguradoras';
    protected static ?string $modelLabel = 'Aseguradora';
    protected static ?string $pluralModelLabel = 'Aseguradoras';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nombre')
                ->required()
                ->maxLength(255)
                ->label('Nombre de la aseguradora'),

            TextInput::make('numero_telefonico')
                ->tel()
                ->maxLength(255)
                ->label('Número telefónico'),

            TextInput::make('email')
                ->email()
                ->maxLength(255)
                ->label('Correo electrónico'),

            Textarea::make('descripcion')
                ->maxLength(500)
                ->rows(3)
                ->label('Descripción'),

            Toggle::make('activo')
                ->default(true)
                ->label('Activo'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Nombre'),

                TextColumn::make('numero_telefonico')
                    ->searchable()
                    ->label('Teléfono'),

                TextColumn::make('email')
                    ->searchable()
                    ->label('Email'),

                IconColumn::make('activo')
                    ->boolean()
                    ->label('Activo'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activo')
                    ->options([
                        true => 'Activos',
                        false => 'Inactivos',
                    ])
                    ->label('Estado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAseguradoras::route('/'),
            'create' => Pages\CreateAseguradora::route('/create'),
            'edit' => Pages\EditAseguradora::route('/{record}/edit'),
        ];
    }
}
