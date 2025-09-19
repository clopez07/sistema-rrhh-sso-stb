<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EPP extends Model
{
    protected $table = 'epp';
    protected $primaryKey = 'id_epp';
    public $timestamps = false;

    protected $fillable = [
        'equipo',
        'codigo',
        'marca',
        'id_tipo_proteccion',
    ];
}