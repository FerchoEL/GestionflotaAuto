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
        'centro_costo',
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

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id', 'id');
    }

    public function localidad()
    {
        return $this->belongsTo(Localidad::class, 'localidad_id', 'id');
    }

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

    public function centroCosto()
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }


}

