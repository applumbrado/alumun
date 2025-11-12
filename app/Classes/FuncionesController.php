<?php

namespace App\Classes;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;


class FuncionesController extends Controller {


    public function __construct()
    {
        // $this->middleware('auth');
    }
    //

    public static function setConfigOne(){
        ini_set('max_execution_time', 600);
        @ini_set( 'upload_max_size' , '32768M' );
        @ini_set( 'post_max_size', '32768M');
        @ini_set( 'max_execution_time', '256000000' );
        @ini_set('memory_limit', '-1');
        return true;
    }

    public function toMayus($str=""){
        return strtr(strtoupper($str), "áéíóúñ", "ÁÉÍÓÚÑ");
    }

    public function showFile($root="/archivos/",$archivo=""){
        $public_path = public_path();
        $url = $public_path."/storage/".$root.$archivo;
        if (Storage::exists($archivo))
        {
            return response()->download($url);
        }
        abort(404);
    }

    public function str_sanitizer($filters){
        $arr = array(' la ',' las ',' lo ',' los ',' de ',' del ',' fracc ',' col ',' col. ',' r/a ',' ria ',' ria. ',' conj ',' conj. ',' priv ',' priv. ',' av ',' av. ',' ave ',' ave. ',' carr ',' carr. ',' colonia ',' pob ',' pob. ',' cda ',' cda. ', 'méxico', 'tab.', 'tab,');
        foreach($arr as $fil){
            $filters = str_replace($fil, ' ', $filters);
        }
        return $filters;
    }


    public function string_to_tsQuery(String $string, String $type){
        $string = str_replace(array(', ', ','), ' ', $string);

        $str = explode(' ',$string);
        //dd($str);
        $string = '';
        $i = 1;
        foreach ($str as $value){
            if ( strlen($value) >= 4 ){
                $vector = '';
                if ($string!=''){
                    $vector = $type;
                }
                if ( ! str_contains($string,$vector.$value) ){
//                if ( strpos($string, $vector.$value) == false)  {
                    $string .= $vector . $value;
                }
            }
            ++$i;
        }
        return $string;
    }

    // get IP, Host or IdEmp
    public function getIHE($type=0){
        switch ($type){
            case 0:
                return 1;
                break;
            case 1:
                return $_SERVER['REMOTE_ADDR'];
                break;
            case 2:
                return gethostbyaddr($_SERVER['REMOTE_ADDR']);
                break;
        }
    }

    public function setDateTo6Digit($fecha){
        if(is_null($fecha)){
            $fecha = Carbon::today()->toDateString();
        }
//        dd(Carbon::now());
        $fecha = str_replace(array('20', '-'), '', $fecha);
        return $fecha;
    }

    public function getFechaFromNumeric($number){
        return
            '20'.substr($number,0,2).'-'.
            substr($number,2,2).'-'.
            substr($number,4,2)
            ;
    }

    public function fechaEspanol($f){
        $f = explode('-',substr($f,0,10));
        return $f[2].'-'.$f[1].'-'.$f[0];
    }

    public function fechaIngles($f){
        $f = explode('-',$f);
        return $f[2].'-'.$f[1].'-'.$f[0];
    }


    public function fechaEspanolComplete($f,$type=false){
        $f = explode('-',substr($f,0,10));
        $f =  $f[2].'-'.$f[1].'-'.$f[0];
        return !$type ? $f.' 00:00:00' : $f.' 23:59:59';
    }

    public function fechaDateTimeFormat($f,$type=false){
        $f = explode('-',substr($f,0,10));
        $f = $f[0].'-'.$f[1].'-'.$f[2];
        return !$type ? $f.' 00:00:00' : $f.' 23:59:59';
    }


    public function getDatesFromMonthNow(){
        $f1 = Carbon::now()->format('Y-m').'-'.'01';
        $f2 = Carbon::now()->endOfMonth()->toDateString();
        return ["fecha_inicial" => $f1." 00:00:00", "fecha_final" => $f2." 23:59:59"];
    }

    public function validImage($model, $storage, $root, $type=1){
        $ext = config('alumun.images_type_extension');
//        for ($i=0;$i < count($ext);$i++){
        foreach ($ext as $iValue) {
            $p1 = $model->id.'.'. $iValue;
            $p2 = '_'.$model->id.'.png';
            $p3 = '_thumb_'.$model->id.'.png';
            $e1 = Storage::disk($storage)->exists($p1);
            if ($e1) {
                switch ($type) {
                    case 1:
                        $model->update([
                            'root'              =>  $root,
                            'filename'          =>  $p1,
                            'filename_png'      =>  $p2,
                            'filename_thumb'    =>  $p3
                        ]);
                        break;
                }
            }
        }
    }

    public function deleteImages($model,$storage){
        $ext = ['jpg','jpeg','gif','png','JPG','JPEG','GIF','PNG','xls','xlsx','doc','docx','ppt','pptx','txt','mp4','pages','key','numbers'];
        for ($i=0;$i<4;$i++){
            $p1 = $model->id.'.'.$ext[$i];
            $e1 = Storage::disk($storage)->exists($p1);
            if ($e1) {
                Storage::disk($storage)->delete($p1);
            }
        }
    }

    public function fitImage($imagePath, $filename, $W, $H, $IsRounded, $disk="profile", $profile_root="PROFILE_ROOT", $extension="png"){
        try{
            $manager = new ImageManager(Driver::class);

            // reading jpeg image
            $image = $manager->read($imagePath);
            if ($IsRounded){
                $image->scale($W, $H);
                $image->drawCircle( $W/2, $H/2, function ($draw) {
                    $draw->radius(150);
                    $draw->background('#fff');
                });
                $filePath = public_path(env($profile_root)).'/'.$filename;
                $image->save($filePath);
                Storage::disk($disk)->put($filename, $image);
                if (File::exists($filePath)) {
//                    unlink($filePath);
                }
            }else{
                $image = Storage::disk($disk)->put($filename, $image);
            }
        }catch (Exception $e){
            return "Error: " . $e->getMessage();
        }
        return $image;
    }




    public function deleteImageDropZone($image,$storage){
        $e1 = Storage::disk($storage)->exists($image);
        if ($e1) {
            Storage::disk($storage)->delete($image);
        }
    }

    public static function getIp(){
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }
        return request()->ip();
    }

    public static function remoteFileExists($url) {
        if (str_contains($url, "localhost")){
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_NOBODY, true);
            $result = curl_exec($curl);
            $ret = false;
            if ($result !== false) {
                $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($statusCode == 200) {
                    $ret = true;
                }
            }
            curl_close($curl);
            return $ret;
        }else{
            return file_get_contents($url,0,null,0,1);
        }
    }

    private function getRedirect(Request $request){
        $role1 = $request->user()->hasRole('Admin|SysOp|USER_OPERATOR_SIAC|USER_OPERATOR_ADMIN|ENLACE|USER_ARCHIVO_CAP|USER_ARCHIVO_ADMIN');
        $role2 = $request->user()->hasRole('CIUDADANO|DELEGADO');
        if ($role1) {
            return 'home';
        } else if($role2) {
            return 'home-ciudadano';
        } else {
            return 'home';
        }
    }


    public static function getTotalFromArray($items){
        $total_ticket = 0;
        foreach ($items as $venta) {
//            dd($venta);
            $venta = (object) $venta;
            $total_ticket += $venta->total;
        }
        return $total_ticket;
    }

    public static function getNumeroDePagoPorMes(): int{
        $paymentMap = [
            '09' => 1,  // Septiembre
            '10' => 2,  // Octubre
            '11' => 3,  // Noviembre
            '12' => 4,  // Diciembre
            '01' => 5,  // Enero
            '02' => 6,  // Febrero
            '03' => 7,  // Marzo
            '04' => 8,  // Abril
            '05' => 9,  // Mayo
            '06' => 10, // Junio
            '07' => 11, // Julio
            '08' => 12, // Agosto
        ];
        $mesActual = Carbon::now()->format('m');
        return $paymentMap[$mesActual] ?? 0;
    }

    public static function elPagoEstaDentroDelDiaLimite(): bool{
        $currentDay = Carbon::now()->day;
        return $currentDay >= 1 && $currentDay <= 10;
    }






}
