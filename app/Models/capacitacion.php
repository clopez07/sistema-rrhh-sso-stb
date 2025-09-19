<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class capacitacion extends Model
{
    protected $table = 'capacitacion';
    protected $primaryKey = 'id_capacitacion';
    public $timestamps = false; // si tu tabla no tiene created_at y updated_at

    protected $fillable = [
        'id_capacitacion',
        'capacitacion'
    ];
}
