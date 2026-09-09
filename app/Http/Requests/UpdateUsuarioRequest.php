<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->nombre === 'Gerente de Bodega';
    }

    public function rules(): array
    {
        $usuarioId = $this->route('usuario')->id;

        return [
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuarioId)],
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe otro usuario registrado con ese correo.',
        ];
    }
}
