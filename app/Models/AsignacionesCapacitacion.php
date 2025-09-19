<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionesCapacitacion extends Model
{
    protected $table = 'asistencia_capacitacion';
    protected $primaryKey = 'id_asistencia_capacitacion';
    public $timestamps = false;

    protected $fillable = [
        'id_empleado',
        'id_capacitacion_instructor',
        'instructor_temporal',
        'fecha_recibida',
    ];
}