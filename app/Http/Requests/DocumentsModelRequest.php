<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentsModelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_content' => 'required|file',
            'id_user' => 'required|exists:users,id',
            'id_subfolder' => 'required|exists:sub_folder_models,id',
        ];
    }

    public function messages()
    {
        return [
            'document_content.required' => 'El contenido del documento es requerido.',
            'id_user.required' => 'El ID de usuario es requerido.',
            'id_user.exists' => 'El usuario especificado no existe en la base de datos.',
            'id_subfolder.required' => 'El ID de la subcarpeta es requerido.',
            'id_subfolder.exists' => 'La subcarpeta especificada no existe en la base de datos.',
        ];
    }
}
