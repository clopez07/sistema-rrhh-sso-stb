<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Localizacion extends Model
{
    protected $table = 'localizacion';
    protected $primaryKey = 'id_localizacion';
    public $timestamps = false;

    protected $fillable = [
        'localizacion'
    ];
}
