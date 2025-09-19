<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class instructor extends Model
{
    protected $table = 'instructor';
    protected $primaryKey = 'id_instructor';
    public $timestamps = false; // si tu tabla no tiene created_at y updated_at

    protected $fillable = [
        'id_instructor',
        'instructor'
    ];
}
