<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentificacionRiesgos extends Model
{
    protected $table = 'identificacion_riesgos';
    protected $primaryKey = 'id_identificacion_riesgos';
    public $timestamps = false;
    protected $guarded = []; // simple para este form grande
}