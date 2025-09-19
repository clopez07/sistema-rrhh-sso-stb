<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuestoCapacitacion extends Model
{
    protected $table = 'puestos_capacitacion';
    protected $primaryKey = 'id_puestos_capacitacion';
    public $timestamps = false;
    protected $fillable = ['id_puesto_trabajo', 'id_capacitacion'];
}
