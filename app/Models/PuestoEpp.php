<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuestoEpp extends Model
{
protected $table = 'puestos_epp';
protected $primaryKey = 'id_puestos_epp';
public $timestamps = false;
protected $fillable = ['id_puesto_trabajo', 'id_epp'];
}