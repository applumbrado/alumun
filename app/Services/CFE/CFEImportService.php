<?php

namespace App\Services\CFE;

use App\Models\Catalogos\Servicio;
use App\Models\CFE\Periodo;
use App\Models\CFE\Recibo;
use SimpleXMLElement;
use ZipArchive;
use Illuminate\Support\Facades\File;

class CFEImportService{

//    public function procesarZip($zipFile, $forceOverwrite = false)
//    {
//        $folder = 'import_' . time();
//        $pathTemporal = storage_path("app/private/tmp/$folder");
//
//        if (!mkdir($pathTemporal, 0755, true) && !is_dir($pathTemporal)) {
//            throw new \RuntimeException(sprintf('Directory "%s" was not created', $pathTemporal));
//        }
//
//        // Guardar ZIP temporal
//        $zipPath = $pathTemporal . '/archivo.zip';
//        file_put_contents($zipPath, file_get_contents($zipFile));
//
//        // Extraer ZIP
//        $zip = new ZipArchive();
//        if ($zip->open($zipPath) === TRUE) {
//            $zip->extractTo($pathTemporal);
//            $zip->close();
//        }
//
//        $resultados = [];
//
//        foreach (glob("$pathTemporal/*.xml") as $archivoXML) {
//
//            $xmlContent = file_get_contents($archivoXML);
//            if (!$xmlContent) {
//                // No se pudo leer el archivo, lo saltamos
//                $resultados[] = ['status' => 'error', 'msg' => 'No se pudo leer XML', 'file' => basename($archivoXML)];
//                continue;
//            }
//
//            // Cargar XML (sin warnings que rompan)
//            $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
//            if ($xml === false) {
//                $resultados[] = ['status' => 'error', 'msg' => 'XML inválido', 'file' => basename($archivoXML)];
//                continue;
//            }
//
//            // Registramos namespaces a mano (los que sabemos que usa CFE)
//            $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
//            $xml->registerXPathNamespace('cfe', 'http://www.itcomplements.com/cfd/cfe/v1');
//
//            // ============================
//            //   1) RPU desde CFE
//            // ============================
//            $nodosRPU = $xml->xpath('//cfe:CFE/cfe:ComisionFederalElectricidad');
//            if (!$nodosRPU || !isset($nodosRPU[0])) {
//                $resultados[] = ['status' => 'error', 'msg' => 'No se encontró nodo CFE/ComisionFederalElectricidad', 'file' => basename($archivoXML)];
//                continue;
//            }
//            $comision = $nodosRPU[0];
//            $rpu = (string) $comision['RPU'];
//
//            // ============================
//            //   2) clsRegArchFact (sin namespace)
//            // ============================
//            $nodosCls = $xml->xpath('//clsRegArchFact');
//            if (!$nodosCls || !isset($nodosCls[0])) {
//                $resultados[] = ['status' => 'error', 'msg' => 'No se encontró nodo clsRegArchFact', 'file' => basename($archivoXML), 'rpu' => $rpu];
//                continue;
//            }
//
//            /** @var \SimpleXMLElement $cls */
//            $cls = $nodosCls[0];
//
//            // ============================
//            //   3) Extraer datos reales
//            // ============================
//            $medidor  = (string) ($cls->NUMMED1 ?? '');
//            $cuenta   = (string) ($cls->NumCta ?? '');
//            $tarifa   = (string) ($cls->TARIFA ?? '');
//
//            $periodo = trim((string)($cls->FECDESDE ?? '') . ' - ' . (string)($cls->FECHASTA ?? ''));
//
//            $direccion = trim(
//                (string)($cls->DIRECCR ?? '') . ', ' .
//                (string)($cls->NOMPOBR ?? '') . ', ' .
//                (string)($cls->NOMESTR ?? '')
//            );
//
//            $subtotal = (float) ($cls->SubTotal ?? 0);
//            $iva      = (float) ($cls->IMPIVA ?? 0);
//            $total    = (float) ($cls->CargosCreditos ?? 0);
//
//            // ============================
//            //   4) Evitar duplicados
//            // ============================
//
//            $existente = Recibo::where('rpu', $rpu)
//                ->where('periodo', $periodo)
//                ->where('cuenta', $cuenta)
//                ->first();
//
//            if ($existente) {
//                if ($forceOverwrite) {
//                    $existente->update([
//                        'medidor'   => $medidor,
//                        'tarifa'    => $tarifa,
//                        'direccion' => $direccion,
//                        'subtotal'  => $subtotal,
//                        'iva'       => $iva,
//                        'total'     => $total,
//                    ]);
//                    $resultados[] = [
//                        'status'  => 'updated',
//                        'rpu'     => $rpu,
//                        'periodo' => $periodo,
//                        'file'    => basename($archivoXML)
//                    ];
//                } else {
//                    $resultados[] = [
//                        'status'  => 'skipped',
//                        'rpu'     => $rpu,
//                        'periodo' => $periodo,
//                        'file'    => basename($archivoXML)
//                    ];
//                }
//                continue;
//            }
//
//            // ============================
//            //   5) Crear nuevo recibo
//            // ============================
//
//            Recibo::create([
//                'rpu'       => $rpu,
//                'medidor'   => $medidor,
//                'cuenta'    => $cuenta,
//                'tarifa'    => $tarifa,
//                'periodo'   => $periodo,
//                'direccion' => $direccion,
//                'subtotal'  => $subtotal,
//                'iva'       => $iva,
//                'total'     => $total,
//            ]);
//
//            $resultados[] = [
//                'status'  => 'created',
//                'rpu'     => $rpu,
//                'periodo' => $periodo,
//                'file'    => basename($archivoXML)
//            ];
//        }
//
//        return $resultados;
//    }



//    public function procesarZip($zipFile, $forceOverwrite = false)
//    {
//        $nombreArchivo = $zipFile->getClientOriginalName();
//
//        $folder = 'import_' . time() . '_' . uniqid();
//        $pathTemporal = storage_path("app/private/tmp/{$folder}");
//
//        if (!mkdir($pathTemporal, 0755, true) && !is_dir($pathTemporal)) {
//            throw new \RuntimeException("No se pudo crear carpeta temporal: $pathTemporal");
//        }
//
//        // Guardar ZIP temporal
//        $zipPath = "{$pathTemporal}/archivo.zip";
//        $zipFile->move($pathTemporal, "archivo.zip");
//
//        // Extraer ZIP
//        $zip = new ZipArchive();
//        if ($zip->open($zipPath) === true) {
//            $zip->extractTo($pathTemporal);
//            $zip->close();
//        } else {
//            throw new \Exception("No se pudo abrir el ZIP: $nombreArchivo");
//        }
//
//        $resultados = [];
//        $archivos = glob($pathTemporal . '/*.xml');
//
//        foreach ($archivos as $archivoXML) {
//            try {
//                $xmlString = file_get_contents($archivoXML);
//                $xml = new SimpleXMLElement($xmlString);
//
//                $ns = $xml->getNamespaces(true);
//                // Lógica de procesamiento del XML...
//                // Ejemplo:
//                // $recibo = $this->procesarXML($xml, $ns);
//
//                $resultados[] = [
//                    'archivo' => basename($archivoXML),
//                    'success' => true,
//                    'mensaje' => 'Procesado correctamente'
//                ];
//
//            } catch (\Throwable $ex) {
//                $resultados[] = [
//                    'archivo' => basename($archivoXML),
//                    'success' => false,
//                    'error' => $ex->getMessage()
//                ];
//            }
//        }
//
//        return [
//            'zip' => $nombreArchivo,
//            'success' => true,
//            'procesados' => $resultados
//        ];
//    }


    public function procesarZip($zipFile, $forceOverwrite = false){

        $nombreArchivo = $zipFile->getClientOriginalName();

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

            $base = basename($archivoXML);

            try {
                $xmlContent = file_get_contents($archivoXML);
                if (!$xmlContent) {
                    $resultados[] = [
                        'status'  => 'error',
                        'msg'     => 'No se pudo leer XML',
                        'file'    => $base,

                        // formato compatible nuevo
                        'archivo' => $base,
                        'success' => false,
                        'error'   => 'No se pudo leer XML'
                    ];
                    continue;
                }

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
                $rpu = trim( (string)$comision['RPU'] );

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
                //   Campos OCR (atributos o nodos)
                // ============================
                $OCR_AAMM     = (string)($cls['OCR_AAMM']     ?? ($cls->OCR_AAMM     ?? ''));
                $OCR_AAAA     = (string)($cls['OCR_AAAA']     ?? ($cls->OCR_AAAA     ?? ''));
                $OCR_MM       = (string)($cls['OCR_MM']       ?? ($cls->OCR_MM       ?? ''));
                $OCR_MM_NOM   = (string)($cls['OCR_MM_NOM']   ?? ($cls->OCR_MM_NOM   ?? ''));
                $OCR_TIPO     = (string)($cls['OCR_TIPO']     ?? ($cls->OCR_TIPO     ?? ''));
                $OCR_DIGITO   = (string)($cls['OCR_DIGITO']   ?? ($cls->OCR_DIGITO   ?? ''));

                // Normalizar espacios
                $OCR_AAMM   = trim($OCR_AAMM);
                $OCR_AAAA   = trim($OCR_AAAA);
                $OCR_MM     = trim($OCR_MM);
                $OCR_MM_NOM = trim($OCR_MM_NOM);
                $OCR_TIPO   = trim($OCR_TIPO);
                $OCR_DIGITO = trim($OCR_DIGITO);


                // Datos
                $medidor  = (string)($cls->NUMMED1 ?? '');
                $cuenta   = (string)($cls->NumCta ?? '');
                $tarifa   = (string)($cls->TARIFA ?? '');
                $periodo  = trim((string)($cls->FECDESDE ?? '').' - '.(string)($cls->FECHASTA ?? ''));

                $direccion = trim(
                    (string)($cls->DIRECCR ?? '') . ', ' .
                    (string)($cls->NOMPOBR ?? '') . ', ' .
                    (string)($cls->NOMESTR ?? '')
                );

                $subtotal = (float)($cls->SubTotal ?? 0);
                $iva      = (float)($cls->IMPIVA ?? 0);
                $total    = (float)($cls->CargosCreditos ?? 0);

                // ============================
                //   4) Evitar duplicados
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

                if ($existente) {

                    if ($forceOverwrite) {


                        $existente->update([
                            'medidor'   => $medidor,
                            'tarifa'    => $tarifa,
                            'direccion' => $direccion,
                            'subtotal'  => $subtotal,
                            'iva'       => $iva,
                            'total'     => $total,
                            'servicio_id' => $servicio_id,
                            'periodo_id'  => $periodo_id,
                        ]);

                        $resultados[] = [
                            'status'  => 'updated',
                            'msg'     => 'Actualizado',
                            'file'    => $base,
                            'rpu'     => $rpu,
                            'periodo' => $periodo,

                            'archivo' => $base,
                            'success' => true,
                            'mensaje' => 'Actualizado'
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
                            'mensaje' => 'Duplicado omitido'
                        ];
                    }

                    continue;
                }

                // ============================
                //   5) Crear nuevo recibo
                // ============================

                Recibo::create([
                    'rpu'        => $rpu,
                    'medidor'    => $medidor,
                    'cuenta'     => $cuenta,
                    'tarifa'     => $tarifa,
                    'periodo'    => $periodo,
                    'direccion'  => $direccion,
                    'subtotal'   => $subtotal,
                    'iva'        => $iva,
                    'total'      => $total,
                    'servicio_id' => $servicio_id,
                    'periodo_id'  => $periodo_id,
                ]);

                $resultados[] = [
                    'status'  => 'created',
                    'msg'     => 'Creado',
                    'file'    => $base,
                    'rpu'     => $rpu,
                    'periodo' => $periodo,

                    'archivo' => $base,
                    'success' => true,
                    'mensaje' => 'Creado'
                ];

            } catch (\Throwable $ex) {

                // Error global del archivo XML
                $resultados[] = [
                    'status'  => 'error',
                    'msg'     => $ex->getMessage(),
                    'file'    => $base,

                    'archivo' => $base,
                    'success' => false,
                    'error'   => $ex->getMessage()
                ];
            } finally {
                // 🧹 5. BORRAR SIEMPRE LA CARPETA TEMPORAL
//                if (is_dir($pathTemporal)) {
//                    File::deleteDirectory($pathTemporal);
//                }
            }
        }

        if (is_dir($pathTemporal)) {
            File::deleteDirectory($pathTemporal);
        }

        return [
            'zip'         => $nombreArchivo,
            'success'     => true,
            'procesados'  => $resultados
        ];
    }



}
