<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargaCombustible extends Model
{
    protected $table = 'carga_combustibles';

    protected $fillable = [
        'vehiculo_id',
        'user_id',
        'fecha_carga',
        'km_odometro',
        'litros',
        'importe',
        'foto_odometro_path',
        'foto_ticket_path',
        'precio_litro',
        'cuenta_analitica_id',
    ];

    protected $casts = [
        'fecha_carga' => 'datetime',
        'litros' => 'decimal:2',
        'importe' => 'decimal:4',
        'precio_litro' => 'decimal:2'
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function rendimiento()
    {
        return $this->hasOne(Rendimiento::class, 'carga_id', 'id');
    }
    

    public function cuentaAnalitica()
    {
        return $this->belongsTo(CuentaAnalitica::class, 'cuenta_analitica_id', 'id');
    }

    
    protected static function booted()
    {
        static::saving(function ($model) {

            if ($model->litros > 0 && $model->precio_litro > 0) {
                $model->importe = round(
                    $model->litros * $model->precio_litro,
                    2
                );
            }
        });
    }
}

