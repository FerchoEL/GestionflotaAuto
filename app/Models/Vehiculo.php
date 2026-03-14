<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{

    protected $fillable = [
        'tipo_vehiculo_id',
        'departamento_id',
        'localidad_id',
        'estatus_id',
        'tipo_combustible',
        'transmision',
        'numero_economico',
        'placas',
        'vin',
        'marca',
        'modelo',
        'anio',
        'color',
        'capacidad_tanque_litros',
        'rendimiento_optimo_km_l',
        'tolerancia_pct',
        'activo',
    ];
    
    public function tipoVehiculo()
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipo_vehiculo_id', 'id');
    }

    /* public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id', 'id');
    }

    public function localidad()
    {
        return $this->belongsTo(Localidad::class, 'localidad_id', 'id');
    } */

    public function estatus()
    {
        return $this->belongsTo(VehiculoEstatus::class, 'estatus_id', 'id');
    }

    public function choferes()
    {
        return $this->hasMany(VehiculoChofer::class, 'vehiculo_id', 'id');
    }

    public function responsables()
    {
        return $this->hasMany(VehiculoResponsable::class, 'vehiculo_id', 'id');
    }
    public function responsableActivo()
    {
        return $this->hasOne(VehiculoResponsable::class)
            ->where('activo', true)
            ->orderByDesc('fecha_inicio');
    }

    public function cargas()
    {
        return $this->hasMany(CargaCombustible::class, 'vehiculo_id', 'id');
    }

    public function rendimientos()
    {
        return $this->hasMany(Rendimiento::class, 'vehiculo_id', 'id');
    }

    public function fondeos()
    {
        return $this->hasMany(Fondeo::class, 'vehiculo_id', 'id');
    }

    public function fondeoConfigActual()
    {
        return $this->hasOne(VehiculoFondeoConfig::class, 'vehiculo_id', 'id')
            ->where('activo', true);
    }

    public function fondeoConfigs()
    {
        return $this->hasMany(VehiculoFondeoConfig::class, 'vehiculo_id', 'id');
    }

    
    public function departamentos()
    {
        return $this->hasMany(VehiculoDepartamento::class);
    }

    public function departamentoActivo()
    {
        return $this->hasOne(VehiculoDepartamento::class)
            ->where('activo', true)
            ->orderByDesc('fecha_inicio');
    }

    public function localidades()
    {
        return $this->hasMany(VehiculoLocalidad::class);
    }

    public function localidadActiva()
    {
        return $this->hasOne(VehiculoLocalidad::class)
            ->where('activo', true)
            ->orderByDesc('fecha_inicio');
    }
    public function tarjetas()
    {
        return $this->hasMany(VehiculoTarjeta::class);
    }

    public function tarjetaActiva()
    {
        return $this->hasOne(VehiculoTarjeta::class)
            ->where('activo', true)
            ->orderByDesc('fecha_inicio')   // ✅ importante
            ->orderByDesc('id');
    }
    public function choferActivo()
    {
        return $this->hasOne(\App\Models\VehiculoChofer::class, 'vehiculo_id', 'id')
            ->where('activo', true)
            ->orderByDesc('fecha_inicio');
    }
    public function cuentasAnaliticas()
    {
        return $this->hasMany(\App\Models\VehiculoCuentaAnalitica::class, 'vehiculo_id', 'id');
    }

    public function cuentaAnaliticaActiva()
    {
        return $this->hasOne(\App\Models\VehiculoCuentaAnalitica::class, 'vehiculo_id', 'id')
            ->where('activo', true)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id');
    }

}

