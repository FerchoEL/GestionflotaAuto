<?php

namespace App\Filament\Resources\TarjetaCombustibleResource\Pages;

use App\Filament\Resources\TarjetaCombustibleResource;
use App\Services\OneCardTarjetaImportService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListTarjetaCombustibles extends ListRecords
{
    protected static string $resource = TarjetaCombustibleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('importarOneCardBd')
                ->label('Importar BD One Card')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Importar hoja BD de One Card')
                ->modalSubmitActionLabel('Importar tarjetas')
                ->visible(fn (): bool => auth()->user()?->can('tarjeta-combustible.create') ?? false)
                ->authorize(fn () => auth()->user()?->can('tarjeta-combustible.create') ?? false)
                ->form([
                    FileUpload::make('archivo')
                        ->label('Archivo Excel')
                        ->disk('local')
                        ->directory('imports/one-card')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $relativePath = $data['archivo'] ?? null;

                    if (! $relativePath) {
                        Notification::make()
                            ->title('Error')
                            ->body('No se recibio ningun archivo para importar.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $fullPath = Storage::disk('local')->path($relativePath);

                    try {
                        $result = app(OneCardTarjetaImportService::class)->importarDesdeArchivo($fullPath);
                    } catch (\Throwable $exception) {
                        Notification::make()
                            ->title('Importacion fallida')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        Storage::disk('local')->delete($relativePath);
                        return;
                    }

                    Storage::disk('local')->delete($relativePath);

                    $body = collect([
                        'Leidas: ' . $result['leidas'],
                        'Validas candidatas: ' . $result['candidatas'],
                        'Nuevas insertadas: ' . $result['insertadas'],
                        'Ya existentes: ' . count($result['existentes']),
                        'Duplicadas en archivo: ' . count($result['duplicados_archivo']),
                        'Invalidas: ' . count($result['invalidos']),
                    ])->implode(' | ');

                    $notification = Notification::make()
                        ->title('Importacion de tarjetas completada')
                        ->body($body);

                    if ($result['insertadas'] > 0) {
                        $notification->success();
                    } else {
                        $notification->warning();
                    }

                    $notification->send();
                }),
        ];
    }
}
