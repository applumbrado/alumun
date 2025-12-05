<?php

namespace App\Services\CFE;

use App\Models\Catalogos\Servicio;
use App\Models\CFE\Periodo;
use App\Models\CFE\Recibo;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use ZipArchive;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CFEImportService{



//    public function procesarZip($zipFile, $forceOverwrite = false){
//
//        $nombreArchivo = $zipFile->getClientOriginalName();
//
//        $periodo = periodo_vigente();
//        if (! $periodo) {
//            throw new \RuntimeException('No hay periodo vigente definido.');
//        }
//
//        $anio = $periodo->ano; // OJO: en tu modelo el campo es "ano", no "anio"
//        $mes  = str_pad($periodo->mes, 2, '0', STR_PAD_LEFT); // 01, 02, etc.
//
//        // Carpeta pública destino: storage/app/public/cfe/{anio}/{mes}
//        $destinoBase = "cfe/{$anio}/{$mes}";
//
//        // Nos aseguramos de que exista en el disco public
//        Storage::disk('public')->makeDirectory($destinoBase);
//
//        $folder = 'import_' . time() . '_' . uniqid();
//        $pathTemporal = storage_path("app/private/tmp/$folder");
//
//        if (!mkdir($pathTemporal, 0755, true) && !is_dir($pathTemporal)) {
//            throw new \RuntimeException("No se pudo crear carpeta temporal: $pathTemporal");
//        }
//
//        // Guardar ZIP temporal
//        $zipPath = $pathTemporal . '/archivo.zip';
//        $zipFile->move($pathTemporal, "archivo.zip");
//
//        // Extraer ZIP
//        $zip = new ZipArchive();
//        if ($zip->open($zipPath) === TRUE) {
//            $zip->extractTo($pathTemporal);
//            $zip->close();
//        } else {
//            throw new \Exception("No se pudo abrir el ZIP: $nombreArchivo");
//        }
//
//        $resultados = [];
//
//        foreach (glob("$pathTemporal/*.xml") as $archivoXML) {
//
//            $base = basename($archivoXML); // ej: 1234567890.xml
//            $nombreSinExt = pathinfo($base, PATHINFO_FILENAME); // ej: 1234567890
//
//            // ==============================
//            //  A) Copiar XML a carpeta pública
//            // ==============================
//            try {
//                // Guardar XML en storage/app/public/cfe/{anio}/{mes}/{archivo}.xml
//                Storage::disk('public')->put(
//                    "{$destinoBase}/{$base}",
//                    file_get_contents($archivoXML)
//                );
//
//                // ==============================
//                //  B) Si existe el PDF "hermano", también copiarlo
//                // ==============================
//                $archivoPDF = $pathTemporal . '/' . $nombreSinExt . '.pdf';
//
//                if (is_file($archivoPDF)) {
//                    $pdfBase = basename($archivoPDF); // ej: 1234567890.pdf
//
//                    Storage::disk('public')->put(
//                        "{$destinoBase}/{$pdfBase}",
//                        file_get_contents($archivoPDF)
//                    );
//                }
//            } catch (\Throwable $e) {
//                // Si falla el copiado, puedes registrarlo y seguir
//                $resultados[] = [
//                    'status'  => 'error',
//                    'msg'     => 'Error al copiar XML/PDF a carpeta pública: ' . $e->getMessage(),
//                    'file'    => $base,
//                    'archivo' => $base,
//                    'success' => false,
//                    'error'   => $e->getMessage(),
//                ];
//                // seguimos al siguiente archivo
//                continue;
//            }
//
//
//            try {
//                $xmlContent = file_get_contents($archivoXML);
//                if (!$xmlContent) {
//                    $resultados[] = [
//                        'status'  => 'error',
//                        'msg'     => 'No se pudo leer XML',
//                        'file'    => $base,
//
//                        // formato compatible nuevo
//                        'archivo' => $base,
//                        'success' => false,
//                        'error'   => 'No se pudo leer XML'
//                    ];
//                    continue;
//                }
//
//                // Cargar XML sin warnings
//                $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
//                if ($xml === false) {
//                    $resultados[] = [
//                        'status'  => 'error',
//                        'msg'     => 'XML inválido',
//                        'file'    => $base,
//
//                        'archivo' => $base,
//                        'success' => false,
//                        'error'   => 'XML inválido',
//                    ];
//                    continue;
//                }
//
//                // Namespaces
//                $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
//                $xml->registerXPathNamespace('cfe',  'http://www.itcomplements.com/cfd/cfe/v1');
//
//                // ============================
//                //   1) Nodo RPU
//                // ============================
//                $nodosRPU = $xml->xpath('//cfe:CFE/cfe:ComisionFederalElectricidad');
//                if (!$nodosRPU || !isset($nodosRPU[0])) {
//                    $msg = 'No se encontró nodo CFE/ComisionFederalElectricidad';
//
//                    $resultados[] = [
//                        'status'  => 'error',
//                        'msg'     => $msg,
//                        'file'    => $base,
//
//                        'archivo' => $base,
//                        'success' => false,
//                        'error'   => $msg,
//                    ];
//
//                    continue;
//                }
//
//                $comision = $nodosRPU[0];
//                $rpu = trim( (string)$comision['RPU'] );
//
//                $isRPU = Servicio::query()->where('rpu', $rpu)->exists();
//
//                // ============================
//                //   2) clsRegArchFact
//                // ============================
//                $nodosCls = $xml->xpath('//clsRegArchFact');
//                if ((!$nodosCls || !isset($nodosCls[0]))) {
//                    $msg = 'No se encontró nodo clsRegArchFact';
//
//                    $resultados[] = [
//                        'status'  => 'error',
//                        'msg'     => $msg,
//                        'file'    => $base,
//                        'rpu'     => $rpu,
//
//                        'archivo' => $base,
//                        'success' => false,
//                        'error'   => $msg,
//                    ];
//                    continue;
//                }
//
//                if (!$isRPU) {
//                    $msg = 'No existe en el catalogo de Servicios';
//
//                    $resultados[] = [
//                        'status'  => 'error',
//                        'msg'     => $msg,
//                        'file'    => $base,
//                        'rpu'     => $rpu,
//
//                        'archivo' => $base,
//                        'success' => false,
//                        'error'   => $msg,
//                    ];
//                    continue;
//                }
//
//                $cls = $nodosCls[0];
//
//                // ============================
//                //   Campos OCR (atributos o nodos)
//                // ============================
//                $OCR_AAMM     = (string)($cls['OCR_AAMM']     ?? ($cls->OCR_AAMM     ?? ''));
//                $OCR_AAAA     = (string)($cls['OCR_AAAA']     ?? ($cls->OCR_AAAA     ?? ''));
//                $OCR_MM       = (string)($cls['OCR_MM']       ?? ($cls->OCR_MM       ?? ''));
//                $OCR_MM_NOM   = (string)($cls['OCR_MM_NOM']   ?? ($cls->OCR_MM_NOM   ?? ''));
//                $OCR_TIPO     = (string)($cls['OCR_TIPO']     ?? ($cls->OCR_TIPO     ?? ''));
//                $OCR_DIGITO   = (string)($cls['OCR_DIGITO']   ?? ($cls->OCR_DIGITO   ?? ''));
//
//                // Normalizar espacios
//                $OCR_AAMM   = trim($OCR_AAMM);
//                $OCR_AAAA   = trim($OCR_AAAA);
//                $OCR_MM     = trim($OCR_MM);
//                $OCR_MM_NOM = trim($OCR_MM_NOM);
//                $OCR_TIPO   = trim($OCR_TIPO);
//                $OCR_DIGITO = trim($OCR_DIGITO);
//
//
//                // Datos
//                $medidor  = (string)($cls->NUMMED1 ?? '');
//                $cuenta   = (string)($cls->NumCta ?? '');
//                $tarifa   = (string)($cls->TARIFA ?? '');
//                $periodo  = trim((string)($cls->FECDESDE ?? '').' - '.(string)($cls->FECHASTA ?? ''));
//
//                $direccion = trim(
//                    (string)($cls->DIRECCR ?? '') . ', ' .
//                    (string)($cls->NOMPOBR ?? '') . ', ' .
//                    (string)($cls->NOMESTR ?? '')
//                );
//
//                $subtotal = (float)($cls->SubTotal ?? 0);
//                $iva      = (float)($cls->IMPIVA ?? 0);
//                $total    = (float)($cls->CargosCreditos ?? 0);
//
//                // ============================
//                //   4) Evitar duplicados
//                // ============================
//                $existente = Recibo::where('rpu', $rpu)
//                    ->where('periodo', $periodo)
//                    ->where('cuenta', $cuenta)
//                    ->first();
//
//                $servicio_id = Servicio::query()->where('rpu', $rpu)->first()->id ?? null;
//                $periodo_id = Periodo::query()
//                    ->where('ano', $OCR_AAAA)
//                    ->where('mes', $OCR_MM)
//                    ->where('tipo', $OCR_TIPO)
//                    ->first()->id ?? null;
//
//                if ($periodo_id === null) {
//                    $per = Periodo::create([
//                        'anomes'     => $OCR_AAMM,
//                        'ano'        => $OCR_AAAA,
//                        'mes'        => $OCR_MM,
//                        'mes_nombre' => $OCR_MM_NOM,
//                        'tipo'       => $OCR_TIPO,
//                        'digito'     => $OCR_DIGITO,
//                    ]);
//                    $periodo_id = $per->id;
//                }
//
//                if ($existente) {
//
//                    if ($forceOverwrite) {
//
//
//                        $existente->update([
//                            'medidor'   => $medidor,
//                            'tarifa'    => $tarifa,
//                            'direccion' => $direccion,
//                            'subtotal'  => $subtotal,
//                            'iva'       => $iva,
//                            'total'     => $total,
//                            'servicio_id' => $servicio_id,
//                            'periodo_id'  => $periodo_id,
//                        ]);
//
//                        $resultados[] = [
//                            'status'  => 'updated',
//                            'msg'     => 'Actualizado',
//                            'file'    => $base,
//                            'rpu'     => $rpu,
//                            'periodo' => $periodo,
//
//                            'archivo' => $base,
//                            'success' => true,
//                            'mensaje' => 'Actualizado'
//                        ];
//
//                    } else {
//
//                        $resultados[] = [
//                            'status'  => 'skipped',
//                            'msg'     => 'Duplicado omitido',
//                            'file'    => $base,
//                            'rpu'     => $rpu,
//                            'periodo' => $periodo,
//
//                            'archivo' => $base,
//                            'success' => true,
//                            'mensaje' => 'Duplicado omitido'
//                        ];
//                    }
//
//                    continue;
//                }
//
//                // ============================
//                //   5) Crear nuevo recibo
//                // ============================
//
//                Recibo::create([
//                    'rpu'        => $rpu,
//                    'medidor'    => $medidor,
//                    'cuenta'     => $cuenta,
//                    'tarifa'     => $tarifa,
//                    'periodo'    => $periodo,
//                    'direccion'  => $direccion,
//                    'subtotal'   => $subtotal,
//                    'iva'        => $iva,
//                    'total'      => $total,
//                    'servicio_id' => $servicio_id,
//                    'periodo_id'  => $periodo_id,
//                ]);
//
//                $resultados[] = [
//                    'status'  => 'created',
//                    'msg'     => 'Creado',
//                    'file'    => $base,
//                    'rpu'     => $rpu,
//                    'periodo' => $periodo,
//
//                    'archivo' => $base,
//                    'success' => true,
//                    'mensaje' => 'Creado'
//                ];
//
//            } catch (\Throwable $ex) {
//
//                // Error global del archivo XML
//                $resultados[] = [
//                    'status'  => 'error',
//                    'msg'     => $ex->getMessage(),
//                    'file'    => $base,
//
//                    'archivo' => $base,
//                    'success' => false,
//                    'error'   => $ex->getMessage()
//                ];
//            } finally {
//                // 🧹 5. BORRAR SIEMPRE LA CARPETA TEMPORAL
////                if (is_dir($pathTemporal)) {
////                    File::deleteDirectory($pathTemporal);
////                }
//            }
//        }
//
//        if (is_dir($pathTemporal)) {
//            File::deleteDirectory($pathTemporal);
//        }
//
//        return [
//            'zip'         => $nombreArchivo,
//            'success'     => true,
//            'procesados'  => $resultados
//        ];
//    }


    public function procesarZip($zipFile, $forceOverwrite = false)
    {
        $nombreArchivo = $zipFile->getClientOriginalName();

        $periodo = periodo_vigente();
        if (! $periodo) {
            throw new \RuntimeException('No hay periodo vigente definido.');
        }

        $anio = $periodo->ano;
        $mes  = str_pad($periodo->mes, 2, '0', STR_PAD_LEFT); // 01, 02...

        /**
         * IMPORTANTE:
         *  - Con el disk "cfe" tu root es storage/app/public/cfe
         *  - Aquí SOLO usamos {anio}/{mes} como ruta relativa.
         */
        $destinoRelDir = "{$anio}/{$mes}";
        Storage::disk('cfe')->makeDirectory($destinoRelDir);

        $folder = 'import_' . time() . '_' . uniqid();
        $pathTemporal = storage_path("app/private/tmp/$folder");

        if (!mkdir($pathTemporal, 0755, true) && !is_dir($pathTemporal)) {
            throw new \RuntimeException("No se pudo crear carpeta temporal: $pathTemporal");
        }

        // Guardar ZIP temporal
        $zipPath = $pathTemporal . '/archivo.zip';
        $zipFile->move($pathTemporal, "archivo.zip");

        // Extraer ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($pathTemporal);
            $zip->close();
        } else {
            throw new \Exception("No se pudo abrir el ZIP: $nombreArchivo");
        }

        $resultados = [];

        foreach (glob("$pathTemporal/*.xml") as $archivoXML) {

            $base = basename($archivoXML); // ej: 123.xml
            $nombreSinExt = pathinfo($base, PATHINFO_FILENAME); // 123

            // ==========================================
            //  A) Copiar XML/PDF a disk "cfe"
            // ==========================================
            $xmlRelativePath = "{$destinoRelDir}/{$base}";
            $pdfRelativePath = null;

            try {
                // XML
                $xmlContent = file_get_contents($archivoXML);
                if ($xmlContent === false) {
                    $resultados[] = [
                        'status'  => 'error',
                        'msg'     => 'No se pudo leer XML',
                        'file'    => $base,
                        'archivo' => $base,
                        'success' => false,
                        'error'   => 'No se pudo leer XML',
                    ];
                    continue;
                }

                Storage::disk('cfe')->put($xmlRelativePath, $xmlContent);

                // PDF hermano
                $archivoPDF = $pathTemporal . '/' . $nombreSinExt . '.pdf';
                if (is_file($archivoPDF)) {
                    $pdfBase = basename($archivoPDF);
                    $pdfRelativePath = "{$destinoRelDir}/{$pdfBase}";
                    Storage::disk('cfe')->put($pdfRelativePath, file_get_contents($archivoPDF));
                }
            } catch (\Throwable $e) {
                $resultados[] = [
                    'status'  => 'error',
                    'msg'     => 'Error al copiar XML/PDF a carpeta pública: ' . $e->getMessage(),
                    'file'    => $base,
                    'archivo' => $base,
                    'success' => false,
                    'error'   => $e->getMessage(),
                ];
                continue;
            }

            try {
                // Cargar XML sin warnings
                $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
                if ($xml === false) {
                    $resultados[] = [
                        'status'  => 'error',
                        'msg'     => 'XML inválido',
                        'file'    => $base,
                        'archivo' => $base,
                        'success' => false,
                        'error'   => 'XML inválido',
                    ];
                    continue;
                }

                // Namespaces
                $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
                $xml->registerXPathNamespace('cfe',  'http://www.itcomplements.com/cfd/cfe/v1');

                // ============================
                //   1) Nodo RPU
                // ============================
                $nodosRPU = $xml->xpath('//cfe:CFE/cfe:ComisionFederalElectricidad');
                if (!$nodosRPU || !isset($nodosRPU[0])) {
                    $msg = 'No se encontró nodo CFE/ComisionFederalElectricidad';

                    $resultados[] = [
                        'status'  => 'error',
                        'msg'     => $msg,
                        'file'    => $base,
                        'archivo' => $base,
                        'success' => false,
                        'error'   => $msg,
                    ];
                    continue;
                }

                $comision = $nodosRPU[0];
                $rpu = trim((string)$comision['RPU']);

                $isRPU = Servicio::query()->where('rpu', $rpu)->exists();

                // ============================
                //   2) clsRegArchFact
                // ============================
                $nodosCls = $xml->xpath('//clsRegArchFact');
                if ((!$nodosCls || !isset($nodosCls[0]))) {
                    $msg = 'No se encontró nodo clsRegArchFact';

                    $resultados[] = [
                        'status'  => 'error',
                        'msg'     => $msg,
                        'file'    => $base,
                        'rpu'     => $rpu,
                        'archivo' => $base,
                        'success' => false,
                        'error'   => $msg,
                    ];
                    continue;
                }

                if (!$isRPU) {
                    $msg = 'No existe en el catalogo de Servicios';

                    $resultados[] = [
                        'status'  => 'error',
                        'msg'     => $msg,
                        'file'    => $base,
                        'rpu'     => $rpu,
                        'archivo' => $base,
                        'success' => false,
                        'error'   => $msg,
                    ];
                    continue;
                }

                $cls = $nodosCls[0];

                // ============================
                //   3) Campos OCR (ya tenías)
                // ============================
                $OCR_AAMM   = trim((string)($cls['OCR_AAMM']   ?? ($cls->OCR_AAMM   ?? '')));
                $OCR_AAAA   = trim((string)($cls['OCR_AAAA']   ?? ($cls->OCR_AAAA   ?? '')));
                $OCR_MM     = trim((string)($cls['OCR_MM']     ?? ($cls->OCR_MM     ?? '')));
                $OCR_MM_NOM = trim((string)($cls['OCR_MM_NOM'] ?? ($cls->OCR_MM_NOM ?? '')));
                $OCR_TIPO   = trim((string)($cls['OCR_TIPO']   ?? ($cls->OCR_TIPO   ?? '')));
                $OCR_DIGITO = trim((string)($cls['OCR_DIGITO'] ?? ($cls->OCR_DIGITO ?? '')));

                // ============================
                //   4) Datos básicos
                // ============================
                $medidor  = (string)($cls->NUMMED1 ?? '');
                $cuenta   = (string)($cls->NumCta ?? '');
                $tarifa   = (string)($cls->TARIFA ?? '');

                // ============================
                //   Fechas y periodo
                // ============================
                $desdeRaw = (string)($cls->FECDESDE ?? '');   // "12 AGO 25"
                $hastaRaw = (string)($cls->FECHASTA ?? '');   // "11 SEP 25"

                $periodo  = trim($desdeRaw . ' - ' . $hastaRaw);

                $desde = parseCfeDate($desdeRaw);
                $hasta = parseCfeDate($hastaRaw);
                try {
                    if ($desdeRaw) {
                        $desde = Carbon::parse($desdeRaw)->format('Y-m-d');
                    }
                    if ($hastaRaw) {
                        $hasta = Carbon::parse($hastaRaw)->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    // Si no puede parsear, se quedan null
                }

                // ============================
                //   Datos básicos
                // ============================
                $medidor  = (string)($cls->NUMMED1 ?? '');          // DAX156
                $cuenta   = (string)($cls->NumCta ?? '');           // 68DK17A016811100
                $tarifa   = (string)($cls->TARIFA ?? '');           // 5A

                $direccion = trim(
                    (string)($cls->DIRECCR ?? '') . ', ' .          // PASEO TABASCO NO. 1401
                    (string)($cls->NOMPOBR ?? '') . ', ' .          // TABASCO 2000 C.P. 86035
                    (string)($cls->NOMESTR ?? '')                   // TAB.
                );

                // ============================
                //   Campos de consumo / demanda
                // ============================
                $consumo         = (float)($cls->CONSUMO_R ?? 0);   // 1515
                $demanda         = (float)($cls->DEMANDA   ?? 0);   // 0
                $reactivos       = (float)($cls->KVARH     ?? 0);   // 0
                $factor_potencia = (float)($cls->FacPot    ?? 0);   // 0
                $factor_carga    = (float)($cls->CARGA_CONECTADA ?? 0); // 6

                // ============================
                //   Importes
                // ============================
                // "SubTotal" del clsRegArchFact = 6978.65 (lo usamos como energía)
                $energia = (float)($cls->SubTotal ?? 0);

                // IVA ya lo tenías, pero ahora lo usamos también para Recibo
                $iva = (float)($cls->IMPIVA ?? 0);                  // 1116.58

                // DAP monetario -> IMPDAP
                $dap = (float)($cls->IMPDAP ?? 0);                  // 0 en este ejemplo

                // Cargos/depositos: usamos adeudo anterior (ADEANT)
                $cargos_y_depositos = (float)($cls->ADEANT ?? 0);   // 0 en este ejemplo

                // Créditos y redondeos: tomamos la diferencia por redondeo Importe6 (0.46)
                $creditos_y_redondeos = 0.0;
                if (isset($cls->Importes) && isset($cls->Importes->Importe6)) {
                    $creditos_y_redondeos = (float)$cls->Importes->Importe6;   // 0.46
                }

                // Total que estás usando actualmente
                $total = (float)($cls->CargosCreditos ?? 0);        // 8095.23

                // ============================
                //   Fórmula que comentaste:
                //   validacion_total = energia + iva + dap + cargos_y_depositos + creditos_y_redondeos
                // ============================
                $validacion_total = $energia + $iva + $dap + $cargos_y_depositos + $creditos_y_redondeos;

                // diferencia = total - validacion_total
                $diferencia       = $total - $validacion_total;

                // ============================
                //   6) Evitar duplicados
                // ============================
                $existente = Recibo::where('rpu', $rpu)
                    ->where('periodo', $periodo)
                    ->where('cuenta', $cuenta)
                    ->first();

                $servicio_id = Servicio::query()->where('rpu', $rpu)->first()->id ?? null;
                $periodo_id = Periodo::query()
                    ->where('ano', $OCR_AAAA)
                    ->where('mes', $OCR_MM)
                    ->where('tipo', $OCR_TIPO)
                    ->first()->id ?? null;

                if ($periodo_id === null) {
                    $per = Periodo::create([
                        'anomes'     => $OCR_AAMM,
                        'ano'        => $OCR_AAAA,
                        'mes'        => $OCR_MM,
                        'mes_nombre' => $OCR_MM_NOM,
                        'tipo'       => $OCR_TIPO,
                        'digito'     => $OCR_DIGITO,
                    ]);
                    $periodo_id = $per->id;
                }

                // ==========================================
                // 7) UPDATE o CREATE del Recibo
                // ==========================================
                $payload = [
                    'rpu'        => $rpu,
                    'medidor'    => $medidor,
                    'cuenta'     => $cuenta,
                    'tarifa'     => $tarifa,
                    'periodo'    => $periodo,
                    'direccion'  => $direccion,

                    'desde'      => $desde,
                    'hasta'      => $hasta,

                    'consumo'         => $consumo,
                    'demanda'         => $demanda,
                    'reactivos'       => $reactivos,
                    'factor_potencia' => $factor_potencia,
                    'factor_carga'    => $factor_carga,

                    'energia'             => $energia,
                    'iva'                 => $iva,
                    'dap'                 => $dap,
                    'cargos_y_depositos'  => $cargos_y_depositos,
                    'creditos_y_redondeos'=> $creditos_y_redondeos,
                    'total'               => $total,
                    'validacion_total'    => $validacion_total,
                    'diferencia'          => $diferencia,

                    'xml_file' => $xmlRelativePath,
                    'pdf_file' => $pdfRelativePath,

                    'servicio_id' => $servicio_id,
                    'periodo_id'  => $periodo_id,
                ];

                if (is_null($payload['desde'])) {
                    unset($payload['desde']);
                }
                if (is_null($payload['hasta'])) {
                    unset($payload['hasta']);
                }

                if ($existente) {
                    if ($forceOverwrite) {
                        $existente->update($payload);

                        $resultados[] = [
                            'status'  => 'updated',
                            'msg'     => 'Actualizado',
                            'file'    => $base,
                            'rpu'     => $rpu,
                            'periodo' => $periodo,
                            'archivo' => $base,
                            'success' => true,
                            'mensaje' => 'Actualizado',
                        ];
                    } else {
                        $resultados[] = [
                            'status'  => 'skipped',
                            'msg'     => 'Duplicado omitido',
                            'file'    => $base,
                            'rpu'     => $rpu,
                            'periodo' => $periodo,
                            'archivo' => $base,
                            'success' => true,
                            'mensaje' => 'Duplicado omitido',
                        ];
                    }

                    continue;
                }

                // Crear nuevo recibo
                Recibo::create($payload);

                $resultados[] = [
                    'status'  => 'created',
                    'msg'     => 'Creado',
                    'file'    => $base,
                    'rpu'     => $rpu,
                    'periodo' => $periodo,
                    'archivo' => $base,
                    'success' => true,
                    'mensaje' => 'Creado',
                ];

            } catch (\Throwable $ex) {

                $resultados[] = [
                    'status'  => 'error',
                    'msg'     => $ex->getMessage(),
                    'file'    => $base,
                    'archivo' => $base,
                    'success' => false,
                    'error'   => $ex->getMessage(),
                ];
            }
        }

        // 🧹 BORRAR carpeta temporal
        if (is_dir($pathTemporal)) {
            File::deleteDirectory($pathTemporal);
        }

        return [
            'zip'        => $nombreArchivo,
            'success'    => true,
            'procesados' => $resultados,
        ];
    }



}
