<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    protected $table = 'prestamo';
    protected $primaryKey = 'id_prestamo';
    public $timestamps = false;

    protected $fillable = [
        'num_prestamo',
        'id_empleado',
        'monto',
        'cuota_capital',
        'porcentaje_interes',
        'total_intereses',
        'cobro_extraordinario',
        'causa',
        'plazo_meses',
        'fecha_deposito_prestamo',
        'fecha_primera_cuota',
        'id_planilla',
        'estado_prestamo',
        'observaciones',
    ];
}