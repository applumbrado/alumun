<?php

namespace App\Services\CFE;

use App\Helpers\CfeXmlCI;
use App\Models\Catalogos\Servicio;
use App\Models\CFE\Periodo;
use App\Models\CFE\Recibo;
use SimpleXMLElement;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CFEImportService{

    public function procesarZip($zipFile, $forceOverwrite = false){

        $nombreArchivo = $zipFile->getClientOriginalName();

        $periodo = periodo_vigente();
        if (! $periodo) {
            throw new \RuntimeException('No hay periodo vigente definido.');
        }

        $anio = $periodo->ano;
        $mes  = str_pad($periodo->mes, 2, '0', STR_PAD_LEFT);

        $destinoRelDir = "{$anio}/{$mes}";
        Storage::disk('cfe')->makeDirectory($destinoRelDir);

        $folder       = 'import_' . time() . '_' . uniqid();
        $pathTemporal = storage_path("app/private/tmp/$folder");
        $cfeTmpDir    = storage_path('app/private/cfe/tmp');

        if (!mkdir($pathTemporal, 0755, true) && !is_dir($pathTemporal)) {
            throw new \RuntimeException("No se pudo crear carpeta temporal: $pathTemporal");
        }

        // Detectar duplicados dentro del ZIP por (rpu+periodo+cuenta)
        $seenKeys = [];

        try {
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

                $base         = basename($archivoXML);
                $nombreSinExt = pathinfo($base, PATHINFO_FILENAME);

                // 1) Leer XML
                $xmlContent = @file_get_contents($archivoXML);
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

                // 2) Parsear XML
                // $xmlContent = mb_convert_encoding($xmlContent, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

                // ✅ NORMALIZAR XML A UTF-8 ANTES DE PARSEAR
                $xmlContent = $this->normalizeXmlToUtf8($xmlContent);


                $xml = @simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
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

                $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
                $xml->registerXPathNamespace('cfe',  'http://www.itcomplements.com/cfd/cfe/v1');

                // 3) Nodo RPU
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
                $rpu      = trim((string)$comision['RPU']);

                // 4) clsRegArchFact
                $nodosCls = $xml->xpath('//clsRegArchFact');
                if (!$nodosCls || !isset($nodosCls[0])) {
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

                $cls = $nodosCls[0];

                // 5) OCR
                $OCR_AAMM   = trim((string)($cls['OCR_AAMM']   ?? ($cls->OCR_AAMM   ?? '')));
                $OCR_AAAA   = trim((string)($cls['OCR_AAAA']   ?? ($cls->OCR_AAAA   ?? '')));
                $OCR_MM     = trim((string)($cls['OCR_MM']     ?? ($cls->OCR_MM     ?? '')));
                $OCR_MM_NOM = trim((string)($cls['OCR_MM_NOM'] ?? ($cls->OCR_MM_NOM ?? '')));
                $OCR_TIPO   = trim((string)($cls['OCR_TIPO']   ?? ($cls->OCR_TIPO   ?? '')));
                $OCR_DIGITO = trim((string)($cls['OCR_DIGITO'] ?? ($cls->OCR_DIGITO ?? '')));

                // Fuera de periodo activo
                if ((int)$OCR_AAAA !== (int)$anio || (int)$OCR_MM !== (int)$mes) {
                    $resultados[] = [
                        'status'  => 'skipped',
                        'msg'     => "Fuera de periodo activo (activo: {$anio}-{$mes}, archivo: {$OCR_AAAA}-{$OCR_MM})",
                        'file'    => $base,
                        'archivo' => $base,
                        'success' => true,
                        'mensaje' => 'Fuera de periodo activo',

                        'periodo_activo' => ['ano' => (int)$anio, 'mes' => (int)$mes],
                        'ocr' => [
                            'OCR_AAMM'   => $OCR_AAMM,
                            'OCR_AAAA'   => $OCR_AAAA,
                            'OCR_MM'     => $OCR_MM,
                            'OCR_MM_NOM' => $OCR_MM_NOM,
                            'OCR_TIPO'   => $OCR_TIPO,
                            'OCR_DIGITO' => $OCR_DIGITO,
                        ],
                        'OCR_AAMM'   => $OCR_AAMM,
                        'OCR_AAAA'   => $OCR_AAAA,
                        'OCR_MM'     => $OCR_MM,
                        'OCR_MM_NOM' => $OCR_MM_NOM,
                        'OCR_TIPO'   => $OCR_TIPO,
                        'OCR_DIGITO' => $OCR_DIGITO,
                        'anio_activo'=> (int)$anio,
                        'mes_activo' => (int)$mes,
                    ];
                    continue;
                }

                // Validar RPU catálogo
                $isRPU = Servicio::query()->where('rpu', $rpu)->exists();
                if (!$isRPU) {
                    $msg = 'No existe en el catálogo de Servicios';
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

                // Básicos
                $cuenta  = (string)($cls->NumCta ?? '');
                $medidor = (string)($cls->NUMMED1 ?? '');
                $tarifa  = (string)($cls->TARIFA ?? '');

                // PeriodoStr
                $desdeRaw   = (string)($cls->FECDESDE ?? '');
                $hastaRaw   = (string)($cls->FECHASTA ?? '');
                $periodoStr = trim($desdeRaw . ' - ' . $hastaRaw);

                // Key única item
                $itemKey  = $rpu . '|' . $periodoStr . '|' . $cuenta;
                $dupEnZip = isset($seenKeys[$itemKey]);
                $seenKeys[$itemKey] = true;

                // PDF en ZIP (si existe)
                $archivoPDF  = $pathTemporal . '/' . $nombreSinExt . '.pdf';
                $hasPdfInZip = is_file($archivoPDF);

                // ============================
                // Rutas sugeridas NORMALIZADAS (1 solo XML/PDF por item)
                // ============================
                $safe = fn($v) => preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$v);
                $periodKey = $safe($OCR_AAAA) . str_pad((string)$OCR_MM, 2, '0', STR_PAD_LEFT);
                $baseName  = $safe($rpu) . '_' . $periodKey;

                $xmlSuggested = "{$destinoRelDir}/{$baseName}.xml";
                $pdfSuggested = "{$destinoRelDir}/{$baseName}.pdf";

                // ============================
                // Buscar duplicado en BD
                // ============================
                $existente = Recibo::where('rpu', $rpu)
                    ->where('periodo_id', $periodo->id)
                    ->first();

                $xmlDb = $existente?->xml_file;
                $pdfDb = $existente?->pdf_file;

                // ============================
                // ✅ RESOLVER EXISTENCIA EN AMBAS RUTAS (DB y sugerida)
                // ============================
                $xmlCandidates = array_values(array_unique(array_filter([$xmlDb, $xmlSuggested])));
                $xmlExisting = [];
                foreach ($xmlCandidates as $p) {
                    if (Storage::disk('cfe')->exists($p)) $xmlExisting[] = $p;
                }
                $xmlExistsAny = count($xmlExisting) > 0;

                // Elegir path “real” existente (preferir DB si existe; si no, sugerida)
                $xmlChosen = null;
                if ($xmlDb && in_array($xmlDb, $xmlExisting, true)) {
                    $xmlChosen = $xmlDb;
                } elseif (in_array($xmlSuggested, $xmlExisting, true)) {
                    $xmlChosen = $xmlSuggested;
                } else {
                    // si no existe en ningún lado, ruta para escribir:
                    $xmlChosen = $xmlDb ?: $xmlSuggested;
                }

                $pdfCandidates = array_values(array_unique(array_filter([$pdfDb, $pdfSuggested])));
                $pdfExisting = [];
                foreach ($pdfCandidates as $p) {
                    if (Storage::disk('cfe')->exists($p)) $pdfExisting[] = $p;
                }
                $pdfExistsAny = count($pdfExisting) > 0;

                $pdfChosen = null;
                if ($pdfDb && in_array($pdfDb, $pdfExisting, true)) {
                    $pdfChosen = $pdfDb;
                } elseif (in_array($pdfSuggested, $pdfExisting, true)) {
                    $pdfChosen = $pdfSuggested;
                } else {
                    // si no existe, ruta para escribir SOLO si viene PDF en ZIP
                    $pdfChosen = $pdfDb ?: ($hasPdfInZip ? $pdfSuggested : null);
                }

                // ============================
                // ✅ Si NO hay PDF en ningún lado y NO viene en ZIP => error (no se puede cumplir 1 item = 1 pdf)
                // ============================
                if (!$pdfExistsAny && !$hasPdfInZip) {
                    $resultados[] = [
                        'status'  => 'error',
                        'msg'     => 'No se encontró PDF (ni en storage ni en ZIP) para este item',
                        'file'    => $base,
                        'archivo' => $base,
                        'rpu'     => $rpu,
                        'periodo' => $periodoStr,
                        'cuenta'  => $cuenta,
                        'success' => false,

                        'pdf_db'        => $pdfDb,
                        'pdf_suggested' => $pdfSuggested,
                    ];
                    continue;
                }

                // ============================
                // Datos para payload (igual que antes)
                // ============================
                $direccion = trim(
                    (string)($cls->DIRECCR ?? '') . ', ' .
                    (string)($cls->NOMPOBR ?? '') . ', ' .
                    (string)($cls->NOMESTR ?? '')
                );

                // ✅ ahora sí quedan como fechas YYYY-MM-DD (o null si no se pudo)
                $desde = parseCfePeriodoFecha($desdeRaw);
                $hasta = parseCfePeriodoFecha($hastaRaw);


                $consumo         = (float)($cls->CONSUMO_R ?? 0);
                $demanda         = (float)($cls->DEMANDA ?? 0);
                $reactivos       = (float)($cls->KVARH ?? 0);
                $factor_potencia = (float)($cls->FacPot ?? 0);
                $factor_carga    = (float)($cls->CARGA_CONECTADA ?? 0);


                $energia = (float)($cls->SubTotal ?? 0);
                $subtotal = (float)($cls->SubTotal ?? 0);
                $iva     = (float)($cls->IMPIVA ?? 0);
                $dap     = (float)($cls->IMPDAP ?? 0);
                $cargos_y_depositos = (float)($cls->ADEANT ?? 0);

                $creditos_y_redondeos = 0.0;

                $creditos_y_redondeos = $creditos_y_redondeos = xml_num_field_find(
                    $cls->Importes ?? null,
                    'Importe',
                    10,
                    fn(float $v) => $v > 0 && $v < 1,
                    0.0
                );

//                $total = (float)($cls->CargosCreditos ?? 0);
                $total = (float)($cls->IMPTOTAL ?? 0);
                $validacion_total = $energia + $iva + $dap + $cargos_y_depositos + $creditos_y_redondeos;
                $diferencia = $total - $validacion_total;

                // ============================
                // ✅ Necesidad de corregir rutas en BD sin escribir archivos
                // (cuando la BD apunta a algo faltante pero el archivo existe en la otra ruta)
                // ============================
                $needsPathFix = $existente && (
                        ($xmlExistsAny && $existente->xml_file !== $xmlChosen) ||
                        ($pdfExistsAny && $pdfChosen && $existente->pdf_file !== $pdfChosen)
                    );

                // ============================
                // ✅ Si existe item y existen archivos (en cualquier ruta) => NO SOBREESCRIBIR
                // pero si hay que corregir rutas, se actualiza BD (updated)
                // ============================
                if ($existente && !$forceOverwrite && $xmlExistsAny && $pdfExistsAny) {

                    if ($needsPathFix) {
                        $fix = [
                            'xml_file' => $xmlChosen,
                            'pdf_file' => $pdfChosen,
                        ];
                        $existente->update($fix);

                        $resultados[] = [
                            'status'  => 'updated',
                            'msg'     => 'Rutas corregidas (archivos ya existían en otra ruta)',
                            'file'    => $base,
                            'archivo' => $base,
                            'rpu'     => $rpu,
                            'periodo' => $periodoStr,
                            'cuenta'  => $cuenta,
                            'success' => true,

                            'duplicado_db' => true,
                            'duplicado_zip'=> $dupEnZip,
                            'xml_file'     => $xmlChosen,
                            'pdf_file'     => $pdfChosen,
                        ];
                    } else {
                        $resultados[] = [
                            'status'  => 'skipped',
                            'msg'     => $dupEnZip
                                ? 'Duplicado en ZIP omitido (item+archivos ya existen)'
                                : 'Duplicado omitido (DB y archivos OK)',
                            'file'    => $base,
                            'archivo' => $base,
                            'rpu'     => $rpu,
                            'periodo' => $periodoStr,
                            'cuenta'  => $cuenta,
                            'success' => true,

                            'duplicado_db' => true,
                            'duplicado_zip'=> $dupEnZip,
                            'xml_file'     => $xmlChosen,
                            'pdf_file'     => $pdfChosen,
                        ];
                    }

                    continue;
                }

                // ============================
                // ✅ Guardar SOLO lo faltante (o sobrescribir si forceOverwrite)
                // ============================
                $wroteXml = false;
                $wrotePdf = false;

                try {
                    // XML
                    if ($forceOverwrite) {
                        Storage::disk('cfe')->put($xmlChosen, $xmlContent);
                        $wroteXml = true;
                    } else {
                        if (!$xmlExistsAny) {
                            Storage::disk('cfe')->put($xmlChosen, $xmlContent);
                            $wroteXml = true;
                            $xmlExistsAny = true;
                        }
                    }

                    // PDF (solo si viene en ZIP o si forceOverwrite con ruta)
                    if ($pdfChosen && $hasPdfInZip) {
                        if ($forceOverwrite) {
                            Storage::disk('cfe')->put($pdfChosen, file_get_contents($archivoPDF));
                            $wrotePdf = true;
                            $pdfExistsAny = true;
                        } else {
                            if (!$pdfExistsAny) {
                                Storage::disk('cfe')->put($pdfChosen, file_get_contents($archivoPDF));
                                $wrotePdf = true;
                                $pdfExistsAny = true;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $resultados[] = [
                        'status'  => 'error',
                        'msg'     => 'Error al copiar XML/PDF: ' . $e->getMessage(),
                        'file'    => $base,
                        'archivo' => $base,
                        'rpu'     => $rpu,
                        'periodo' => $periodoStr,
                        'cuenta'  => $cuenta,
                        'success' => false,
                        'error'   => $e->getMessage(),
                    ];
                    continue;
                }

                // ============================
                // Periodo_id / servicio_id
                // ============================
                $servicio_id = Servicio::query()->where('rpu', $rpu)->first()->id ?? null;

                $periodo_id = $periodo->id;

                $arrConceptos = collect(CfeXmlCI::conceptosMap($xml))
                    ->mapWithKeys(fn ($item, $i) => ["concepto{$i}" => $item])
                    ->toArray();

                $arrImportes = collect(CfeXmlCI::importesMap($xml))
                    ->mapWithKeys(fn ($item, $i) => ["importe{$i}" => $item])
                    ->toArray();

                $aConceptos = CfeXmlCI::conceptos($xml);
                $aImportes = CfeXmlCI::importes($xml);

                $total_recibo = 0;
                foreach ($aConceptos as $key => $concepto) {
                    if (substr($concepto,0,4) === 'Ener') {
                        $energia = $aImportes[$key];
                    }
                    if (substr($concepto,0,4) === 'Subt') {
                        $subtotal = $aImportes[$key];
                    }
                    if (substr($concepto,0,5) === 'Factu') {
                        $total_recibo = $aImportes[$key];
                    }
                }


                // Payload
                $payloadFull = [
                    'rpu'       => $rpu,
                    'periodo'   => $periodo->periodo,
                    'periodo_extend' => $periodoStr,
                    'medidor'   => $medidor,
                    'cuenta'    => $cuenta,
                    'tarifa'    => $tarifa,
                    'direccion' => $direccion,
                    'desde'     => $desde,
                    'hasta'     => $hasta,

                    'consumo'         => $consumo,
                    'demanda'         => $demanda,
                    'reactivos'       => $reactivos,
                    'factor_potencia' => $factor_potencia,
                    'factor_carga'    => $factor_carga,

                    'energia'              => $energia,
                    'subtotal'             => $subtotal,
                    'iva'                  => $iva,
                    'dap'                  => $dap,
                    'cargos_y_depositos'   => $cargos_y_depositos,
                    'creditos_y_redondeos' => $creditos_y_redondeos,
                    'total'                => $total,
                    'total_recibo'         => $total_recibo,
                    'validacion_total'     => $validacion_total,
                    'diferencia'           => $diferencia,

                    'xml_file' => $xmlChosen,
                    'pdf_file' => $pdfChosen,

                    'servicio_id' => $servicio_id,
                    'periodo_id'  => $periodo_id,
                ];

                // Update/Create
                if ($existente) {

                    // si NO forceOverwrite: NO pisamos datos, pero sí aseguramos rutas/ids
                    if (!$forceOverwrite) {
                        $fix = [
                            'xml_file' => $xmlChosen,
                            'pdf_file' => $pdfChosen,
                        ];
                        if (empty($existente->servicio_id) && $servicio_id) $fix['servicio_id'] = $servicio_id;
                        if (empty($existente->periodo_id)  && $periodo_id)  $fix['periodo_id']  = $periodo_id;

                        $existente->update($fix);



                        $resultados[] = [
                            'status'  => 'updated',
                            'msg'     => 'Duplicado en DB: se completaron archivos faltantes (sin sobreescribir existentes)',
                            'file'    => $base,
                            'archivo' => $base,
                            'rpu'     => $rpu,
                            'periodo' => $periodoStr,
                            'cuenta'  => $cuenta,
                            'success' => true,

                            'duplicado_db' => true,
                            'duplicado_zip'=> $dupEnZip,
                            'wrote_xml'    => $wroteXml,
                            'wrote_pdf'    => $wrotePdf,
                            'xml_file'     => $xmlChosen,
                            'pdf_file'     => $pdfChosen,
                        ];
                    } else {
                        $existente->update($payloadFull);

                        $arrReciboPeriodo = ["recibo_id" => $existente->id, "periodo_id" => $existente->periodo_id,];
                        $arrConcepto = array_merge($arrConceptos,$arrImportes,$arrReciboPeriodo);
                        $existente->concepto()->sync($arrConcepto);


                        $resultados[] = [
                            'status'  => 'updated',
                            'msg'     => 'Actualizado (forceOverwrite)',
                            'file'    => $base,
                            'archivo' => $base,
                            'rpu'     => $rpu,
                            'periodo' => $periodoStr,
                            'cuenta'  => $cuenta,
                            'success' => true,
                            'wrote_xml' => $wroteXml,
                            'wrote_pdf' => $wrotePdf,
                            'xml_file'  => $xmlChosen,
                            'pdf_file'  => $pdfChosen,
                        ];
                    }
                } else {
                    // Si archivo ya existía, no se escribió; igual creamos registro apuntando a él
                    $existente = Recibo::create($payloadFull);

                    $arrReciboPeriodo = ["recibo_id" => $existente->id, "periodo_id" => $existente->periodo_id,];
                    $arrConcepto = array_merge($arrConceptos,$arrImportes,$arrReciboPeriodo);

                    $existente->concepto()->create($arrConcepto);

                    $resultados[] = [
                        'status'  => 'created',
                        'msg'     => ($xmlExistsAny || $pdfExistsAny)
                            ? 'Creado (referenciando archivo existente)'
                            : 'Creado',
                        'file'    => $base,
                        'archivo' => $base,
                        'rpu'     => $rpu,
                        'periodo' => $periodoStr,
                        'cuenta'  => $cuenta,
                        'success' => true,

                        'duplicado_db' => false,
                        'duplicado_zip'=> $dupEnZip,
                        'wrote_xml'    => $wroteXml,
                        'wrote_pdf'    => $wrotePdf,
                        'xml_file'     => $xmlChosen,
                        'pdf_file'     => $pdfChosen,
                    ];
                }
            }

            return [
                'zip'        => $nombreArchivo,
                'success'    => true,
                'procesados' => $resultados,
            ];

        } finally {

            if ($pathTemporal && is_dir($pathTemporal)) {
                File::deleteDirectory($pathTemporal);
            }

            if (is_dir($cfeTmpDir)) {
                File::cleanDirectory($cfeTmpDir);
            }
        }
    }






    private function normalizeXmlToUtf8(string $xml): string{

        // Quitar BOM si viene
        $xml = preg_replace('/^\xEF\xBB\xBF/', '', $xml);

        // Detectar encoding declarado en el XML
        $declared = null;
        if (preg_match('/<\?xml[^>]*encoding=["\']([^"\']+)["\']/i', $xml, $m)) {
            $declared = strtoupper(trim($m[1]));
        }

        // Si el contenido NO es UTF-8 válido, casi seguro viene en ISO/Windows-1252
        if (!mb_check_encoding($xml, 'UTF-8')) {
            $from = $declared ?: 'WINDOWS-1252';
            // fallback duro por si declared es raro
            $from = str_replace(['UTF8'], ['UTF-8'], $from);

            $converted = @iconv($from, 'UTF-8//TRANSLIT//IGNORE', $xml);

            if ($converted === false) {
                // último fallback
                $converted = mb_convert_encoding($xml, 'UTF-8', 'WINDOWS-1252, ISO-8859-1');
            }

            $xml = $converted;
        }

        // Forzar que el header diga UTF-8 para que libxml no “reinterprete”
        if (preg_match('/<\?xml/i', $xml)) {
            $xml = preg_replace(
                '/(<\?xml[^>]*encoding=["\'])([^"\']+)(["\'])/i',
                '$1UTF-8$3',
                $xml
            );
        }

        return $xml;
    }


}
