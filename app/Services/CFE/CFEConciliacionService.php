<?php

namespace App\Services\CFE;

use App\Filters\CFE\ConciliacionReadFilter;
use App\Models\CFE\Recibo;
use App\Models\CFE\Periodo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CFEConciliacionService
{
    /**
     * Ajusta el tamaño del lote para Upsert (500-1500 suele ir bien).
     */
    private int $batchSize = 500;

    public function conciliarPeriodoVigente(Periodo $periodo): array
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new ConciliacionReadFilter());

        // 1) Periodo vigente
        $anio = (int) $periodo->ano;
        $mes  = str_pad((string) $periodo->mes, 2, '0', STR_PAD_LEFT);

        // 2) Directorio de archivos planos (PUBLIC)
        $dir = "cfe/{$anio}/{$mes}/expediente/archivos_planos";
        $files = collect(Storage::disk('public')->files($dir))
            ->filter(fn ($p) => str_ends_with(strtolower($p), '.xlsx'))
            ->values()
            ->all();

        // 3) Recibos del periodo vigente
        $recibos = Recibo::query()
            ->where('periodo_id', $periodo->id)
            ->get();

        // Reset flags
        Recibo::query()
            ->where('periodo_id', $periodo->id)
            ->update([
                'rpu_ok' => false,
                'periodo_ok' => false,
                'total_ok' => false,
                'consumo_ok' => false,
                'desde_ok' => false,
                'hasta_ok' => false,
                'validado' => false,
                'conciliado_at' => null,
            ]);

        $byRpu = $recibos->groupBy(fn ($r) => (string) $r->rpu);

        $global = [
            'periodo' => ['id' => $periodo->id, 'ano' => $anio, 'mes' => (int) $mes],
            'folder' => $dir,
            'files_count' => count($files),
            'rows_read' => 0,
            'rows_matched' => 0,
            'rows_not_found' => 0,
            'rows_validated' => 0,
            'rows_mismatch' => 0,
            'rows_duplicates' => 0,
            'db_items' => $recibos->count(),
            'db_duplicates_rpu' => $byRpu->filter(fn ($g) => $g->count() > 1)->map->count()->all(),
        ];

        $perFile = [];
        $seenHashes = [];      // hash => ['file'=>..., 'row'=>...]
        $pendingUpserts = [];  // acumulador global de upserts

        foreach ($files as $relativePath) {
            $absPath = Storage::disk('public')->path($relativePath);

            $fileReport = [
                'file' => basename($relativePath),
                'path' => $relativePath,
                'rows_read' => 0,
                'matched' => 0,
                'not_found' => 0,
                'validated' => 0,
                'mismatch' => 0,
                'duplicates' => 0,
                'details' => [],
            ];

            $spreadsheet = null;

            try {
                $spreadsheet = $reader->load($absPath);
                $sheet = $spreadsheet->getSheet(0);

                for ($row = 20; $row < 20000; $row++) {

                    $rpu      = $this->cellString($sheet, "A{$row}");
                    $periodoX = $this->cellString($sheet, "B{$row}");

                    // ✅ REGLA: Total desde columna I
                    $totalX   = $this->cellFloatNullableFast($sheet, "L{$row}");
                    $consX    = $this->cellFloatNullableFast($sheet, "CC{$row}");

                    $desdeX   = $this->cellDate($sheet, "CG{$row}");
                    $hastaX   = $this->cellDate($sheet, "CH{$row}");

                    if ($this->stopRow($rpu, $periodoX, $totalX, $consX, $desdeX, $hastaX)) {
                        break;
                    }

                    $hash = $this->rowHash(
                        $rpu,
                        $periodoX,
                        (float) ($totalX ?? 0),
                        (float) ($consX ?? 0),
                        $desdeX,
                        $hastaX
                    );

                    $isDuplicateRow = false;
                    $dupRef = null;

                    if (isset($seenHashes[$hash])) {
                        $isDuplicateRow = true;
                        $dupRef = $seenHashes[$hash];

                        $fileReport['duplicates']++;
                        $global['rows_duplicates']++;
                        // ✅ OJO: NO hacemos continue; se valida igual
                    } else {
                        $seenHashes[$hash] = ['file' => basename($relativePath), 'row' => $row];
                    }

                    if ($rpu === '') {
                        if ($isDuplicateRow) {
                            $fileReport['details'][] = [
                                'row' => $row,
                                'status' => 'duplicate_row_no_rpu',
                                'hash' => $hash,
                                'msg' => 'Fila duplicada (hash repetido) pero sin RPU (se ignora).',
                                'first_seen' => $dupRef,
                            ];
                        }
                        continue;
                    }

                    $fileReport['rows_read']++;
                    $global['rows_read']++;

                    $grupo = $byRpu->get($rpu);

                    if (!$grupo || $grupo->isEmpty()) {
                        $fileReport['not_found']++;
                        $global['rows_not_found']++;

                        $fileReport['details'][] = [
                            'row' => $row,
                            'status' => $isDuplicateRow ? 'duplicate_row_not_found' : 'not_found',
                            'hash' => $hash,
                            'rpu' => $rpu,
                            'periodo_xlsx' => $periodoX,
                            'total_xlsx' => (float) ($totalX ?? 0),
                            'consumo_xlsx' => (float) ($consX ?? 0),
                            'desde_xlsx' => $desdeX,
                            'hasta_xlsx' => $hastaX,
                            'msg' => $isDuplicateRow
                                ? 'Fila duplicada (hash repetido) y además RPU no existe en recibos del periodo.'
                                : 'No existe en recibos del periodo vigente (se continúa).',
                            'first_seen' => $dupRef,
                        ];
                        continue;
                    }

                    $recibo = $this->pickBestRecibo($grupo->all(), $periodoX, $desdeX, $hastaX) ?? $grupo->first();

                    $fileReport['matched']++;
                    $global['rows_matched']++;

                    $rpuOk   = ((string) $recibo->rpu === (string) $rpu);
                    $perOk   = ($this->norm($recibo->periodo) === $this->norm($periodoX));
                    $totalOk = $this->floatEquals((float) $recibo->total, (float) ($totalX ?? 0), 0.01);
                    $consOk  = $this->floatEquals((float) $recibo->consumo, (float) ($consX ?? 0), 0.01);
                    $desdeOk = $this->dateEquals($recibo->desde, $desdeX);
                    $hastaOk = $this->dateEquals($recibo->hasta, $hastaX);

                    $validado = $rpuOk && $perOk && $totalOk && $consOk && $desdeOk && $hastaOk;

                    // ✅ BATCH UPDATE (upsert por id)
                    $now = now();
                    $pendingUpserts[] = [
                        'id' => $recibo->id,
                        'rpu_ok' => $rpuOk,
                        'periodo_ok' => $perOk,
                        'total_ok' => $totalOk,
                        'consumo_ok' => $consOk,
                        'desde_ok' => $desdeOk,
                        'hasta_ok' => $hastaOk,
                        'validado' => $validado,
                        'conciliado_at' => $now,
                        'updated_at' => $now, // por si usas timestamps
                    ];

                    if ($validado) {
                        $fileReport['validated']++;
                        $global['rows_validated']++;
                    } else {
                        $fileReport['mismatch']++;
                        $global['rows_mismatch']++;
                    }

                    // Details (duplicado o mismatch)
                    if ($isDuplicateRow || !$validado) {
                        $fileReport['details'][] = [
                            'row' => $row,
                            'status' => $isDuplicateRow
                                ? ($validado ? 'duplicate_row_validated' : 'duplicate_row_mismatch')
                                : 'mismatch',
                            'hash' => $hash,
                            'first_seen' => $dupRef,
                            'recibo_id' => $recibo->id,
                            'rpu' => $rpu,
                            'checks' => [
                                'rpu_ok' => $rpuOk,
                                'periodo_ok' => $perOk,
                                'total_ok' => $totalOk,
                                'consumo_ok' => $consOk,
                                'desde_ok' => $desdeOk,
                                'hasta_ok' => $hastaOk,
                                'validado' => $validado,
                            ],
                            'xlsx' => [
                                'periodo' => $periodoX,
                                'total' => (float) ($totalX ?? 0),
                                'consumo' => (float) ($consX ?? 0),
                                'desde' => $desdeX,
                                'hasta' => $hastaX,
                            ],
                            'db' => [
                                'periodo' => $recibo->periodo,
                                'total' => (float) $recibo->total,
                                'consumo' => (float) $recibo->consumo,
                                'desde' => $recibo->desde ? Carbon::parse($recibo->desde)->format('Y-m-d') : null,
                                'hasta' => $recibo->hasta ? Carbon::parse($recibo->hasta)->format('Y-m-d') : null,
                            ],
                        ];
                    }

                    // ✅ Flushear cada N filas para no acumular memoria y acelerar
                    if (count($pendingUpserts) >= $this->batchSize) {
                        $this->flushUpserts($pendingUpserts);
                        $pendingUpserts = [];
                    }
                }

            } finally {
                // flush final del archivo
                if (!empty($pendingUpserts)) {
                    $this->flushUpserts($pendingUpserts);
                    $pendingUpserts = [];
                }

                if ($spreadsheet) {
                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet);
                }
                gc_collect_cycles();
            }

            $perFile[] = $fileReport;
        }

        return [
            'ok' => true,
            'global' => $global,
            'files' => $perFile,
        ];
    }

    /**
     * ✅ Ejecuta upsert masivo (súper rápido vs update() por fila).
     */
    private function flushUpserts(array $rows): void
    {
        if (empty($rows)) return;

        Recibo::query()->upsert(
            $rows,
            ['id'], // uniqueBy
            ['rpu_ok','periodo_ok','total_ok','consumo_ok','desde_ok','hasta_ok','validado','conciliado_at','updated_at']
        );
    }

    private function stopRow(string $rpu, string $periodo, ?float $total, ?float $consumo, ?string $desde, ?string $hasta): bool
    {
        return ($rpu === '')
            && ($periodo === '')
            && ($total === null)
            && ($consumo === null)
            && ($desde === null)
            && ($hasta === null);
    }

    private function norm(?string $s): string
    {
        $s = trim((string) $s);
        $s = mb_strtoupper($s, 'UTF-8');
        $s = preg_replace('/\s+/', ' ', $s);
        return $s ?: '';
    }

    private function floatEquals(float $a, float $b, float $tol = 0.01): bool
    {
        return abs($a - $b) <= $tol;
    }

    private function dateEquals($dbDate, ?string $xlsxYmd): bool
    {
        if (!$dbDate && !$xlsxYmd) return true;
        if (!$dbDate || !$xlsxYmd) return false;

        try {
            $db = Carbon::parse($dbDate)->format('Y-m-d');
            return $db === $xlsxYmd;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function pickBestRecibo(array $recibos, string $periodoXlsx, ?string $desdeXlsx, ?string $hastaXlsx): ?Recibo
    {
        $periodoXlsxN = $this->norm($periodoXlsx);

        foreach ($recibos as $r) {
            if ($this->norm($r->periodo) === $periodoXlsxN) return $r;
        }

        foreach ($recibos as $r) {
            $dOk = $this->dateEquals($r->desde, $desdeXlsx);
            $hOk = $this->dateEquals($r->hasta, $hastaXlsx);
            if ($dOk && $hOk) return $r;
        }

        return null;
    }

    private function cellString($sheet, string $addr): string
    {
        // getFormattedValue para textos “bonitos”
        $v = $sheet->getCell($addr)->getFormattedValue();
        return trim((string) $v);
    }

    /**
     * ✅ Float “rápido”:
     * - si la celda es fórmula, usa calculated
     * - si no, usa value directo
     * - vacío => null (para stopRow correcto)
     */
    private function cellFloatNullableFast($sheet, string $addr): ?float
    {
        $cell = $sheet->getCell($addr);
        $v = $cell->getValue();

        if ($v === null || $v === '') return null;

        // fórmula
        if (is_string($v) && str_starts_with($v, '=')) {
            $v = $cell->getCalculatedValue();
        }

        if ($v === null || $v === '') return null;

        if (is_string($v)) {
            $v = str_replace([',', '$'], '', $v);
            $v = trim($v);
            if ($v === '') return null;
        }

        return (float) $v;
    }

    private function cellDate($sheet, string $addr): ?string
    {
        $cell = $sheet->getCell($addr);
        $v = $cell->getValue();

        if ($v === null || $v === '') return null;

        // serial excel
        if (is_numeric($v)) {
            try {
                return ExcelDate::excelToDateTimeObject($v)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $s = trim((string) $cell->getFormattedValue());
        if ($s === '') return null;

        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd M y', 'd M Y'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, mb_strtoupper($s, 'UTF-8'));
            if ($dt) return $dt->format('Y-m-d');
        }

        try {
            return Carbon::parse($s)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function rowHash(string $rpu, string $periodo, float $total, float $consumo, ?string $desde, ?string $hasta): string
    {
        $rpu = trim((string) $rpu);
        $periodo = $this->norm($periodo);

        $total = number_format((float) $total, 2, '.', '');
        $consumo = number_format((float) $consumo, 2, '.', '');

        $desde = $desde ? trim($desde) : '';
        $hasta = $hasta ? trim($hasta) : '';

        return sha1($rpu . '|' . $periodo . '|' . $total . '|' . $consumo . '|' . $desde . '|' . $hasta);
    }
}
