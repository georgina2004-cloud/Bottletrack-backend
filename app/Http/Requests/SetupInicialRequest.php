<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetupInicialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_licoreria' => 'required|string|max:150',
            'eslogan' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:20',
            'email_empresa' => 'nullable|email|max:150',
            'direccion' => 'nullable|string|max:255',
            'moneda' => 'required|string|max:10',
            'color_primario' => 'required|string|max:7',
            'logo' => 'nullable|image|max:2048',
            'admin_name' => 'required|string|max:150',
            'admin_email' => 'required|email|max:150|unique:users,email',
            'admin_password' => 'required|string|min:6',
        ];
    }
}
