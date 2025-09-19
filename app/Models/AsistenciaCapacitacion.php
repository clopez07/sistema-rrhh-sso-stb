<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AsistenciaCapacitacion extends Model
{
    use HasFactory;

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
