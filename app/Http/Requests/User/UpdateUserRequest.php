<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseFormatter;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        $targetUser = $this->route('id');

        // Anggota cuma bisa update diri sendiri
        if ($user->role === 'anggota') {
            return $user->id == $targetUser;
        }

        // Kasir, manajer, admin bisa update
        return in_array($user->role, ['kasir', 'manajer', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'nik' => [
                'sometimes',
                'string',
                'size:16',
                Rule::unique('users', 'nik')->ignore($userId)
            ],
            'nama' => 'sometimes|string|max:255',
            'alamat' => 'nullable|string',
            'no_hp' => 'sometimes|string|max:20',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'password' => 'sometimes|string|min:6',
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nik.size' => 'NIK harus 16 digit',
            'nik.unique' => 'NIK sudah terdaftar',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.min' => 'Password minimal 6 karakter',
            'status.in' => 'Status tidak valid',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ResponseFormatter::validationError($validator->errors())
        );
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            ResponseFormatter::forbidden('Anda tidak memiliki akses untuk mengubah data user ini')
        );
    }
}
