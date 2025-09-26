<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $casts = [
        'fecha_deposito_prestamo' => 'date',
        'fecha_primera_cuota' => 'date',
        'monto' => 'decimal:2',
        'cuota_capital' => 'decimal:2',
        'porcentaje_interes' => 'decimal:2',
        'total_intereses' => 'decimal:2',
        'cobro_extraordinario' => 'decimal:2',
    ];

    /**
     * Relación con el empleado
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }

    /**
     * Relación con el historial de cuotas
     */
    public function historialCuotas(): HasMany
    {
        return $this->hasMany(HistorialCuota::class, 'id_prestamo', 'id_prestamo');
    }

    /**
     * Obtener cuotas pagadas
     */
    public function cuotasPagadas(): HasMany
    {
        return $this->historialCuotas()->where('pagado', 1);
    }

    /**
     * Obtener cuotas pendientes
     */
    public function cuotasPendientes(): HasMany
    {
        return $this->historialCuotas()->where('pagado', 0);
    }

    /**
     * Calcular el total pagado
     */
    public function getTotalPagadoAttribute(): float
    {
        return $this->cuotasPagadas()->sum('saldo_pagado') ?? 0;
    }

    /**
     * Calcular el saldo restante
     */
    public function getSaldoRestanteAttribute(): float
    {
        $ultimaCuota = $this->historialCuotas()->orderBy('num_cuota', 'desc')->first();
        return $ultimaCuota ? $ultimaCuota->saldo_restante : $this->monto;
    }

    /**
     * Calcular progreso del préstamo en porcentaje
     */
    public function getProgresoAttribute(): float
    {
        if ($this->monto == 0) return 0;
        return round(($this->total_pagado / $this->monto) * 100, 2);
    }
}