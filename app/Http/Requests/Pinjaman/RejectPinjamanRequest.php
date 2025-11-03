<?php

namespace App\Http\Requests\Pinjaman;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Helpers\ResponseFormatter;

class RejectPinjamanRequest extends FormRequest
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
            'catatan_penolakan' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'catatan_penolakan.required' => 'Catatan penolakan wajib diisi',
            'catatan_penolakan.string' => 'Catatan penolakan harus berupa teks',
            'catatan_penolakan.min' => 'Catatan penolakan minimal 10 karakter',
            'catatan_penolakan.max' => 'Catatan penolakan maksimal 1000 karakter',
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
