<?php

namespace App\Http\Requests\Cicilan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use App\Helpers\ResponseFormatter;

class BayarCicilanRequest extends FormRequest
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
            'jumlah_bayar' => [
                'required',
                'numeric',
                'min:1000', // Minimal Rp 1.000
                'max:999999999.99',
            ],
            'metode_bayar' => [
                'required',
                Rule::in(['tunai', 'transfer', 'lainnya']),
            ],
            'nomor_referensi' => [
                'nullable',
                'string',
                'max:100',
            ],
            'keterangan' => [
                'nullable',
                'string',
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
            'jumlah_bayar.required' => 'Jumlah pembayaran wajib diisi',
            'jumlah_bayar.numeric' => 'Jumlah pembayaran harus berupa angka',
            'jumlah_bayar.min' => 'Jumlah pembayaran minimal Rp 1.000',
            'jumlah_bayar.max' => 'Jumlah pembayaran terlalu besar',
            'metode_bayar.required' => 'Metode pembayaran wajib diisi',
            'metode_bayar.in' => 'Metode pembayaran tidak valid (tunai/transfer/lainnya)',
            'nomor_referensi.max' => 'Nomor referensi maksimal 100 karakter',
            'keterangan.max' => 'Keterangan maksimal 1000 karakter',
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
