<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class capacitacionesInstructor extends Model
{
    protected $table = 'capacitacion_instructor';
    protected $primaryKey = 'id_capacitacion_instructor';
    public $timestamps = false; // si tu tabla no tiene created_at y updated_at

    protected $fillable = [
        'id_capacitacion',
        'id_instructor',
        'duracion',
        'depto',
    ];
}
