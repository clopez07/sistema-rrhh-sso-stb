<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleado';
    protected $primaryKey = 'id_empleado';
    public $timestamps = false;

    protected $fillable = [
        'nombre_completo',
        'identidad',
        'codigo_empleado',
        'id_puesto_trabajo',
        'estado'
    ];
}
