<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuestoTrabajo extends Model
{
    protected $table = 'puesto_trabajo_matriz';
    protected $primaryKey = 'id_puesto_trabajo_matriz';
    public $timestamps = false; // si tu tabla no tiene created_at y updated_at

    protected $fillable = [
        'puesto_trabajo_matriz',
        'id_departamento',
        'id_localizacion',
        'id_area',
        'num_empleados',
        'descripcion_general',
        'actividades_diarias',
        'objetivo_puesto',
        'estado'
    ];
}
