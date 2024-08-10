<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FolderModelRequest extends FormRequest
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
            'folder_name' => 'required|string|max:255',
            'id_user' => 'required|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'folder_name.required' => 'The folder name is required',
            'folder_name.string' => 'The folder name must be a string',
            'folder_name.max' => 'The folder name may not be greater than 255 characters',
            'id_user.required' => 'The user ID is required',
            'id_user.exists' => 'The user ID must exist in the users table',
        ];
    }
}
