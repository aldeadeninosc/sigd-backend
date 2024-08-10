<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubFolderModelRequest extends FormRequest
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
            'subfolder_name' => 'required|string|max:255',
            'id_folder' => 'required|exists:folder_models,id',
        ];
    }

    public function messages()
    {
        return [
            'subfolder_name.required' => 'El nombre de la subcarpeta es requerido.',
            'id_folder.required' => 'El ID de la carpeta es requerido.',
            'id_folder.exists' => 'La carpeta especificada no existe en la base de datos.',
        ];
    }
}
