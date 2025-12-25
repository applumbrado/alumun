<?php

namespace App\Http\Controllers\CFE;

use App\Http\Controllers\Controller;
use App\Models\Catalogos\Grupo;
use App\Models\CFE\ArchivoPlano;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class ArchivosPlanosController extends Controller
{
    public function index()
    {
        $periodo = periodo_vigente();
        if (! $periodo) {
            return Inertia::render('CFE/ArchivosPlanos/Index', [
                'archivosPlanos' => [],
                'periodoActivo' => null,
            ]);
        }

        $archivos = ArchivoPlano::query()
            ->where('periodo_id', $periodo->id)
            ->orderBy('grupo_id')
            ->orderBy('consecutivo')
            ->get()
            ->map(fn($a) => [
                'id'            => $a->id,
                'grupo_codigo'  => $a->grupo->grupo,
                'consecutivo'   => $a->consecutivo,
                'original_name' => $a->original_name,
                'stored_name'   => $a->stored_name,
                'size'          => $a->size,
                'mime'          => $a->mime,
                'url'           => $a->url,
                'created_at'    => optional($a->created_at)->toDateTimeString(),
            ]);

        return Inertia::render('CFE/ArchivosPlanos/Index', [
            'archivosPlanos' => $archivos,
            'periodoActivo'  => [
                'id'  => $periodo->id,
                'ano' => (int) $periodo->ano,
                'mes' => (int) $periodo->mes,
            ]
        ]);
    }

    public function upload(Request $request){

        foreach ($request->file('archivos', []) as $f) {
            logger()->info('UPLOAD', [
                'name' => $f->getClientOriginalName(),
                'ext'  => $f->getClientOriginalExtension(),
                'mime' => $f->getMimeType(),
            ]);
        }

        $request->validate([
            'archivos'   => ['required', 'array', 'min:1', 'max:3'],
            'archivos.*' => [
                'required',
                'file',
                'max:51200', // 50MB (ajusta)
                // ✅ MIMEs comunes para xlsx (muchas veces llega como zip/octet-stream)
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/octet-stream',
            ],
        ], [
            'archivos.*.mimetypes' => 'El archivo debe ser .xlsx (Excel).',
        ]);

// ✅ Segunda capa: asegurar extensión .xlsx sí o sí
        foreach ($request->file('archivos', []) as $i => $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext !== 'xlsx') {
                throw ValidationException::withMessages([
                    "archivos.$i" => "El archivo debe tener extensión .xlsx (recibí .$ext).",
                ]);
            }
        }

        $periodo = periodo_vigente();
        if (! $periodo) {
            return response()->json(['message' => 'No hay periodo vigente definido.'], 422);
        }

        $anio = (int) $periodo->ano;
        $mes  = str_pad((string)$periodo->mes, 2, '0', STR_PAD_LEFT);
        $yyyymmActivo = $anio . $mes;

        $auto = (bool) $request->boolean('auto_consecutivo', true);

        // storage/app/public/cfe/{anio}/{mes}/expediente/archivos_planos/
        $folder = "cfe/{$anio}/{$mes}/expediente/archivos_planos";
        Storage::disk('public')->makeDirectory($folder);

        $procesados = [];

        foreach ($request->file('archivos') as $file) {

            $original = $file->getClientOriginalName();

            // AAAAMM_GRUPO_N.xlsx
            if (!preg_match('/^(\d{6})_([A-Za-z0-9]+)_(\d+)\.xlsx$/i', $original, $m)) {
                $procesados[] = [
                    'status'   => 'error',
                    'original' => $original,
                    'saved_as' => null,
                    'message'  => 'Formato inválido. Usa: AAAAMM_GRUPO_CONSECUTIVO.xlsx',
                ];
                continue;
            }

            $yyyymm = $m[1];
            $gpo  = $m[2];
            $grupo  = Grupo::where('grupo', $gpo)->firstOrFail();
            $seq    = (int) $m[3];

            if ($yyyymm !== $yyyymmActivo) {
                $procesados[] = [
                    'status'   => 'error',
                    'original' => $original,
                    'saved_as' => null,
                    'message'  => "Periodo no coincide con activo ({$yyyymmActivo}).",
                ];
                continue;
            }

            if ($seq < 1) {
                $procesados[] = [
                    'status'   => 'error',
                    'original' => $original,
                    'saved_as' => null,
                    'message'  => 'Consecutivo inválido (debe iniciar en 1).',
                ];
                continue;
            }

            $userId = auth()->id();

            // --- Resolver destino con BD + Storage (sin pisar si existe archivo+registro) ---
            $result = DB::transaction(function () use (
                $periodo, $grupo, $seq, $yyyymm, $folder, $file, $original, $auto, $userId
            ) {
                $disk = 'public';

                $buildName = fn(int $n) => "{$yyyymm}_{$grupo->grupo}_{$n}.xlsx";
                $buildPath = fn(string $name) => "{$folder}/{$name}";

                $trySeq = $seq;

                // función para decidir si "ocupado"
                $isOccupied = function (int $n) use ($periodo, $grupo, $disk, $buildPath, $buildName) {
                    $existsDb = ArchivoPlano::query()
                        ->where('periodo_id', $periodo->id)
                        ->where('grupo_id', $grupo->id)
                        ->where('consecutivo', $n)
                        ->exists();

                    $existsFile = Storage::disk($disk)->exists($buildPath($buildName($n)));

                    return [$existsDb, $existsFile];
                };

                // Caso base: checar ocupación
                [$existsDb, $existsFile] = $isOccupied($trySeq);

                // Si existe DB pero NO existe archivo → REPARAR archivo (se permite mismo consecutivo)
                if ($existsDb && !$existsFile) {
                    $stored = $buildName($trySeq);
                    $path   = $buildPath($stored);

                    $file->storeAs($folder, $stored, $disk);

                    $row = ArchivoPlano::query()
                        ->where('periodo_id', $periodo->id)
                        ->where('grupo_id', $grupo->id)
                        ->where('consecutivo', $trySeq)
                        ->first();

                    $row->update([
                        'original_name' => $original,
                        'stored_name'   => $stored,
                        'disk'          => $disk,
                        'path'          => $path,
                        'size'          => $file->getSize() ?? 0,
                        'mime'          => $file->getMimeType(),
                        'user_id'       => $userId,
                    ]);

                    return ['status' => 'repaired_file', 'saved_as' => $stored, 'message' => 'Registro existía pero faltaba archivo: reparado'];
                }

                // Si existe archivo pero NO DB → REPARAR BD (crear registro)
                if (!$existsDb && $existsFile) {
                    $stored = $buildName($trySeq);
                    $path   = $buildPath($stored);

                    ArchivoPlano::create([
                        'periodo_id'     => $periodo->id,
                        'grupo_id'       => $grupo->id,
                        'consecutivo'    => $trySeq,
                        'original_name'  => $original,
                        'stored_name'    => $stored,
                        'disk'           => $disk,
                        'path'           => $path,
                        'size'           => 0,
                        'mime'           => null,
                        'user_id'        => $userId,
                    ]);

                    // ahora sí sobre-escribimos el archivo con el que suben? NO.
                    // Tu regla: si existe archivo y existe item, NO sobreescribir.
                    // Aquí existe archivo pero NO item, podemos guardar el archivo? ya está.
                    // Mejor lo guardamos como siguiente disponible si auto, o error si no auto.
                    if (!$auto) {
                        return ['status' => 'error', 'saved_as' => $stored, 'message' => 'Existía el archivo sin registro (BD reparada). Reintenta con otro consecutivo.'];
                    }

                    // auto → buscar siguiente
                    $existsDb = true;
                    $existsFile = true;
                }

                // Si existe DB y existe archivo → NO sobreescribir
                if ($existsDb && $existsFile) {
                    if (!$auto) {
                        return ['status' => 'error', 'saved_as' => null, 'message' => 'Ya existe registro y archivo con ese consecutivo.'];
                    }

                    // auto → buscar siguiente libre (db y file)
                    $maxDb = (int) (ArchivoPlano::query()
                        ->where('periodo_id', $periodo->id)
                        ->where('grupo_id', $grupo->id)
                        ->max('consecutivo') ?? 0);

                    $trySeq = max($trySeq, $maxDb + 1);

                    // while por seguridad (por archivos sueltos)
                    while (true) {
                        [$eDb, $eFile] = $isOccupied($trySeq);
                        if (!$eDb && !$eFile) break;
                        $trySeq++;
                    }
                }

                // Si NO existe DB y NO existe archivo → se puede usar el seq original
                // Guardar
                $stored = $buildName($trySeq);
                $path   = $buildPath($stored);

                $file->storeAs($folder, $stored, $disk);

                // Crear/actualizar registro
                $row = ArchivoPlano::query()
                    ->where('periodo_id', $periodo->id)
                    ->where('grupo_id', $grupo->id)
                    ->where('consecutivo', $trySeq)
                    ->first();

                if ($row) {
                    // esto solo ocurriría por condiciones raras de carrera
                    $row->update([
                        'original_name' => $original,
                        'stored_name'   => $stored,
                        'disk'          => $disk,
                        'path'          => $path,
                        'size'          => $file->getSize() ?? 0,
                        'mime'          => $file->getMimeType(),
                        'user_id'       => $userId,
                    ]);

                    return ['status' => 'updated', 'saved_as' => $stored, 'message' => 'Actualizado (condición especial)'];
                }

                ArchivoPlano::create([
                    'periodo_id'     => $periodo->id,
                    'grupo_id'       => $grupo->id,
                    'consecutivo'    => $trySeq,
                    'original_name'  => $original,
                    'stored_name'    => $stored,
                    'disk'           => $disk,
                    'path'           => $path,
                    'size'           => $file->getSize() ?? 0,
                    'mime'           => $file->getMimeType(),
                    'user_id'        => $userId,
                ]);

                return [
                    'status'  => ($trySeq === $seq) ? 'ok' : 'ok_autoseq',
                    'saved_as'=> $stored,
                    'message' => ($trySeq === $seq) ? 'Guardado' : "Guardado con consecutivo {$trySeq}"
                ];
            });

            if (($result['status'] ?? '') === 'error') {
                $procesados[] = [
                    'status'   => 'error',
                    'original' => $original,
                    'saved_as' => $result['saved_as'] ?? null,
                    'message'  => $result['message'] ?? 'Error',
                ];
            } else {
                $procesados[] = [
                    'status'   => $result['status'],
                    'original' => $original,
                    'saved_as' => $result['saved_as'] ?? null,
                    'message'  => $result['message'] ?? 'OK',
                ];
            }
        }

        return response()->json([
            'success'   => true,
            'procesados'=> $procesados,
        ]);
    }

    public function destroy(Request $request, ArchivoPlano $archivoPlano){

        // ✅ permiso (ajusta si usas policies o gates)
//        abort_unless(auth()->user()?->can(' subir archivos planos | all ') || auth()->user()?->can(' conciliar recibos | all '), 403);

        try {
            $disk = $archivoPlano->disk ?: 'public';
            $path = $archivoPlano->path;

            $fileExists = false;
            $fileDeleted = null;

            if ($path) {
                $fileExists = Storage::disk($disk)->exists($path);

                // ✅ si existe, lo borramos
                if ($fileExists) {
                    $fileDeleted = Storage::disk($disk)->delete($path);
                } else {
                    // existe en DB pero no en disco
                    $fileDeleted = false;
                }
            }

            // ✅ Soft delete en DB
            $archivoPlano->delete();
            $archivoPlano->forceDelete();

            return response()->json([
                'ok' => true,
                'id' => $archivoPlano->id,
                'disk' => $disk,
                'path' => $path,
                'file_exists' => $fileExists,
                'file_deleted' => $fileDeleted, // true / false / null
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar archivo plano.',
            ], 500);
        }
    }



}
