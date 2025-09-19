<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuestosSistema extends Model
{
    protected $table = 'puesto_trabajo';
    protected $primaryKey = 'id_puesto_trabajo';
    public $timestamps = false; // si tu tabla no tiene created_at y updated_at

    protected $fillable = [
        'id_puesto_trabajo',
        'puesto_trabajo',
        'departamento', 
        'num_empleados',
        'estado'
    ];
}
