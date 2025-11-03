<?php

namespace App\Http\Requests\Pinjaman;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Helpers\ResponseFormatter;

class ApprovePinjamanRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'catatan_approval' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'tanggal_mulai_cicilan' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'catatan_approval.max' => 'Catatan approval maksimal 1000 karakter',
            'tanggal_mulai_cicilan.date' => 'Format tanggal tidak valid',
            'tanggal_mulai_cicilan.after_or_equal' => 'Tanggal mulai cicilan tidak boleh di masa lalu',
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
}
