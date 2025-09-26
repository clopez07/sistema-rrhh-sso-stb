<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialCuota extends Model
{
    protected $table = 'historial_cuotas';
    protected $primaryKey = 'id_historial_cuotas';
    public $timestamps = false;

    protected $fillable = [
        'id_prestamo',
        'num_cuota',
        'fecha_programada',
        'fecha_pago_real',
        'abono_capital',
        'abono_intereses',
        'cuota_mensual',
        'cuota_quincenal',
        'saldo_pagado',
        'saldo_restante',
        'interes_pagado',
        'interes_restante',
        'pagado',
        'ajuste',
        'motivo',
        'observaciones',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_pago_real' => 'date',
        'abono_capital' => 'decimal:2',
        'abono_intereses' => 'decimal:2',
        'cuota_mensual' => 'decimal:2',
        'cuota_quincenal' => 'decimal:2',
        'saldo_pagado' => 'decimal:2',
        'saldo_restante' => 'decimal:2',
        'interes_pagado' => 'decimal:2',
        'interes_restante' => 'decimal:2',
        'pagado' => 'boolean',
        'ajuste' => 'boolean',
    ];

    /**
     * Relación con el préstamo
     */
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'id_prestamo', 'id_prestamo');
    }

    /**
     * Scope para cuotas pagadas
     */
    public function scopePagadas($query)
    {
        return $query->where('pagado', 1);
    }

    /**
     * Scope para cuotas pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('pagado', 0);
    }

    /**
     * Scope para cuotas normales (no ajustes)
     */
    public function scopeNormales($query)
    {
        return $query->where('ajuste', 0);
    }

    /**
     * Scope para ajustes
     */
    public function scopeAjustes($query)
    {
        return $query->where('ajuste', 1);
    }
}
