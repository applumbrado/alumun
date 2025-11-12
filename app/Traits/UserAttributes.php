<?php

namespace App\Traits ;

use App\Models\Catalogos\Ciclo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait UserAttributes{


    protected $disk1 = 'profile';

    public function isVerifyMail(): bool{
        return $this->hasVerifiedEmail();
    }


    public function isRole($role): bool{
        return $this->hasRole($role);
    }

    public function isPermission($permissions, string $guard = null): bool{
        $ar = explode("|", $permissions);
        $IsExist = false;
        foreach ($this->permissions as $p){
            foreach ($ar as $key => $value){
                if ( $p->name == $ar[$key] ){
                    $IsExist = true;
                }
            }
        }
        return $IsExist;

    }


    public function getRoleIdStrArrayAttribute(){
        return $this->roles()->allRelatedIds('id')->implode('|','id');
    }

    public function getRoleNameStrArrayAttribute(){
        return $this->roles()->pluck('name')->implode('|','name');
    }

    public function hasDependencia($dependencia): bool{
        return $this->dependencias->contains('id',$dependencia);
    }

    public function getDependenciaArrayAttribute(){
        return $this->dependencias()->pluck('dependencia')->implode('|','name');
    }

    public function getDependenciaAbreviaturaArrayAttribute(){
        return $this->dependencias()->pluck('abreviatura')->implode('|','name');
    }

    public function getDependenciaIdArrayAttribute(){
        return $this->dependencias()->pluck('dependencia_id')->toArray();
    }

    public function getFullNameAttribute(){
        return trim(
            $this->ap_paterno . ' ' .
            $this->ap_materno . ' ' .
            $this->nombre
        );
    }

    public function getFullNameWithUsernameAttribute() {
        return "{$this->ap_paterno} {$this->ap_materno} {$this->nombre} - {$this->username}";
    }

    public function getFullNameWithUsernameDependenciaAttribute() {
        return "{$this->ap_paterno} {$this->ap_materno} {$this->nombre} - {$this->id} - {$this->username} - {$this->dependencia_abreviatura_array}";
    }

    public function getFullUbicationAttribute(){
        return $this->user_address->calle.' '.
            $this->user_address->num_ext.' '.
            $this->user_address->num_int.' '.
            $this->user_address->colonia.' '.
            $this->user_address->comunidad.' '.
            $this->user_address->ciudad.' '.
            $this->user_address->municipio.' '.
            $this->user_address->estado.' '.
            $this->user_address->cp;
    }


    public function getStrGeneroAttribute() {
        $Gen = "NO BINARIO";
        switch ($this->genero){
            case 0:
                $Gen = "FEMENINO";
                break;
            case 1:
                $Gen = "MASCULINO";
                break;
        }
        return $Gen;
    }

    public function getTipoImageProfile($tipo=""){
        switch ($tipo){
            case 'thumb':
                return $this->filename_thumb;
                break;
            case 'png':
                return $this->filename_png;
                break;
            default :
                return $this->filename;
                break;
        }
    }

    // Get Image
    public function getPathImageProfileAttribute(){
        return $this->getImage("");
    }

    // Get Image Thumbnail
    public function getPathImageThumbProfileAttribute(){
        return $this->getImage("thumb");
    }

    // Get Image PNG
    public function getPathImagePNGProfileAttribute(){
        return $this->getImage("png");
    }

    public function getImage($tipoImage="thumb"){
        $ret = '/images/web/file-not-found.png';
        if ( $this->IsEmptyPhoto() ){
            if ( $this->IsFemale() ){
                $ret = '/images/web/empty_user_female.png';
            }else{
                $ret = '/images/web/empty_user_male.png';
            }
        }else{
            $tFile = $this->getTipoImageProfile($tipoImage);
            $exists = Storage::disk($this->disk1)->exists($tFile);
            $ret = $exists
                ? "/storage/".$this->disk1."/".$tFile
                : $ret;
        }
        return $ret;

    }

    public function getHomeAttribute($withSlash=false): string {

        $slash = "/";
        if (Auth::user()->isRole('Admin|SysOp')){
            $home = 'home';
        }  elseif (Auth::user()->isRole('DELEGADO|CIUDADANO')) {
            $home = 'home-ciudadano';
        } else {
            $home = 'home';
        }
        return $withSlash ? $slash . $home : $home;
    }

    public static function getUsernameNext( string $Abreviatura ): array{
        $Abreviatura = $Abreviatura == "0" ? "inv" : $Abreviatura;
        $next_id=DB::select("SELECT NEXTVAL('users_id_seq')");
        $Id = (int)$next_id['0']->nextval;
        DB::select("SELECT SETVAL('users_id_seq',".($Id-1).")" );
        $Id = str_pad($Id,6,'0',0);
        $role = Role::query()->where('abreviatura',$Abreviatura)->first();
        return ['username'=>$role->abreviatura.$Id,'role_id'=>$role->id];
    }

    public function getTelefonosCelularesEmailsAttribute(){
        return trim($this->celulares)."; ".trim($this->telefonos)."; ".trim($this->emails);
    }


    public function getIsEnlaceDependenciaAttribute(){
        $IsEnlace =$this->isRole('ENLACE');
        $attributes['dependencia_id'] = ["0"];
        $DependenciaArray = '';
        IF ($IsEnlace) {
            $DependenciaIdArray = $this->DependenciaIdArray;
            $attributes['dependencia_id'] = $DependenciaIdArray;
        }
//        dd($attributes['dependencia_id']);
        return $attributes['dependencia_id'];

    }

    public function scopeAlumno(Builder $query): Builder{
        return $query->whereHas('roles', fn($q) =>
        $q->where('name', 'ALUMNO')
        );
    }

    public function scopeConGrupoCiclo($query, $cicloId, $grupoId){
        return $query->whereHas("grupos", function ($q) use ($cicloId, $grupoId) {
            $q->where('ciclo_id', $cicloId)->where('grupo_id', $grupoId);
        });
    }


}
