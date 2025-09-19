<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvaluacionRiesgosExportController extends Controller
{
    public function puestos()
    {
        $tableCandidates = [
            'puesto_trabajo_matriz',
            'puestos_trabajo_matriz',
            'puestostrabajo',
            'puestos_trabajo',
            'matriz_puestos_trabajo',
            'matrizpuestos',
        ];

        foreach ($tableCandidates as $t) {
            try {
                $rows = DB::table($t)->limit(10000)->get();
            } catch (\Throwable $e) { continue; }
            if ($rows->isEmpty()) continue;

            // Detectar columnas
            $first = (array)$rows->first();
            $idKey = collect(['id_puesto_trabajo_matriz','id_puesto_trabajo','id_puesto','id'])->first(fn($k)=>array_key_exists($k,$first));
            $nameKey = collect(['puesto_trabajo_matriz','puesto_actual','puesto','nombre','puesto_nombre'])->first(fn($k)=>array_key_exists($k,$first));
            if (!$idKey || !$nameKey) continue;

            $out = $rows->map(function($r) use ($idKey,$nameKey){
                $arr = (array)$r;
                return [
                    'id_puesto_trabajo_matriz' => $arr[$idKey] ?? null,
                    'puesto_trabajo_matriz'    => (string)($arr[$nameKey] ?? ''),
                ];
            })->filter(fn($x)=>$x['id_puesto_trabajo_matriz']!==null)->values();

            return response()->json($out);
        }
        return response()->json([], 200);
    }
    public function export(Request $request)
    {
        $request->validate([
            'ptm_id' => 'required']
        );
        $ptmId = $request->input('ptm_id');

        // 1) Encabezado del puesto desde puesto_trabajo_matriz + departamento
        $pt = DB::table('puesto_trabajo_matriz as pt')
            ->leftJoin('departamento as d', 'd.id_departamento', '=', 'pt.id_departamento')
            ->where('pt.id_puesto_trabajo_matriz', $ptmId)
            ->select('pt.puesto_trabajo_matriz as puesto', 'pt.num_empleados', 'd.departamento')
            ->first();

        if (!$pt) {
            return back()->with('error', 'No se encontró el puesto en "puesto_trabajo_matriz".');
        }

        $header = [
            'departamento' => $pt->departamento ?? '',
            'puesto'       => $pt->puesto ?? '',
            'empleados'    => $pt->num_empleados ?? '',
        ];

        // 2) Cargar evaluación de riesgos para ese puesto (JOINs para nombres y niveles)
        $rows = DB::table('evaluacion_riesgo as er')
            ->join('riesgo as r', 'r.id_riesgo', '=', 'er.id_riesgo')
            ->leftJoin('probabilidad as pr', 'pr.id_probabilidad', '=', 'er.id_probabilidad')
            ->leftJoin('consecuencia as co', 'co.id_consecuencia', '=', 'er.id_consecuencia')
            ->leftJoin('nivel_riesgo as nr', 'nr.id_nivel_riesgo', '=', 'er.id_nivel_riesgo')
            ->where('er.id_puesto_trabajo_matriz', $ptmId)
            ->select('r.nombre_riesgo', 'pr.probabilidad', 'co.consecuencia', 'nr.nivel_riesgo')
            ->get();

        if ($rows->isEmpty()) {
            return back()->with('error', 'No se encontraron registros en evaluacion_riesgo para el puesto seleccionado.');
        }

        // Mapas a códigos de la plantilla
        $probMap = [ 'ALTA' => 'A', 'MEDIA' => 'M', 'BAJA' => 'B' ];
        $consMap = [ 'LEVE' => 'L', 'GRAVE' => 'G', 'MUY GRAVE' => 'MG' ];
        $valMap  = [
            'RIESGO IRRELEVANTE' => 'I',
            'RIESGO BAJO'        => 'B',
            'RIESGO MEDIO'       => 'M',
            'RIESGO ALTO'        => 'A',
            'RIESGO MUY ALTO'    => 'MA',
        ];

        // 3) Normaliza -> diccionario nombre_riesgo -> {prob,cons,val} en códigos
        $riskDict = [];
        foreach ($rows as $r) {
            $riskName = (string)($r->nombre_riesgo ?? '');
            if (!$riskName) continue;
            $key = self::norm($riskName);
            $prob = strtoupper((string)($r->probabilidad ?? ''));
            $cons = strtoupper((string)($r->consecuencia ?? ''));
            $val  = strtoupper((string)($r->nivel_riesgo ?? ''));
            $riskDict[$key] = [
                'prob' => $probMap[$prob] ?? '',
                'cons' => $consMap[$cons] ?? '',
                'val'  => $valMap[$val]   ?? '',
            ];
        }

        // 3b) Consulta extendida de riesgos químicos y puestos sin riesgos químicos
        $sqlExtra = <<<SQL
SELECT *
FROM (
  SELECT
    ptm.id_puesto_trabajo_matriz,
    ptm.puesto_trabajo_matriz AS nombre_puesto,
    'Partículas de polvo, humos, gases y vapores' AS nombre_riesgo,
    (
      SELECT p.probabilidad
      FROM probabilidad p
      WHERE p.id_probabilidad = (
        SELECT vr.id_probabilidad
        FROM valoracion_riesgo vr
        WHERE vr.id_nivel_riesgo = a.nivel_polvo
        ORDER BY vr.id_probabilidad DESC, vr.id_consecuencia DESC
        LIMIT 1
      )
    ) AS nombre_probabilidad,
    (
      SELECT c.consecuencia
      FROM consecuencia c
      WHERE c.id_consecuencia = (
        SELECT vr.id_consecuencia
        FROM valoracion_riesgo vr
        WHERE vr.id_nivel_riesgo = a.nivel_polvo
        ORDER BY vr.id_probabilidad DESC, vr.id_consecuencia DESC
        LIMIT 1
      )
    ) AS nombre_consecuencia,
    nrp.nivel_riesgo   AS nombre_nivel_riesgo
  FROM puesto_trabajo_matriz ptm
  LEFT JOIN (
    SELECT
      qp.id_puesto_trabajo_matriz,
      MAX(COALESCE(q.particulas_polvo,0)) AS polvo,
      MAX(CASE WHEN COALESCE(q.particulas_polvo,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_polvo,
      MAX(COALESCE(q.sustancias_corrosivas,0)) AS corrosivas,
      MAX(CASE WHEN COALESCE(q.sustancias_corrosivas,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_corrosivas,
      MAX(COALESCE(q.sustancias_toxicas,0)) AS toxicas,
      MAX(CASE WHEN COALESCE(q.sustancias_toxicas,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_toxicas,
      MAX(COALESCE(q.sustancias_irritantes,0)) AS irritantes,
      MAX(CASE WHEN COALESCE(q.sustancias_irritantes,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_irritantes
    FROM quimico_puesto qp
    JOIN quimico q ON q.id_quimico = qp.id_quimico
    GROUP BY qp.id_puesto_trabajo_matriz
  ) a  ON a.id_puesto_trabajo_matriz = ptm.id_puesto_trabajo_matriz
  LEFT JOIN nivel_riesgo nrp ON nrp.id_nivel_riesgo = NULLIF(a.nivel_polvo,0)
  WHERE IFNULL(a.polvo,0)=1

  UNION ALL

  SELECT
    ptm.id_puesto_trabajo_matriz,
    ptm.puesto_trabajo_matriz AS nombre_puesto,
    'SUSTANCIAS CORROSIVAS'   AS nombre_riesgo,
    (
      SELECT p.probabilidad
      FROM probabilidad p
      WHERE p.id_probabilidad = (
        SELECT vr.id_probabilidad
        FROM valoracion_riesgo vr
        WHERE vr.id_nivel_riesgo = a.nivel_corrosivas
        ORDER BY vr.id_probabilidad DESC, vr.id_consecuencia DESC
        LIMIT 1
      )
    ) AS nombre_probabilidad,
    (
      SELECT c.consecuencia
      FROM consecuencia c
      WHERE c.id_consecuencia = (
        SELECT vr.id_consecuencia
        FROM valoracion_riesgo vr
        WHERE vr.id_nivel_riesgo = a.nivel_corrosivas
        ORDER BY vr.id_probabilidad DESC, vr.id_consecuencia DESC
        LIMIT 1
      )
    ) AS nombre_consecuencia,
    nrc.nivel_riesgo    AS nombre_nivel_riesgo
  FROM puesto_trabajo_matriz ptm
  LEFT JOIN (
    SELECT
      qp.id_puesto_trabajo_matriz,
      MAX(COALESCE(q.particulas_polvo,0)) AS polvo,
      MAX(CASE WHEN COALESCE(q.particulas_polvo,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_polvo,
      MAX(COALESCE(q.sustancias_corrosivas,0)) AS corrosivas,
      MAX(CASE WHEN COALESCE(q.sustancias_corrosivas,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_corrosivas,
      MAX(COALESCE(q.sustancias_toxicas,0)) AS toxicas,
      MAX(CASE WHEN COALESCE(q.sustancias_toxicas,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_toxicas,
      MAX(COALESCE(q.sustancias_irritantes,0)) AS irritantes,
      MAX(CASE WHEN COALESCE(q.sustancias_irritantes,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_irritantes
    FROM quimico_puesto qp
    JOIN quimico q ON q.id_quimico = qp.id_quimico
    GROUP BY qp.id_puesto_trabajo_matriz
  ) a  ON a.id_puesto_trabajo_matriz = ptm.id_puesto_trabajo_matriz
  LEFT JOIN nivel_riesgo nrc ON nrc.id_nivel_riesgo = NULLIF(a.nivel_corrosivas,0)
  WHERE IFNULL(a.corrosivas,0)=1

  UNION ALL

  SELECT
    ptm.id_puesto_trabajo_matriz,
    ptm.puesto_trabajo_matriz AS nombre_puesto,
    'SUSTANCIAS TÓXICAS'      AS nombre_riesgo,
    (
      SELECT p.probabilidad
      FROM probabilidad p
      WHERE p.id_probabilidad = (
        SELECT vr.id_probabilidad
        FROM valoracion_riesgo vr
        WHERE vr.id_nivel_riesgo = a.nivel_toxicas
        ORDER BY vr.id_probabilidad DESC, vr.id_consecuencia DESC
        LIMIT 1
      )
    ) AS nombre_probabilidad,
    (
      SELECT c.consecuencia
      FROM consecuencia c
      WHERE c.id_consecuencia = (
        SELECT vr.id_consecuencia
        FROM valoracion_riesgo vr
        WHERE vr.id_nivel_riesgo = a.nivel_toxicas
        ORDER BY vr.id_probabilidad DESC, vr.id_consecuencia DESC
        LIMIT 1
      )
    ) AS nombre_consecuencia,
    nrt.nivel_riesgo    AS nombre_nivel_riesgo
  FROM puesto_trabajo_matriz ptm
  LEFT JOIN (
    SELECT
      qp.id_puesto_trabajo_matriz,
      MAX(COALESCE(q.particulas_polvo,0)) AS polvo,
      MAX(CASE WHEN COALESCE(q.particulas_polvo,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_polvo,
      MAX(COALESCE(q.sustancias_corrosivas,0)) AS corrosivas,
      MAX(CASE WHEN COALESCE(q.sustancias_corrosivas,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_corrosivas,
      MAX(COALESCE(q.sustancias_toxicas,0)) AS toxicas,
      MAX(CASE WHEN COALESCE(q.sustancias_toxicas,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_toxicas,
      MAX(COALESCE(q.sustancias_irritantes,0)) AS irritantes,
      MAX(CASE WHEN COALESCE(q.sustancias_irritantes,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_irritantes
    FROM quimico_puesto qp
    JOIN quimico q ON q.id_quimico = qp.id_quimico
    GROUP BY qp.id_puesto_trabajo_matriz
  ) a  ON a.id_puesto_trabajo_matriz = ptm.id_puesto_trabajo_matriz
  LEFT JOIN nivel_riesgo nrt ON nrt.id_nivel_riesgo = NULLIF(a.nivel_toxicas,0)
  WHERE IFNULL(a.toxicas,0)=1

  UNION ALL

  SELECT
    ptm.id_puesto_trabajo_matriz,
    ptm.puesto_trabajo_matriz AS nombre_puesto,
    'Sustancias irritantes o alergizantes' AS nombre_riesgo,
    (
      SELECT p.probabilidad
      FROM probabilidad p
      WHERE p.id_probabilidad = (
        SELECT vr.id_probabilidad
        FROM valoracion_riesgo vr
        WHERE vr.id_nivel_riesgo = a.nivel_irritantes
        ORDER BY vr.id_probabilidad DESC, vr.id_consecuencia DESC
        LIMIT 1
      )
    ) AS nombre_probabilidad,
    (
      SELECT c.consecuencia
      FROM consecuencia c
      WHERE c.id_consecuencia = (
        SELECT vr.id_consecuencia
        FROM valoracion_riesgo vr
        WHERE vr.id_nivel_riesgo = a.nivel_irritantes
        ORDER BY vr.id_probabilidad DESC, vr.id_consecuencia DESC
        LIMIT 1
      )
    ) AS nombre_consecuencia,
    nri.nivel_riesgo    AS nombre_nivel_riesgo
  FROM puesto_trabajo_matriz ptm
  LEFT JOIN (
    SELECT
      qp.id_puesto_trabajo_matriz,
      MAX(COALESCE(q.particulas_polvo,0)) AS polvo,
      MAX(CASE WHEN COALESCE(q.particulas_polvo,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_polvo,
      MAX(COALESCE(q.sustancias_corrosivas,0)) AS corrosivas,
      MAX(CASE WHEN COALESCE(q.sustancias_corrosivas,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_corrosivas,
      MAX(COALESCE(q.sustancias_toxicas,0)) AS toxicas,
      MAX(CASE WHEN COALESCE(q.sustancias_toxicas,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_toxicas,
      MAX(COALESCE(q.sustancias_irritantes,0)) AS irritantes,
      MAX(CASE WHEN COALESCE(q.sustancias_irritantes,0)=1 THEN COALESCE(q.id_nivel_riesgo,0) ELSE 0 END) AS nivel_irritantes
    FROM quimico_puesto qp
    JOIN quimico q ON q.id_quimico = qp.id_quimico
    GROUP BY qp.id_puesto_trabajo_matriz
  ) a  ON a.id_puesto_trabajo_matriz = ptm.id_puesto_trabajo_matriz
  LEFT JOIN nivel_riesgo nri ON nri.id_nivel_riesgo = NULLIF(a.nivel_irritantes,0)
  WHERE IFNULL(a.irritantes,0)=1

  UNION ALL

  SELECT
    ptm.id_puesto_trabajo_matriz,
    ptm.puesto_trabajo_matriz AS nombre_puesto,
    NULL AS nombre_riesgo,
    NULL AS nombre_probabilidad,
    NULL AS nombre_consecuencia,
    NULL AS nombre_nivel_riesgo
  FROM puesto_trabajo_matriz ptm
  LEFT JOIN (
    SELECT
      qp.id_puesto_trabajo_matriz,
      MAX(COALESCE(q.particulas_polvo,0)) AS polvo,
      MAX(COALESCE(q.sustancias_corrosivas,0)) AS corrosivas,
      MAX(COALESCE(q.sustancias_toxicas,0)) AS toxicas,
      MAX(COALESCE(q.sustancias_irritantes,0)) AS irritantes
    FROM quimico_puesto qp
    JOIN quimico q ON q.id_quimico = qp.id_quimico
    GROUP BY qp.id_puesto_trabajo_matriz
  ) a ON a.id_puesto_trabajo_matriz = ptm.id_puesto_trabajo_matriz
  WHERE (IFNULL(a.polvo,0)+IFNULL(a.corrosivas,0)+IFNULL(a.toxicas,0)+IFNULL(a.irritantes,0) = 0
         OR a.id_puesto_trabajo_matriz IS NULL)
) t
WHERE t.id_puesto_trabajo_matriz = :ptm
ORDER BY t.nombre_puesto, t.nombre_riesgo
SQL;

        $extraRows = collect(DB::select($sqlExtra, ['ptm' => $ptmId]));

        $chemDict = [];
        $noChemLow = false;
        foreach ($extraRows as $r) {
            $riskName = (string)($r->nombre_riesgo ?? '');
            if ($riskName === '') {
                // Señal: puesto sin riesgos químicos → marcar categorías más bajas por defecto
                $noChemLow = true;
                continue;
            }
            $key = self::norm($riskName);
            $prob = strtoupper((string)($r->nombre_probabilidad ?? ''));
            $cons = strtoupper((string)($r->nombre_consecuencia ?? ''));
            $val  = strtoupper((string)($r->nombre_nivel_riesgo ?? ''));
            $chemDict[$key] = [
                'prob' => $probMap[$prob] ?? '',
                'cons' => $consMap[$cons] ?? '',
                'val'  => $valMap[$val]   ?? '',
            ];
        }

        // Catálogo global de riesgos (para defaults mínimos cuando el puesto no lo tiene)
        $catalogNames = DB::table('riesgo')->pluck('nombre_riesgo')->map(fn($n)=>(string)$n)->toArray();
        // Agregar los nombres de la consulta extendida que quizá no estén en 'riesgo'
        foreach ([
            'Partículas de polvo, humos, gases y vapores',
            'SUSTANCIAS CORROSIVAS',
            'SUSTANCIAS TÓXICAS',
            'Sustancias irritantes o alergizantes',
        ] as $extraName) { $catalogNames[] = $extraName; }
        $catalogKeys = [];
        foreach ($catalogNames as $n) { $catalogKeys[self::norm($n)] = true; }

        // 4) Abrir plantilla
        $tplPath = storage_path('app/public/formato_evaluacion_riesgos.xlsx');
        if (!is_file($tplPath)) {
            return back()->with('error', 'No se encontró la plantilla formato_evaluacion_riesgos.xlsx');
        }
        try {
            $spreadsheet = IOFactory::load($tplPath);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo abrir la plantilla de Excel: '.$e->getMessage());
        }
        $sheet = $spreadsheet->getActiveSheet();

        // 5) Escribir encabezados
        $sheet->setCellValue('C7',  $header['departamento'] ?? '');
        $sheet->setCellValue('C8',  $header['puesto'] ?? '');
        $sheet->setCellValue('C9',  (string)($header['empleados'] ?? ''));
        // C10 (fecha) dejar vacío por pedido del usuario
        $sheet->setCellValue('C10', '');

        // 6) Config de mapeo de columnas. El usuario puede ajustar aquí si su plantilla difiere
        //    Columna B es el nombre de riesgo y a partir de row 12 empiezan los riesgos (ajustable)
        $config = [
            // Según indicación: buscar solo en columna B iniciando en B13
            'col_riesgo' => 'B',
            'row_inicio' => 13,
            'row_limite' => 59, // la lista de riesgos termina en B59
            // Probabilidad columnas: D=BAJO(B), E=MEDIO(M), F=ALTO(A)
            'prob_cols'  => [ 'B' => 'D', 'M' => 'E', 'A' => 'F' ],
            // Consecuencia columnas: G=LEVE(L), H=GRAVE(G), I=MUY GRAVE(MG)
            'cons_cols'  => [ 'L' => 'G', 'G' => 'H', 'MG' => 'I' ],
            // Valoración columnas: J=IRRELEVANTE(I), K=BAJO(B), L=MEDIO(M), M=ALTO(A), N=MUY ALTO(MA)
            'val_cols'   => [ 'I' => 'J', 'B' => 'K', 'M' => 'L', 'A' => 'M', 'MA' => 'N' ],
            'mark'       => 'X',
        ];

        // 7) Recorrer filas de riesgos en la plantilla (columna B)
        //    Combinar info de consulta principal + extendida; si no hay datos para el puesto
        //    pero el riesgo existe en BD, marcar mínimos (B, L, I). Si ni siquiera existe en BD, no marcar.
        $colRisk = $config['col_riesgo'];
        for ($r = $config['row_inicio']; $r <= $config['row_limite']; $r++) {
            $name = trim((string) $sheet->getCell($colRisk.$r)->getCalculatedValue());
            if ($name === '') { continue; }

            $key = self::norm($name);
            $src = null;

            if (isset($riskDict[$key])) {
                $src = $riskDict[$key];
            } else if (($mk = self::findClosestKey($key, array_keys($riskDict))) !== null) {
                $src = $riskDict[$mk];
            } else if (isset($chemDict[$key])) {
                $src = $chemDict[$key];
            } else if (($mk2 = self::findClosestKey($key, array_keys($chemDict))) !== null) {
                $src = $chemDict[$mk2];
            }
            // Si no hay dato para el puesto: marcar mínimos solo si el riesgo existe en catálogo (BD)
            if ($src === null) {
                $exists = isset($catalogKeys[$key]) || self::findClosestKey($key, array_keys($catalogKeys)) !== null;
                if (!$exists) { continue; }
                $src = ['prob' => 'B', 'cons' => 'L', 'val' => 'I'];
            }

            $prob = $src['prob'] ?? null;
            $cons = $src['cons'] ?? null;
            $val  = $src['val']  ?? null;

            if ($prob !== null && isset($config['prob_cols'][$prob])) {
                $sheet->setCellValue($config['prob_cols'][$prob].$r, $config['mark']);
            }
            if ($cons !== null && isset($config['cons_cols'][$cons])) {
                $sheet->setCellValue($config['cons_cols'][$cons].$r, $config['mark']);
            }
            if ($val !== null && isset($config['val_cols'][$val])) {
                $sheet->setCellValue($config['val_cols'][$val].$r, $config['mark']);
            }
        }

        // 8) Stream del archivo al navegador
        $filename = 'evaluacion_riesgos_'.$ptmId.'_'.date('Ymd_His').'.xlsx';

        try {
            // Guardar en storage/app con extensión .xlsx para evitar rarezas de algunos SO
            $tmp = storage_path('app/'.uniqid('evaluacion_', true).'.xlsx');
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            // $writer->setPreCalculateFormulas(false); // opcional
            $writer->save($tmp);

            // Validación básica: tamaño > 0 y hoja válida
            if (!is_file($tmp) || filesize($tmp) < 1000) {
                // Reintentar: a veces la primera escritura se queda corta por buffers
                $writer->save($tmp);
            }
            if (!is_file($tmp) || filesize($tmp) < 1000) {
                return back()->with('error', 'El archivo generado parece vacío. Revisa la plantilla y vuelve a intentar.');
            }

            // Limpia buffers para evitar bytes extraños antes del binario
            while (ob_get_level() > 0) { @ob_end_clean(); }

            // Liberar memoria de Spreadsheet
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo generar el archivo de Excel: '.$e->getMessage());
        }

        return response()->download($tmp, $filename, [
            'Content-Type'              => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'             => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'                    => 'no-cache',
            'Content-Transfer-Encoding' => 'binary',
        ])->deleteFileAfterSend(true);
    }

    private static function pick(array $row, array $keys, $default=null)
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') return $row[$k];
        }
        return $default;
    }

    private static function norm(string $s): string
    {
        // Normaliza para comparaciones insensibles a mayúsculas, acentos y espacios
        $s = trim(mb_strtoupper($s, 'UTF-8'));
        // Quitar acentos y diacríticos
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        // Mantener solo letras y números; convertir separadores a espacio
        $s = preg_replace('/[^A-Z0-9 ]+/', ' ', $s);
        // Colapsar espacios y luego ELIMINARLOS para ignorarlos en matching
        $s = preg_replace('/\s+/', ' ', $s);
        $s = str_replace(' ', '', $s);
        return $s;
    }

    private static function findClosestKey(string $needle, array $keys): ?string
    {
        // heurística simple: igualdad por inclusión
        foreach ($keys as $k) {
            if (str_contains($k, $needle) || str_contains($needle, $k)) {
                return $k;
            }
        }
        return null;
    }
}
