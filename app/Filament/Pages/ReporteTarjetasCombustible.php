<?php

namespace App\Filament\Pages;

use App\Exports\EstadoTarjetasExport;
use App\Models\TarjetaCombustible;
use App\Reports\EstadoTarjetasReport as Report;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ReporteTarjetasCombustible extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Reportes';

    protected static ?string $title = 'Reporte de tarjetas de combustible';

    protected static string $view = 'filament.pages.reporte-tarjetas-combustible';

    public static function canAccess(): bool
    {
        return (auth()->user()?->can('tarjeta-combustible.view') ?? false)
            && (auth()->user()?->can('vehiculo-tarjeta.view') ?? false);
    }

    public function table(Table $table): Table
    {
        $paths = ['vehiculo.numero_economico' => 'No. Económico', 'vehiculo.placas' => 'Placa',
            'vehiculo.marca' => 'Marca', 'vehiculo.modelo' => 'Modelo',
            'vehiculo.localidadActiva.localidad.nombre' => 'Localidad',
            'fecha_inicio' => 'Fecha inicio', 'activo' => 'Activo',
            'vehiculo.cuentaAnaliticaActiva.cuentaAnalitica.nombre' => 'Cuenta analítica'];
        $columns = [Tables\Columns\TextColumn::make('numero')->label('Número de tarjeta')->searchable()->sortable()];
        $filters = [Tables\Filters\SelectFilter::make('estado')->label('Mostrar tarjetas')->options([
            'asignadas' => 'Solo asignadas', 'sin' => 'Solo sin asignar', 'conflictos' => 'Con posibles duplicidades',
        ])->placeholder('Todas las tarjetas')->query(function (Builder $query, array $data) {
            return match ($data['value'] ?? null) {
                'asignadas' => $query->whereHas('vehiculoTarjetas', Report::vigentes(...)),
                'sin' => $query->whereDoesntHave('vehiculoTarjetas', Report::vigentes(...)),
                'conflictos' => Report::conflicto($query), default => $query,
            };
        })];
        foreach ($paths as $path => $label) {
            $name = str_replace('.', '_', $path);
            $column = Tables\Columns\TextColumn::make($name)->label($label)
                ->getStateUsing(fn (TarjetaCombustible $record) => Report::valores($record, $path))
                ->placeholder($label === 'Cuenta analítica' ? 'SIN INFORMACIÓN' : '—')->wrap();
            if (in_array($path, ['vehiculo.numero_economico', 'vehiculo.placas'])) {
                $column->searchable(query: fn (Builder $query, string $search) => $this->porAsignacion($query, $path, $search));
            }
            if ($path === 'fecha_inicio' || in_array($path, ['vehiculo.numero_economico', 'vehiculo.placas', 'vehiculo.marca', 'vehiculo.modelo'])) {
                $column->sortable(query: function (Builder $query, string $direction) use ($path) {
                    $sub = \App\Models\VehiculoTarjeta::query()->select(str_starts_with($path, 'vehiculo.') ? 'vehiculos.'.substr($path, 9) : 'vehiculo_tarjetas.fecha_inicio')
                        ->whereColumn('tarjeta_combustible_id', 'tarjeta_combustibles.id')->where('vehiculo_tarjetas.activo', true)
                        ->leftJoin('vehiculos', 'vehiculos.id', '=', 'vehiculo_tarjetas.vehiculo_id')->orderBy('vehiculo_tarjetas.id')->limit(1);

                    return $query->orderBy($sub, $direction)->orderBy('tarjeta_combustibles.id');
                });
            }
            $columns[] = $column;
            if ($path === 'activo') {
                continue;
            }
            if ($path === 'fecha_inicio') {
                $filters[] = Tables\Filters\Filter::make('fecha_inicio')->form([
                    DatePicker::make('desde'), DatePicker::make('hasta'),
                ])->query(fn (Builder $query, array $data) => $query->when(filled($data['desde'] ?? null) || filled($data['hasta'] ?? null), fn ($query) => $query->whereHas('vehiculoTarjetas', fn (Builder $q) => Report::vigentes($q)
                    ->when($data['desde'] ?? null, fn ($q, $v) => $q->whereDate('fecha_inicio', '>=', $v))
                    ->when($data['hasta'] ?? null, fn ($q, $v) => $q->whereDate('fecha_inicio', '<=', $v)))));
            } else {
                $filters[] = Tables\Filters\Filter::make($name)->label($label)->form([TextInput::make('valor')->label($label)])
                    ->query(fn (Builder $query, array $data) => filled($data['valor'] ?? null) ? $this->porAsignacion($query, $path, $data['valor']) : $query);
            }
        }
        $filters[] = Tables\Filters\Filter::make('tarjeta')->form([TextInput::make('numero')->label('Número de tarjeta')])
            ->query(fn (Builder $query, array $data) => $query->when($data['numero'] ?? null, fn ($q, $v) => $q->where('numero', 'like', "%{$v}%")));
        $columns[] = Tables\Columns\TextColumn::make('estado')->label('Estado de asignación')
            ->getStateUsing(fn (TarjetaCombustible $record) => Report::estado($record))->badge()
            ->color(fn (string $state) => match ($state) {
                'ASIGNADA' => 'success', 'SIN ASIGNAR' => 'gray', default => 'warning'
            });

        return $table->query(Report::query())->columns($columns)
            ->filters($filters, layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(['default' => 1, 'md' => 2, 'xl' => 3])
            ->defaultSort('numero')
            ->paginated([25, 50, 100])->actions([
                Tables\Actions\Action::make('historial')->label('Historial')->icon('heroicon-o-clock')
                    ->url(fn (TarjetaCombustible $record) => HistorialTarjetaCombustible::getUrl(['tarjeta' => $record->id])),
            ])->headerActions([Tables\Actions\Action::make('exportar')->label('Exportar Excel')->tooltip('Exporta todos los resultados con los filtros y la búsqueda actuales')->action('exportar')]);
    }

    private function porAsignacion(Builder $query, string $path, string $valor): Builder
    {
        $parts = explode('.', $path);
        $campo = array_pop($parts);

        return $query->whereHas('vehiculoTarjetas', fn (Builder $q) => Report::vigentes($q)
            ->whereHas(implode('.', $parts), fn (Builder $r) => $r->where($campo, 'like', "%{$valor}%")));
    }

    public function exportar()
    {
        abort_unless(static::canAccess(), 403);

        return Excel::download(new EstadoTarjetasExport($this->getFilteredSortedTableQuery()), 'reporte_tarjetas_combustible.xlsx');
    }
}
