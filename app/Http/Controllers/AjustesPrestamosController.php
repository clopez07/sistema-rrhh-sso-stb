<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AjustesPrestamosController extends Controller
{
    private const SESSION_PREFIX = 'ajustes_prestamos.preview.';
    private const MAX_EMPTY_ROWS = 30;

    public function form()
    {
        return redirect()->route('cuotas');
    }

    public function previewExcel(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        try {
            $plan = $this->buildPlan($request->file('archivo')->getRealPath());
        } catch (\Throwable $th) {
            \Log::error('[AJUSTES PRESTAMOS][preview] ' . $th->getMessage(), ['exception' => $th]);
            return back()->with('error', $th->getMessage())->withInput();
        }

        $token = (string) Str::uuid();
        session([self::SESSION_PREFIX . $token => $plan]);

        return redirect()->route('cuotas.rango', [
            'fecha_inicio' => $plan['inicio'],
            'fecha_fin' => $plan['fin'],
            'estado' => 'todas',
            'preview_token' => $token,
        ])->with('ajustes_preview_token', $token);
    }

    public function commitExcel(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'redirect_route' => ['nullable', 'string'],
            'redirect_fecha_inicio' => ['nullable', 'string'],
            'redirect_fecha_fin' => ['nullable', 'string'],
            'redirect_estado' => ['nullable', 'string'],
            'redirect_search' => ['nullable', 'string'],
        ]);

        $token = $data['token'];
        $plan = session(self::SESSION_PREFIX . $token);

        if (!$plan) {
            return redirect()->route('cuotas')->with('error', 'No se encontró la previsualización del ajuste. Vuelve a cargar el archivo.');
        }

        DB::transaction(fn () => $this->applyPlan($plan));

        session()->forget(self::SESSION_PREFIX . $token);

        $movimientos = $plan['counts']['acciones_aplicables'] ?? 0;
        $rango = "{$plan['inicio']} al {$plan['fin']}";

        $redirectRoute = $data['redirect_route'] ?? null;

        if ($redirectRoute === 'cuotas.rango') {
            $params = [
                'fecha_inicio' => $data['redirect_fecha_inicio'] ?? $plan['inicio'],
                'fecha_fin' => $data['redirect_fecha_fin'] ?? $plan['fin'],
                'estado' => $data['redirect_estado'] ?? 'todas',
            ];

            if (!empty($data['redirect_search'])) {
                $params['search'] = $data['redirect_search'];
            }

            return redirect()->route('cuotas.rango', $params)
                ->with('success', "Ajustes aplicados para el rango {$rango}. Movimientos ejecutados: {$movimientos}.");
        }

        return redirect()->route('cuotas')
            ->with('success', "Ajustes aplicados para el rango {$rango}. Movimientos ejecutados: {$movimientos}.");
    }

    private function applyPlan(array $plan): void
    {
        foreach ($plan['actions'] as $action) {
            if (empty($action['updates'])) {
                continue;
            }

            foreach ($action['updates'] as $update) {
                if ($update['action'] === 'update') {
                    $table = $update['table'];
                    $primary = $update['primary'] ?? 'id';
                    $id = $update['id'];
                    $data = $update['data'];

                    if (array_key_exists('append_observaciones', $update)) {
                        $current = DB::table($table)->where($primary, $id)->value('observaciones');
                        $append = $update['append_observaciones'] ?? '';
                        if ($append !== '') {
                            $separator = $current ? ' | ' : '';
                            $data['observaciones'] = trim(($current ?? '') . $separator . $append);
                        } else {
                            $data['observaciones'] = $current;
                        }
                    }

                    DB::table($table)->where($primary, $id)->update($data);
                } elseif ($update['action'] === 'insert') {
                    DB::table($update['table'])->insert($update['data']);
                }
            }
        }
    }

    private function buildPlan(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        [$sheet, $hdrRow, $colCodigo, $colNombre, $colDeduccion] = $this->pickSheetWithHeaders($spreadsheet);
        if (!$sheet || !$hdrRow || !$colCodigo || !$colDeduccion) {
            throw new \RuntimeException('No se encontró una hoja con encabezados "Código" y "Deducción".');
        }

        $sheetName = $sheet->getTitle();
        $inicio = $this->normalizeExcelDate($sheet->getCell('A7')->getValue());
        $fin = $this->normalizeExcelDate($sheet->getCell('B7')->getValue());

        if (!$inicio || !$fin) {
            throw new \RuntimeException("No se pudieron leer las fechas A7/B7 en la hoja '{$sheetName}'.");
        }

        $inicio = $inicio->copy()->startOfDay();
        $fin = $fin->copy()->endOfDay();

        $inicioStr = $inicio->toDateString();
        $finStr = $fin->toDateString();

        $counts = [
            'filas_excel' => 0,
            'omitidas' => 0,
            'pagos_completos' => 0,
            'pagos_parciales' => 0,
            'sin_pago' => 0,
            'sin_empleado' => 0,
            'sin_coincidencia' => 0,
            'acciones_aplicables' => 0,
        ];

        $actions = [];
        $sinEmpleadoDetalle = [];
        $sinCoincidenciaDetalle = [];
        $ejemplos = [];

        $empleadosCache = [];
        $cuotasCache = [];
        $empleadosExcel = [];
        $processedQuotaIds = [];
        $pendingCarry = [];

        $sheetMaxRow = $sheet->getHighestRow();
        $emptyStreak = 0;

        for ($row = $hdrRow + 1; $row <= $sheetMaxRow && $emptyStreak < self::MAX_EMPTY_ROWS; $row++) {
            $codigoAddr = Coordinate::stringFromColumnIndex($colCodigo) . $row;
            $deducAddr = Coordinate::stringFromColumnIndex($colDeduccion) . $row;

            $codigoRaw = $this->readCellSafe($sheet, $codigoAddr, false);
            $deducRaw = $this->readCellSafe($sheet, $deducAddr, true);

            $codigo = trim((string) $codigoRaw);
            $deduccion = $this->toFloat($deducRaw);

            if ($codigo === '' && ($deducRaw === null || $deduccion <= 0)) {
                $emptyStreak++;
                continue;
            }

            $emptyStreak = 0;
            $counts['filas_excel']++;

            if (count($ejemplos) < 5) {
                $ejemplos[] = "Fila {$row}: codigo='{$codigo}' deduccion_raw='{$deducRaw}' normalizado=" . number_format($deduccion, 2, '.', '');
            }

            if ($codigo === '' || $deduccion <= 0) {
                $counts['omitidas']++;
                continue;
            }

            if (isset($empleadosCache[$codigo])) {
                $empleado = $empleadosCache[$codigo];
            } else {
                $empleado = DB::table('empleado')->where('codigo_empleado', $codigo)->first();
                if (!$empleado && ctype_digit($codigo)) {
                    $empleado = DB::table('empleado')->where('codigo_empleado', ltrim($codigo, '0'))->first();
                }
                $empleadosCache[$codigo] = $empleado ?: null;
            }

            if (!$empleado) {
                $counts['sin_empleado']++;
                $sinEmpleadoDetalle[] = [
                    'fila' => $row,
                    'codigo' => $codigo,
                    'deduccion' => $this->roundMoney($deduccion),
                ];
                continue;
            }

            $empleadoArr = [
                'id' => (int) $empleado->id_empleado,
                'codigo' => $empleado->codigo_empleado,
                'nombre' => $empleado->nombre_completo,
            ];
            $empleadosExcel[$empleadoArr['id']] = $empleadoArr;

            if (!isset($cuotasCache[$empleadoArr['id']])) {
                $cuotasCache[$empleadoArr['id']] = $this->collectCuotasForEmpleado($empleadoArr['id'], $inicioStr, $finStr);
            }

            $cuotas = $cuotasCache[$empleadoArr['id']];
            $matched = false;

            foreach ($cuotas as $cuota) {
                $quotaId = $cuota['id_historial_cuotas'];
                if (isset($processedQuotaIds[$quotaId])) {
                    continue;
                }

                $expectedBase = $this->getMontoCuotaBase($cuota);
                $carry = $pendingCarry[$quotaId] ?? 0.0;
                $expected = $this->roundMoney($expectedBase + $carry);

                if ($this->moneyEq2($expected, $deduccion)) {
                    $actions[] = $this->makeFullPaymentAction($empleadoArr, $cuota, $expected, $deduccion, $inicioStr, $finStr);
                    $counts['pagos_completos']++;
                    $counts['acciones_aplicables']++;
                    $processedQuotaIds[$quotaId] = true;
                    $pendingCarry[$quotaId] = 0.0;
                    $matched = true;
                    break;
                }

                if ($deduccion > 0 && $this->roundMoney($deduccion) + 0.005 < $expected) {
                    $diff = $this->roundMoney($expected - $deduccion);
                    $actions[] = $this->makePartialPaymentAction($empleadoArr, $cuota, $expected, $deduccion, $diff, $inicioStr, $finStr, $pendingCarry);
                    $counts['pagos_parciales']++;
                    $counts['acciones_aplicables']++;
                    $processedQuotaIds[$quotaId] = true;
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $counts['sin_coincidencia']++;
                $sinCoincidenciaDetalle[] = [
                    'fila' => $row,
                    'codigo' => $codigo,
                    'deduccion' => $this->roundMoney($deduccion),
                ];
                $actions[] = [
                    'type' => 'sin_coincidencia',
                    'employee' => $empleadoArr,
                    'prestamo' => null,
                    'cuota' => null,
                    'expected' => null,
                    'pagado' => $this->roundMoney($deduccion),
                    'diferencia' => null,
                    'notes' => 'No se encontró una cuota en el rango con un monto que coincida con la deducción reportada.',
                    'updates' => [],
                ];
            }
        }

        $empleadosIdsExcel = array_keys($empleadosExcel);

        $faltantesQuery = DB::table('historial_cuotas as hc')
            ->join('prestamo as p', 'p.id_prestamo', '=', 'hc.id_prestamo')
            ->join('empleado as e', 'e.id_empleado', '=', 'p.id_empleado')
            ->whereBetween('hc.fecha_programada', [$inicioStr, $finStr])
            ->where('hc.pagado', 0)
            ->where(function ($q) {
                $q->whereNull('hc.motivo')
                    ->orWhere('hc.motivo', '')
                    ->orWhere('hc.motivo', 'PLANILLA')
                    ->orWhere('hc.motivo', 'Planilla');
            })
            ->orderBy('e.nombre_completo')
            ->orderBy('hc.fecha_programada')
            ->select(
                'hc.id_historial_cuotas',
                'hc.id_prestamo',
                'hc.num_cuota',
                'hc.fecha_programada',
                'hc.cuota_quincenal',
                'hc.cuota_mensual',
                'hc.abono_capital',
                'hc.abono_intereses',
                'hc.pagado',
                'hc.observaciones',
                'p.num_prestamo',
                'p.id_empleado',
                'e.codigo_empleado',
                'e.nombre_completo'
            );

        if (!empty($empleadosIdsExcel)) {
            $faltantesQuery->whereNotIn('p.id_empleado', $empleadosIdsExcel);
        }

        $faltantes = $faltantesQuery->get()->groupBy('id_empleado');

        foreach ($faltantes as $empleadoId => $cuotasEmpleado) {
            $empleadoInfo = [
                'id' => (int) $empleadoId,
                'codigo' => $cuotasEmpleado->first()->codigo_empleado,
                'nombre' => $cuotasEmpleado->first()->nombre_completo,
            ];

            foreach ($cuotasEmpleado as $cuotaObj) {
                $cuota = [
                    'id_historial_cuotas' => (int) $cuotaObj->id_historial_cuotas,
                    'id_prestamo' => (int) $cuotaObj->id_prestamo,
                    'num_cuota' => (int) $cuotaObj->num_cuota,
                    'fecha_programada' => (string) $cuotaObj->fecha_programada,
                    'cuota_quincenal' => $cuotaObj->cuota_quincenal !== null ? (float) $cuotaObj->cuota_quincenal : null,
                    'cuota_mensual' => $cuotaObj->cuota_mensual !== null ? (float) $cuotaObj->cuota_mensual : null,
                    'abono_capital' => (float) $cuotaObj->abono_capital,
                    'abono_intereses' => (float) $cuotaObj->abono_intereses,
                    'pagado' => (int) $cuotaObj->pagado,
                    'observaciones' => $cuotaObj->observaciones ?? '',
                    'num_prestamo' => $cuotaObj->num_prestamo,
                ];

                $quotaId = $cuota['id_historial_cuotas'];
                if (isset($processedQuotaIds[$quotaId])) {
                    continue;
                }

                $expectedBase = $this->getMontoCuotaBase($cuota);
                $carry = $pendingCarry[$quotaId] ?? 0.0;
                $expected = $this->roundMoney($expectedBase + $carry);

                if ($expected <= 0) {
                    $processedQuotaIds[$quotaId] = true;
                    $pendingCarry[$quotaId] = 0.0;
                    continue;
                }

                $actions[] = $this->makeZeroPaymentAction($empleadoInfo, $cuota, $expected, $inicioStr, $finStr, $pendingCarry);
                $counts['sin_pago']++;
                $counts['acciones_aplicables']++;
                $processedQuotaIds[$quotaId] = true;
            }
        }

        return [
            'inicio' => $inicioStr,
            'fin' => $finStr,
            'sheet' => $sheetName,
            'counts' => $counts,
            'actions' => $actions,
            'sin_empleado_detalle' => array_values($sinEmpleadoDetalle),
            'sin_coincidencia_detalle' => array_values($sinCoincidenciaDetalle),
            'ejemplos' => $ejemplos,
        ];
    }

    private function makeFullPaymentAction(array $empleado, array $cuota, float $expected, float $pagado, string $inicio, string $fin): array
    {
        return [
            'type' => 'pago_completo',
            'employee' => $empleado,
            'prestamo' => [
                'id' => $cuota['id_prestamo'],
                'numero' => $cuota['num_prestamo'],
            ],
            'cuota' => [
                'id' => $cuota['id_historial_cuotas'],
                'num' => $cuota['num_cuota'],
                'fecha' => $cuota['fecha_programada'],
            ],
            'expected' => $this->roundMoney($expected),
            'pagado' => $this->roundMoney($pagado),
            'diferencia' => 0.0,
            'notes' => 'Pago completo según planilla.',
            'updates' => [
                [
                    'action' => 'update',
                    'table' => 'historial_cuotas',
                    'primary' => 'id_historial_cuotas',
                    'id' => $cuota['id_historial_cuotas'],
                    'data' => [
                        'pagado' => 1,
                        'fecha_pago_real' => $fin,
                    ],
                    'append_observaciones' => "Pagada por planilla (ajuste {$inicio} a {$fin})",
                ],
            ],
        ];
    }

    private function makePartialPaymentAction(array $empleado, array $cuota, float $expected, float $pagado, float $diff, string $inicio, string $fin, array &$pendingCarry): array
    {
        $quotaId = $cuota['id_historial_cuotas'];
        unset($pendingCarry[$quotaId]);

        $origCap = (float) $cuota['abono_capital'];
        $origInt = (float) $cuota['abono_intereses'];

        if ($origCap + $origInt <= 0) {
            $paidInt = 0.0;
            $paidCap = $pagado;
        } else {
            $paidInt = min($origInt, $pagado);
            $paidCap = max(0.0, $pagado - $paidInt);
        }

        $montoField = $this->getMontoField($cuota);

        $updates = [];
        $currentData = [
            'pagado' => 1,
            'fecha_pago_real' => $fin,
            'ajuste' => 1,
            'abono_capital' => $this->roundMoney($paidCap),
            'abono_intereses' => $this->roundMoney($paidInt),
        ];

        if ($montoField) {
            $currentData[$montoField] = $this->roundMoney($pagado);
        }

        $updates[] = [
            'action' => 'update',
            'table' => 'historial_cuotas',
            'primary' => 'id_historial_cuotas',
            'id' => $quotaId,
            'data' => $currentData,
            'append_observaciones' => 'Pago parcial: L ' . number_format($pagado, 2, '.', '') . ' (faltó L ' . number_format($diff, 2, '.', '') . ') trasladado a la siguiente cuota',
        ];

        $next = $this->findNextQuota($cuota);
        if ($next) {
            $nextId = $next['id_historial_cuotas'];
            $pendingCarry[$nextId] = ($pendingCarry[$nextId] ?? 0.0) + $diff;

            $nextMontoField = $this->getMontoField($next) ?? 'cuota_quincenal';
            $newNextMonto = $this->roundMoney($this->getMontoCuotaBase($next) + $pendingCarry[$nextId]);
            $newNextCap = $this->roundMoney($next['abono_capital'] + $pendingCarry[$nextId]);

            $updates[] = [
                'action' => 'update',
                'table' => 'historial_cuotas',
                'primary' => 'id_historial_cuotas',
                'id' => $nextId,
                'data' => [
                    $nextMontoField => $newNextMonto,
                    'abono_capital' => $newNextCap,
                    'ajuste' => 1,
                ],
                'append_observaciones' => 'Ajuste: +L ' . number_format($diff, 2, '.', '') . " por parcial de cuota ID {$quotaId}",
            ];
        } else {
            $nextNum = (int) DB::table('historial_cuotas')->where('id_prestamo', $cuota['id_prestamo'])->max('num_cuota');
            $nextNum = $nextNum + 1;
            $fechaAjuste = Carbon::parse($cuota['fecha_programada'])->addDay()->toDateString();

            $updates[] = [
                'action' => 'insert',
                'table' => 'historial_cuotas',
                'data' => [
                    'id_prestamo' => $cuota['id_prestamo'],
                    'num_cuota' => $nextNum,
                    'fecha_programada' => $fechaAjuste,
                    'abono_capital' => $this->roundMoney($diff),
                    'abono_intereses' => 0.0,
                    'cuota_mensual' => $this->roundMoney($diff),
                    'cuota_quincenal' => $this->roundMoney($diff),
                    'saldo_pagado' => null,
                    'saldo_restante' => null,
                    'interes_pagado' => null,
                    'interes_restante' => null,
                    'ajuste' => 1,
                    'motivo' => 'AJUSTE',
                    'fecha_pago_real' => null,
                    'pagado' => 0,
                    'observaciones' => 'Ajuste por traslado de diferencia de cuota anterior (pago parcial)',
                ],
            ];
        }

        return [
            'type' => 'pago_parcial',
            'employee' => $empleado,
            'prestamo' => [
                'id' => $cuota['id_prestamo'],
                'numero' => $cuota['num_prestamo'],
            ],
            'cuota' => [
                'id' => $quotaId,
                'num' => $cuota['num_cuota'],
                'fecha' => $cuota['fecha_programada'],
            ],
            'expected' => $this->roundMoney($expected),
            'pagado' => $this->roundMoney($pagado),
            'diferencia' => $this->roundMoney($diff),
            'notes' => 'Pago parcial detectado en planilla, diferencia trasladada.',
            'updates' => $updates,
        ];
    }

    private function makeZeroPaymentAction(array $empleado, array $cuota, float $expected, string $inicio, string $fin, array &$pendingCarry): array
    {
        $quotaId = $cuota['id_historial_cuotas'];
        unset($pendingCarry[$quotaId]);

        $montoField = $this->getMontoField($cuota);

        $updates = [];
        $currentData = [
            'ajuste' => 1,
            'pagado' => 0,
            'fecha_pago_real' => null,
            'abono_capital' => 0.0,
            'abono_intereses' => 0.0,
        ];

        if ($montoField) {
            $currentData[$montoField] = 0.0;
        }

        $updates[] = [
            'action' => 'update',
            'table' => 'historial_cuotas',
            'primary' => 'id_historial_cuotas',
            'id' => $quotaId,
            'data' => $currentData,
            'append_observaciones' => "Sin deducción ({$inicio} a {$fin}). Monto L " . number_format($expected, 2, '.', '') . ' trasladado.',
        ];

        $next = $this->findNextQuota($cuota);
        if ($next) {
            $nextId = $next['id_historial_cuotas'];
            $pendingCarry[$nextId] = ($pendingCarry[$nextId] ?? 0.0) + $expected;

            $nextMontoField = $this->getMontoField($next) ?? 'cuota_quincenal';
            $newNextMonto = $this->roundMoney($this->getMontoCuotaBase($next) + $pendingCarry[$nextId]);
            $newNextCap = $this->roundMoney($next['abono_capital'] + $pendingCarry[$nextId]);

            $updates[] = [
                'action' => 'update',
                'table' => 'historial_cuotas',
                'primary' => 'id_historial_cuotas',
                'id' => $nextId,
                'data' => [
                    $nextMontoField => $newNextMonto,
                    'abono_capital' => $newNextCap,
                    'ajuste' => 1,
                ],
                'append_observaciones' => "Ajuste: +L " . number_format($expected, 2, '.', '') . " por cuota sin deducción ID {$quotaId}",
            ];
        } else {
            $nextNum = (int) DB::table('historial_cuotas')->where('id_prestamo', $cuota['id_prestamo'])->max('num_cuota');
            $nextNum = $nextNum + 1;
            $fechaAjuste = Carbon::parse($cuota['fecha_programada'])->addDay()->toDateString();

            $updates[] = [
                'action' => 'insert',
                'table' => 'historial_cuotas',
                'data' => [
                    'id_prestamo' => $cuota['id_prestamo'],
                    'num_cuota' => $nextNum,
                    'fecha_programada' => $fechaAjuste,
                    'abono_capital' => $this->roundMoney($expected),
                    'abono_intereses' => 0.0,
                    'cuota_mensual' => $this->roundMoney($expected),
                    'cuota_quincenal' => $this->roundMoney($expected),
                    'saldo_pagado' => null,
                    'saldo_restante' => null,
                    'interes_pagado' => null,
                    'interes_restante' => null,
                    'ajuste' => 1,
                    'motivo' => 'AJUSTE',
                    'fecha_pago_real' => null,
                    'pagado' => 0,
                    'observaciones' => "Ajuste por cuotas sin deducción ({$inicio} a {$fin})",
                ],
            ];
        }

        return [
            'type' => 'sin_pago',
            'employee' => $empleado,
            'prestamo' => [
                'id' => $cuota['id_prestamo'],
                'numero' => $cuota['num_prestamo'],
            ],
            'cuota' => [
                'id' => $quotaId,
                'num' => $cuota['num_cuota'],
                'fecha' => $cuota['fecha_programada'],
            ],
            'expected' => $this->roundMoney($expected),
            'pagado' => 0.0,
            'diferencia' => $this->roundMoney($expected),
            'notes' => 'Sin deducción en planilla; monto trasladado a la siguiente cuota.',
            'updates' => $updates,
        ];
    }

    private function collectCuotasForEmpleado(int $empleadoId, string $inicio, string $fin): array
    {
        return DB::table('historial_cuotas as hc')
            ->join('prestamo as p', 'p.id_prestamo', '=', 'hc.id_prestamo')
            ->where('p.id_empleado', $empleadoId)
            ->whereBetween('hc.fecha_programada', [$inicio, $fin])
            ->where('hc.pagado', 0)
            ->where(function ($q) {
                $q->whereNull('hc.motivo')
                    ->orWhere('hc.motivo', '')
                    ->orWhere('hc.motivo', 'PLANILLA')
                    ->orWhere('hc.motivo', 'Planilla');
            })
            ->orderBy('hc.fecha_programada')
            ->orderBy('hc.num_cuota')
            ->select(
                'hc.id_historial_cuotas',
                'hc.id_prestamo',
                'hc.num_cuota',
                'hc.fecha_programada',
                'hc.cuota_quincenal',
                'hc.cuota_mensual',
                'hc.abono_capital',
                'hc.abono_intereses',
                'hc.pagado',
                'hc.observaciones',
                'p.num_prestamo'
            )
            ->get()
            ->map(function ($row) {
                return [
                    'id_historial_cuotas' => (int) $row->id_historial_cuotas,
                    'id_prestamo' => (int) $row->id_prestamo,
                    'num_cuota' => (int) $row->num_cuota,
                    'fecha_programada' => (string) $row->fecha_programada,
                    'cuota_quincenal' => $row->cuota_quincenal !== null ? (float) $row->cuota_quincenal : null,
                    'cuota_mensual' => $row->cuota_mensual !== null ? (float) $row->cuota_mensual : null,
                    'abono_capital' => (float) $row->abono_capital,
                    'abono_intereses' => (float) $row->abono_intereses,
                    'pagado' => (int) $row->pagado,
                    'observaciones' => $row->observaciones ?? '',
                    'num_prestamo' => $row->num_prestamo,
                ];
            })
            ->all();
    }

    private function getMontoCuotaBase(array $cuota): float
    {
        if ($cuota['cuota_quincenal'] !== null) {
            return (float) $cuota['cuota_quincenal'];
        }
        if ($cuota['cuota_mensual'] !== null) {
            return (float) $cuota['cuota_mensual'];
        }
        return (float) $cuota['abono_capital'] + (float) $cuota['abono_intereses'];
    }

    private function getMontoField(array $cuota): ?string
    {
        if ($cuota['cuota_quincenal'] !== null) {
            return 'cuota_quincenal';
        }
        if ($cuota['cuota_mensual'] !== null) {
            return 'cuota_mensual';
        }
        return null;
    }

    private function findNextQuota(array $cuota): ?array
    {
        $next = DB::table('historial_cuotas as hc')
            ->join('prestamo as p', 'p.id_prestamo', '=', 'hc.id_prestamo')
            ->where('hc.id_prestamo', $cuota['id_prestamo'])
            ->where('hc.fecha_programada', '>', $cuota['fecha_programada'])
            ->orderBy('hc.fecha_programada')
            ->orderBy('hc.num_cuota')
            ->select(
                'hc.id_historial_cuotas',
                'hc.id_prestamo',
                'hc.num_cuota',
                'hc.fecha_programada',
                'hc.cuota_quincenal',
                'hc.cuota_mensual',
                'hc.abono_capital',
                'hc.abono_intereses',
                'hc.pagado',
                'hc.observaciones',
                'p.num_prestamo'
            )
            ->first();

        if (!$next) {
            return null;
        }

        return [
            'id_historial_cuotas' => (int) $next->id_historial_cuotas,
            'id_prestamo' => (int) $next->id_prestamo,
            'num_cuota' => (int) $next->num_cuota,
            'fecha_programada' => (string) $next->fecha_programada,
            'cuota_quincenal' => $next->cuota_quincenal !== null ? (float) $next->cuota_quincenal : null,
            'cuota_mensual' => $next->cuota_mensual !== null ? (float) $next->cuota_mensual : null,
            'abono_capital' => (float) $next->abono_capital,
            'abono_intereses' => (float) $next->abono_intereses,
            'pagado' => (int) $next->pagado,
            'observaciones' => $next->observaciones ?? '',
            'num_prestamo' => $next->num_prestamo,
        ];
    }

    private function pickSheetWithHeaders($spreadsheet): array
    {
        foreach ($spreadsheet->getWorksheetIterator() as $ws) {
            [$hdrRow, $colCodigo, $colNombre, $colDeduccion] = $this->detectHeaders($ws);
            if ($hdrRow && $colCodigo && $colDeduccion) {
                return [$ws, $hdrRow, $colCodigo, $colNombre, $colDeduccion];
            }
        }

        $ws = $spreadsheet->getActiveSheet();
        [$hdrRow, $colCodigo, $colNombre, $colDeduccion] = $this->detectHeaders($ws);
        return [$ws, $hdrRow, $colCodigo, $colNombre, $colDeduccion];
    }

    private function readCellSafe(Worksheet $sheet, string $addr, bool $preferCalc = true)
    {
        try {
            $cell = $sheet->getCell($addr);
            if ($preferCalc) {
                $value = $cell->getCalculatedValue();
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }

            return $cell->getValue();
        } catch (\Throwable $e) {
            try {
                return $sheet->getCell($addr)->getValue();
            } catch (\Throwable $e2) {
                return null;
            }
        }
    }

    private function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (!is_string($value)) {
            return 0.0;
        }

        $sanitized = preg_replace('/\x{00A0}|\s/u', '', trim($value));
        if ($sanitized === '') {
            return 0.0;
        }

        $negative = str_contains($sanitized, '-');
        $sanitized = preg_replace('/[^0-9\.,-]/u', '', $sanitized);

        $lastDot = strrpos($sanitized, '.');
        $lastComma = strrpos($sanitized, ',');
        $lastSep = max($lastDot !== false ? $lastDot : -1, $lastComma !== false ? $lastComma : -1);

        if ($lastSep === -1) {
            $digits = preg_replace('/\D/', '', $sanitized);
            $num = $digits === '' ? 0.0 : (float) $digits;
            return $negative ? -$num : $num;
        }

        $decimals = preg_replace('/\D/', '', substr($sanitized, $lastSep + 1));
        $decCount = strlen($decimals);
        $allDigits = preg_replace('/\D/', '', $sanitized);
        if ($allDigits === '') {
            return 0.0;
        }

        $num = $decCount > 0 ? (float) $allDigits / (10 ** $decCount) : (float) $allDigits;
        return $negative ? -$num : $num;
    }

    private function roundMoney(float $value): float
    {
        return round($value + 1e-9, 2);
    }

    private function moneyEq2(float $a, float $b): bool
    {
        return abs($this->roundMoney($a) - $this->roundMoney($b)) < 0.005;
    }

    private function normalizeExcelDate($value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_numeric($value)) {
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            return Carbon::instance($date);
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function detectHeaders(Worksheet $sheet): array
    {
        for ($row = 1; $row <= 30; $row++) {
            $map = [];
            for ($col = 1; $col <= 20; $col++) {
                $letter = Coordinate::stringFromColumnIndex($col);
                $value = $sheet->getCell($letter . $row)->getValue();
                $normalized = is_string($value) ? mb_strtolower(trim($value)) : '';

                if (in_array($normalized, ['codigo', 'código', 'codigo empleado', 'código empleado'], true)) {
                    $map['codigo'] = $col;
                }
                if (str_contains($normalized, 'deduc')) {
                    $map['deduccion'] = $col;
                }
                if (str_contains($normalized, 'nombre')) {
                    $map['nombre'] = $col;
                }
            }

            if (isset($map['codigo']) && isset($map['deduccion'])) {
                return [$row, $map['codigo'], $map['nombre'] ?? null, $map['deduccion']];
            }
        }

        return [null, null, null, null];
    }
}

