<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapacitacionInstructor extends Model
{
    // Nombre de la tabla
    protected $table = 'capacitacion_instructor';

    // Clave primaria
    protected $primaryKey = 'id_capacitacion_instructor';

    // Si no tienes columnas created_at / updated_at
    public $timestamps = false;

    // Columnas que se pueden asignar masivamente
    protected $fillable = [
        'id_capacitacion',
        'id_instructor',
        'duracion',
        'depto',
    ];

    /**
     * Relación con la tabla capacitacion
     * Un registro de capacitacion_instructor pertenece a una capacitación.
     */
    public function capacitacion()
    {
        return $this->belongsTo(capacitacion::class, 'id_capacitacion');
    }

    /**
     * Relación con la tabla instructor
     * Un registro de capacitacion_instructor pertenece a un instructor.
     */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'id_instructor');
    }
}
