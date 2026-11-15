<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-clinic') ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'fecha_nacimiento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'alergia' => ['nullable', 'array', 'max:20'],
            'alergia.*' => ['nullable', 'string', 'max:100'],
            'enfermedades' => ['required', 'string', 'max:255'],
            'medico_id' => ['required', 'array', 'min:1', 'max:20'],
            'medico_id.*' => ['required', 'integer', 'distinct', 'exists:medicos,id'],
            'url' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', Rule::dimensions()->maxWidth(3000)->maxHeight(3000)],
        ];
    }

    public function attributes(): array
    {
        return ['medico_id' => 'médicos', 'medico_id.*' => 'médico', 'url' => 'fotografía', 'alergia.*' => 'alergia'];
    }
}
