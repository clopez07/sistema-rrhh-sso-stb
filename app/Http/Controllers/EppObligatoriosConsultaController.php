<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EppObligatoriosConsultaController extends Controller
{
    public function index(Request $request)
    {
        [$puestoToken, $puestoTipo, $puestoId] = $this->normalizePuestoToken((string) $request->input('puesto', ''));
        $anio = $request->integer('anio') ?: (int) date('Y');

        $puestosMatriz = DB::table('puesto_trabajo_matriz as ptm')
            ->select(
                'ptm.id_puesto_trabajo_matriz as id',
                'ptm.puesto_trabajo_matriz as nombre'
            )
            ->where(function ($q) {
                $q->where('ptm.estado', 1)->orWhereNull('ptm.estado');
            })
            ->get();

        $puestosLegacy = DB::table('puesto_trabajo as pt')
            ->select(
                'pt.id_puesto_trabajo as id',
                'pt.puesto_trabajo as nombre'
            )
            ->where(function ($q) {
                $q->where('pt.estado', 1)->orWhereNull('pt.estado');
            })
            ->get();

        $legacyByName = [];
        foreach ($puestosLegacy as $row) {
            $normalized = $this->normalizeNombre($row->nombre);
            if ($normalized !== '') {
                $legacyByName[$normalized] = (int) $row->id;
            }
        }

        foreach ($puestosMatriz as $row) {
            $normalized = $this->normalizeNombre($row->nombre);
            $row->legacy_id = $legacyByName[$normalized] ?? null;
        }

        $puestos = collect($puestosMatriz)
            ->map(function ($row) {
                return (object) [
                    'token'     => 'matriz:' . $row->id,
                    'tipo'      => 'matriz',
                    'id'        => (int) $row->id,
                    'nombre'    => $row->nombre,
                    'label'     => $row->nombre,
                    'legacy_id' => $row->legacy_id ? (int) $row->legacy_id : null,
                ];
            })
            ->sortBy(function ($row) {
                return strtolower($row->nombre ?? '');
            })
            ->values();

        $puestoToken = $puestoTipo && $puestoId !== null ? $puestoTipo . ':' . $puestoId : '';
        $puestoLookup = $puestos->keyBy('token');
        $puestoSeleccionado = $puestoToken === '' ? null : $puestoLookup->get($puestoToken);
        $legacyFallbackId = null;
        if ($puestoTipo === 'matriz' && $puestoSeleccionado && $puestoSeleccionado->legacy_id) {
            $legacyFallbackId = (int) $puestoSeleccionado->legacy_id;
        }

        $years = range((int) date('Y'), (int) date('Y') - 10);

        $matriz = [];
        $empleados = collect();
        $eppsObligatorios = collect();

        if ($puestoTipo && $puestoId) {
            $hasMatrixColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo_matriz');
            $hasLegacyColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo');

            $eppsQuery = DB::table('puestos_epp as pe')
                ->join('epp as e', 'e.id_epp', '=', 'pe.id_epp');

            if ($puestoTipo === 'matriz') {
                $eppsQuery->where(function ($q) use ($hasMatrixColumn, $hasLegacyColumn, $puestoId, $legacyFallbackId) {
                    $applied = false;
                    if ($hasMatrixColumn) {
                        $q->where('pe.id_puesto_trabajo_matriz', $puestoId);
                        $applied = true;
                    }
                    if ($legacyFallbackId && $hasLegacyColumn) {
                        $applied
                            ? $q->orWhere('pe.id_puesto_trabajo', $legacyFallbackId)
                            : $q->where('pe.id_puesto_trabajo', $legacyFallbackId);
                    }
                });
            } else {
                if ($hasLegacyColumn) {
                    $eppsQuery->where('pe.id_puesto_trabajo', $puestoId);
                } elseif ($hasMatrixColumn) {
                    $eppsQuery->where('pe.id_puesto_trabajo_matriz', $puestoId);
                }
            }

            $eppsObligatorios = $eppsQuery
                ->orderBy('e.equipo')
                ->get([
                    'e.id_epp',
                    'e.equipo',
                    'e.codigo',
                    'e.marca',
                    'e.id_tipo_proteccion',
                ]);

            $empleadosQuery = DB::table('empleado')
                ->select('id_empleado', 'nombre_completo', 'codigo_empleado', 'identidad')
                ->where(function ($q) {
                    $q->where('estado', 1)->orWhereNull('estado');
                });

            if ($puestoTipo === 'matriz') {
                $empleadosQuery->where(function ($q) use ($puestoId, $legacyFallbackId) {
                    $q->where('id_puesto_trabajo_matriz', $puestoId);
                    if ($legacyFallbackId) {
                        $q->orWhere('id_puesto_trabajo', $legacyFallbackId);
                    }
                });
            } else {
                $empleadosQuery->where('id_puesto_trabajo', $puestoId);
            }

            $empleados = $empleadosQuery
                ->orderBy('nombre_completo')
                ->get();

            $asignaciones = collect();
            if ($eppsObligatorios->count() && $empleados->count()) {
                $eppIds = $eppsObligatorios->pluck('id_epp')->all();
                $empleadoIds = $empleados->pluck('id_empleado')->all();

                $raw = "LOWER(TRIM(asig.fecha_entrega_epp))";
                $clean = "
    REPLACE(
      REPLACE(
        REPLACE(
          REPLACE($raw, 'a. m.', ''),
        'p. m.', ''),
      'a.m.', ''),
    'p.m.', '')
";
                $clean = "REPLACE(REPLACE($clean, ',', ''), '  ', ' ')";
                $firstToken = "SUBSTRING_INDEX($clean, ' ', 1)";
                $isoToken = "SUBSTRING_INDEX($clean, 't', 1)";

                $parsed = "COALESCE(
    STR_TO_DATE($clean, '%Y-%m-%d'),
    STR_TO_DATE($clean, '%Y/%m/%d'),
    STR_TO_DATE($clean, '%d/%m/%Y'),
    STR_TO_DATE($clean, '%d-%m-%Y'),
    STR_TO_DATE($clean, '%m/%d/%Y'),
    STR_TO_DATE($clean, '%m-%d-%Y'),
    STR_TO_DATE($clean, '%d.%m.%Y'),
    STR_TO_DATE($firstToken, '%Y-%m-%d'),
    STR_TO_DATE($firstToken, '%d/%m/%Y'),
    STR_TO_DATE($firstToken, '%m/%d/%Y'),
    STR_TO_DATE($firstToken, '%d-%m-%Y'),
    STR_TO_DATE($isoToken, '%Y-%m-%d')
)";

                $asignaciones = DB::table('asignacion_epp as asig')
                    ->select('asig.id_epp', 'asig.id_empleado')
                    ->selectRaw("DATE_FORMAT(MAX($parsed), '%Y-%m-%d') as fecha")
                    ->whereIn('asig.id_epp', $eppIds)
                    ->whereIn('asig.id_empleado', $empleadoIds)
                    ->whereRaw("YEAR($parsed) = ?", [$anio])
                    ->groupBy('asig.id_epp', 'asig.id_empleado')
                    ->get();
            }

            $recibido = [];
            foreach ($asignaciones as $a) {
                $recibido[$a->id_epp][$a->id_empleado] = $a->fecha;
            }

            $matriz = [];
            foreach ($eppsObligatorios as $epp) {
                $si = [];
                $no = [];
                foreach ($empleados as $emp) {
                    $fecha = $recibido[$epp->id_epp][$emp->id_empleado] ?? null;
                    if ($fecha) {
                        $si[] = ['empleado' => $emp, 'fecha' => $fecha];
                    } else {
                        $no[] = $emp;
                    }
                }

                $matriz[] = [
                    'epp'        => $epp,
                    'asignados'  => $si,
                    'pendientes' => $no,
                    'total_emp'  => $empleados->count(),
                ];
            }
        }

        return view('riesgos.epp_obligatorios', [
            'puestos'             => $puestos,
            'years'               => $years,
            'puestoToken'         => $puestoToken,
            'puestoTipo'          => $puestoTipo,
            'puestoId'            => $puestoId,
            'puestoSeleccionado'  => $puestoSeleccionado,
            'anio'                => $anio,
            'matriz'              => $matriz,
            'empleados'           => $empleados,
            'eppsObligatorios'    => $eppsObligatorios,
        ]);
    }


    public function export(Request $request)
    {
        [$puestoToken, $puestoTipo, $puestoId] = $this->normalizePuestoToken((string) $request->input('puesto', ''));
        $anio = $request->integer('anio') ?: (int) date('Y');

        if (!$puestoTipo || !$puestoId) {
            return redirect()->route('riesgos.epp.obligatorios')
                ->with('error', 'Selecciona un puesto para exportar.');
        }

        $legacyFallbackId = null;
        if ($puestoTipo === 'matriz') {
            $legacyFallbackId = $this->resolveLegacyIdForMatrix($puestoId);
        }

        $hasMatrixColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo_matriz');
        $hasLegacyColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo');

        $eppsQuery = DB::table('puestos_epp as pe')
            ->join('epp as e', 'e.id_epp', '=', 'pe.id_epp');

        if ($puestoTipo === 'matriz') {
            $eppsQuery->where(function ($q) use ($hasMatrixColumn, $hasLegacyColumn, $puestoId, $legacyFallbackId) {
                $applied = false;
                if ($hasMatrixColumn) {
                    $q->where('pe.id_puesto_trabajo_matriz', $puestoId);
                    $applied = true;
                }
                if ($legacyFallbackId && $hasLegacyColumn) {
                    $applied
                        ? $q->orWhere('pe.id_puesto_trabajo', $legacyFallbackId)
                        : $q->where('pe.id_puesto_trabajo', $legacyFallbackId);
                }
            });
        } else {
            if ($hasLegacyColumn) {
                $eppsQuery->where('pe.id_puesto_trabajo', $puestoId);
            } elseif ($hasMatrixColumn) {
                $eppsQuery->where('pe.id_puesto_trabajo_matriz', $puestoId);
            }
        }

        $eppsObligatorios = $eppsQuery
            ->orderBy('e.equipo')
            ->get(['e.id_epp', 'e.equipo', 'e.codigo', 'e.marca']);

        $empleadosQuery = DB::table('empleado')
            ->select('id_empleado', 'nombre_completo', 'codigo_empleado', 'identidad')
            ->where(function ($q) {
                $q->where('estado', 1)->orWhereNull('estado');
            });

        if ($puestoTipo === 'matriz') {
            $empleadosQuery->where(function ($q) use ($puestoId, $legacyFallbackId) {
                $q->where('id_puesto_trabajo_matriz', $puestoId);
                if ($legacyFallbackId) {
                    $q->orWhere('id_puesto_trabajo', $legacyFallbackId);
                }
            });
        } else {
            $empleadosQuery->where('id_puesto_trabajo', $puestoId);
        }

        $empleados = $empleadosQuery
            ->orderBy('nombre_completo')
            ->get();

        $rows = [];
        if ($eppsObligatorios->count() && $empleados->count()) {
            $eppIds = $eppsObligatorios->pluck('id_epp')->all();
            $empleadoIds = $empleados->pluck('id_empleado')->all();

            $raw = "TRIM(asig.fecha_entrega_epp)";
            $firstToken = "SUBSTRING_INDEX($raw, ' ', 1)";
            $parsed = "COALESCE(
                CAST(asig.fecha_entrega_epp AS DATE),
                STR_TO_DATE($raw, '%Y-%m-%d'),
                STR_TO_DATE($raw, '%Y/%m/%d'),
                STR_TO_DATE($raw, '%d/%m/%Y'),
                STR_TO_DATE($raw, '%d-%m-%Y'),
                STR_TO_DATE($raw, '%d.%m.%Y'),
                STR_TO_DATE($raw, '%Y-%m-%d %H:%i:%s'),
                STR_TO_DATE($raw, '%d/%m/%Y %H:%i:%s'),
                STR_TO_DATE($firstToken, '%Y-%m-%d'),
                STR_TO_DATE($firstToken, '%d/%m/%Y'),
                STR_TO_DATE(REPLACE($raw,'/','-'), '%d-%m-%Y'),
                STR_TO_DATE(REPLACE($raw,'-','/'), '%d/%m/%Y')
            )";

            $asig = DB::table('asignacion_epp as asig')
                ->select('asig.id_epp', 'asig.id_empleado')
                ->selectRaw("DATE_FORMAT(MAX($parsed), '%Y-%m-%d') as fecha")
                ->whereIn('asig.id_epp', $eppIds)
                ->whereIn('asig.id_empleado', $empleadoIds)
                ->whereRaw("YEAR($parsed) = ?", [$anio])
                ->groupBy('asig.id_epp', 'asig.id_empleado')
                ->get();

            $idxFecha = [];
            foreach ($asig as $a) {
                $idxFecha[$a->id_epp][$a->id_empleado] = $a->fecha;
            }

            foreach ($eppsObligatorios as $epp) {
                foreach ($empleados as $emp) {
                    $fecha = $idxFecha[$epp->id_epp][$emp->id_empleado] ?? null;
                    $rows[] = [
                        'EPP'       => $epp->equipo,
                        'CODIGO'    => $epp->codigo,
                        'MARCA'     => $epp->marca,
                        'EMPLEADO'  => $emp->nombre_completo,
                        'IDENTIDAD' => $emp->identidad,
                        'COD_EMPL'  => $emp->codigo_empleado,
                        'ESTADO'    => $fecha ? 'ENTREGADO' : 'PENDIENTE',
                        'FECHA'     => $fecha,
                        'ANIO'      => $anio,
                    ];
                }
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['EPP','CODIGO','MARCA','EMPLEADO','IDENTIDAD','COD_EMPL','ESTADO','FECHA','ANIO'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.'1', $h);
            $col++;
        }
        $r = 2;
        foreach ($rows as $row) {
            $c = 'A';
            foreach ($headers as $h) {
                $sheet->setCellValue($c.$r, $row[$h] ?? null);
                $c++;
            }
            $r++;
        }
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('H2:H'.($r-1))->getNumberFormat()->setFormatCode('yyyy-mm-dd');

        $safeToken = str_replace(':', '_', $puestoToken);
        $fileName = 'EPP_Obligatorios_'.$safeToken.'_'.$anio.'.xlsx';
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }


    private function resolveLegacyIdForMatrix(int $matrizId): ?int
    {
        $nombre = DB::table('puesto_trabajo_matriz')
            ->where('id_puesto_trabajo_matriz', $matrizId)
            ->value('puesto_trabajo_matriz');

        if (!$nombre) {
            return null;
        }

        $normalized = $this->normalizeNombre($nombre);
        if ($normalized === '') {
            return null;
        }

        $rows = DB::table('puesto_trabajo')
            ->select('id_puesto_trabajo', 'puesto_trabajo')
            ->get();

        foreach ($rows as $row) {
            if ($this->normalizeNombre($row->puesto_trabajo) === $normalized) {
                return (int) $row->id_puesto_trabajo;
            }
        }

        return null;
    }

    private function normalizeNombre(?string $valor): string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return '';
        }

        $valor = Str::ascii($valor);
        $valor = strtolower($valor);
        $valor = preg_replace('/\s+/', ' ', $valor);

        return trim($valor);
    }

    private function normalizePuestoToken(string $valor): array
    {
        $valor = trim($valor);
        if ($valor === '') {
            return ['', null, null];
        }

        if (preg_match('/^(matriz|legacy):(\d+)$/', $valor, $matches)) {
            return [$matches[1] . ':' . $matches[2], $matches[1], (int) $matches[2]];
        }

        if (ctype_digit($valor)) {
            $id = (int) $valor;
            return ['legacy:' . $id, 'legacy', $id];
        }

        return ['', null, null];
    }
}
