<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AsignacionEPP extends Model
{
    use HasFactory;

    protected $table = 'asignacion_epp';
    protected $primaryKey = 'id_asignacion_epp';
    public $timestamps = false;

    protected $fillable = [
        'id_empleado',
        'id_epp',
        'fecha_entrega_epp',
    ];
}
