<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
//        return [
//            'rpu' => 'required|string|max:30|unique:servicios,rpu,' . $this->id,
//            'medidor' => 'string|max:30',
//            'cuenta' => 'string|max:30',
//            'tarifa' => 'string|max:6',
//            'carga_contratada' => 'string|max:6',
//            'carga_conectada' => 'string|max:6',
//            'rmu' => 'string|max:30',
//            'direccion' => 'string|max:250',
//            'ciudad' => 'string|max:150',
//            'colonia' => 'string|max:150',
//            'calle_1' => 'string|max:150',
//            'calle_2' => 'string|max:150',
//            'calle_3' => 'string|max:150',
//            'alias' => 'string|max:150',
//            'grupo_id' => 'integer|exists:grupos,id'
//        ];

        return [
            'rpu' => [
                'required',
                'string',
                'max:30',
                Rule::unique('servicios')->ignore($this->id ?: null)
            ],
            'medidor' => 'string|max:30',
            'cuenta' => 'string|max:30',
            'tarifa' => 'string|max:6',
            'carga_contratada' => 'string|max:6',
            'carga_conectada' => 'string|max:6',
            'carga_minima' => 'string|max:6',
            'carga_maxima' => 'string|max:6',
            'rmu' => 'string|max:30',
            'direccion' => 'string|max:250',
            'ciudad' => 'string|max:150',
            'colonia' => 'string|max:150',
            'calle_1' => 'string|max:150',
            'grupo_id' => 'integer|exists:grupos,id'
        ];

    }
}
