<?php

namespace App\Models;

use App\Services\TarjetaMovimientoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CargaCombustible extends Model
{
    protected $table = 'carga_combustibles';

    protected $fillable = [
        'vehiculo_id',
        'tarjeta_combustible_id',
        'user_id',
        'fecha_carga',
        'km_odometro',
        'litros',
        'importe',
        'foto_odometro_path',
        'foto_ticket_path',
        'foto_bomba_path',
        'precio_litro',
        'cuenta_analitica_id',
        'es_extemporanea',
        'motivo_correccion',
        'registrada_por_user_id',
        'fecha_registro_real',
    ];

    protected $casts = [
        'fecha_carga' => 'datetime',
        'litros' => 'decimal:2',
        'importe' => 'decimal:4',
        'precio_litro' => 'decimal:2',
        'es_extemporanea' => 'boolean',
        'fecha_registro_real' => 'datetime',
    ];

    public function scopeOrderedChronologically(Builder $query): Builder
    {
        return $query
            ->orderBy('fecha_carga')
            ->orderBy('id');
    }

    public function scopeOrderedChronologicallyDesc(Builder $query): Builder
    {
        return $query
            ->orderByDesc('fecha_carga')
            ->orderByDesc('id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function tarjeta()
    {
        return $this->belongsTo(TarjetaCombustible::class, 'tarjeta_combustible_id', 'id');
    }

    public function registradaPor()
    {
        return $this->belongsTo(User::class, 'registrada_por_user_id', 'id');
    }

    public function rendimiento()
    {
        return $this->hasOne(Rendimiento::class, 'carga_id', 'id');
    }
    

    public function cuentaAnalitica()
    {
        return $this->belongsTo(CuentaAnalitica::class, 'cuenta_analitica_id', 'id');
    }

    
    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->tarjeta_combustible_id = app(TarjetaMovimientoService::class)
                ->resolverTarjetaIdVehiculoEnFecha($model->vehiculo_id, $model->fecha_carga);

            if ($model->litros > 0 && $model->precio_litro > 0) {
                $model->importe = round(
                    $model->litros * $model->precio_litro,
                    2
                );
            }
        });

        static::saved(function (self $model): void {
            app(TarjetaMovimientoService::class)->sincronizarCarga($model);
        });

        static::deleted(function (self $model): void {
            app(TarjetaMovimientoService::class)->eliminarMovimientoDeOrigen($model);
        });
    }
}
