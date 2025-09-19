<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quimico extends Model
{
    protected $table = 'quimico';
    protected $primaryKey = 'id_quimico';
    public $timestamps = false;

    protected $fillable = [
        'nombre_comercial',
        'uso',
        'proveedor',
        'concentracion',
        'composicion_quimica',
        'estado_fisico',
        'msds',
        'salud',
        'inflamabilidad',
        'reactividad',
        'ninguno',
        'particulas_polvo',
        'sustancias_corrosivas',
        'sustancias_toxicas',
        'sustancias_irritantes',
        'nocivo',
        'corrosivo',
        'inflamable',
        'peligro_salud',
        'oxidante',
        'peligro_medio_ambiente',
        'toxico',
        'gas_presion',
        'explosivo',
    ];
}