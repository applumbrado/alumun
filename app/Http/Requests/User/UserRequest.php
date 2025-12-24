<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){
        return [
            'username' => [
                'required',
                'string',
                'min:4',
                'max:50',
                'unique:users,username,'.$this->id
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                'unique:users,email,'.$this->id
            ],
            'nombre' => ['required', 'string', 'min:2'],
            'ap_paterno' => ['required', 'string', 'min:2'],
            'password' => ['nullable', Password::min(8)],
            'curp' => [
                'required',
                'unique:users,curp,'.$this->id
            ],
            'genero' => 'required|in:0,1,2',
        ];
    }


    public function managed ($User) {

        $ude = [
            'lugar_nacimiento' => strtoupper(trim($this->user_data_extend['lugar_nacimiento'] ?? '')),
            'ocupacion'      => strtoupper(trim($this->user_data_extend['ocupacion'] ?? '')),
            'profesion'      => strtoupper(trim($this->user_data_extend['profesion'] ?? '')),
            'lugar_trabajo'  => strtoupper(trim($this->user_data_extend['lugar_trabajo'] ?? '')),
            'nacionalidad'  => strtoupper(trim($this->user_data_extend['nacionalidad'] ?? '')),
        ];

        $ua = [
            'calle' => strtoupper(trim($this->user_address['calle'] ?? '')),
            'num_ext'      => strtoupper(trim($this->user_address['num_ext'] ?? '')),
            'num_int'      => strtoupper(trim($this->user_address['num_int'] ?? '')),
            'colonia'  => strtoupper(trim($this->user_address['colonia'] ?? '')),
            'municipio' => strtoupper(trim($this->user_address['municipio'] ?? '')),
            'estado'      => strtoupper(trim($this->user_address['estado'] ?? '')),
            'pais'      => strtoupper(trim($this->user_address['pais'] ?? 'México')),
            'cp'  => strtoupper(trim($this->user_address['cp'] ?? '')),
        ];


        if ($this->id <= 0) {
           $item = [
               'username' => trim($this->username),
               'password' => bcrypt(trim($this->username)),
               'email' => trim($this->email) ?? '',
               'nombre' => strtoupper(trim($this->nombre)) ?? '',
               'ap_paterno' => strtoupper(trim($this->ap_paterno)) ?? '',
               'ap_materno' => strtoupper(trim($this->ap_materno)) ?? '',
               'curp' => strtoupper(trim($this->curp)) ?? '',
               'emails' => strtolower(trim($this->emails)) ?? '',
               'celulares' => strtoupper(trim($this->celulares)) ?? '',
               'telefonos' => strtoupper(trim($this->telefonos)) ?? '',
               'fecha_nacimiento' => $this->fecha_nacimiento ?? now(),
               'genero' => (int) ($this->genero ?? 0),
           ];
           $User = User::create($item);

           $User->user_address()->create($ua);
           $User->user_data_extend()->create($ude);

            $role_id = (int) ($this->role_id ?? 12);
            $User->roles()->attach($role_id);

        }else{

           $item = [
               'username' => trim($this->username),
               'password' => bcrypt(trim($this->username)),
               'email' => trim($this->email) ?? '',
               'nombre' => strtoupper(trim($this->nombre)) ?? '',
               'ap_paterno' => strtoupper(trim($this->ap_paterno)) ?? '',
               'ap_materno' => strtoupper(trim($this->ap_materno)) ?? '',
               'curp' => strtoupper(trim($this->curp)) ?? '',
               'emails' => strtoupper(trim($this->emails)) ?? '',
               'celulares' => strtoupper(trim($this->celulares)) ?? '',
               'telefonos' => strtoupper(trim($this->telefonos)) ?? '',
               'fecha_nacimiento' => $this->fecha_nacimiento ?? now(),
               'genero' => (int) ($this->genero ?? 0),
           ];

           $User->update($item);

           if ( $User->user_address()->exists() ) {
//               dd($ude);
               $User->user_address()->update($ua);
               $User->user_data_extend()->update($ude);
           }else{
               $User->user_address()->create($ua);
               $User->user_data_extend()->create($ude);
           }

       }
       return $User;
    }



}
