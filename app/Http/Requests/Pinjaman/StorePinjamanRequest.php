<?php

namespace App\Http\Requests\Pinjaman;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use App\Helpers\ResponseFormatter;

class StorePinjamanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization di-handle di controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'exists:users,id',
            ],
            'jumlah_pinjaman' => [
                'required',
                'numeric',
                'min:100000', // Minimal Rp 100.000
                'max:999999999.99',
            ],
            'tenor_bulan' => [
                'required',
                'integer',
                Rule::in([6, 12, 24]), // Hanya boleh 6, 12, atau 24 bulan
            ],
            'tujuan_pinjaman' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'bunga_persen' => [
                'nullable',
                'numeric',
                'min:0',
                'max:10', // Maksimal 10% (safety limit)
            ],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID wajib diisi',
            'user_id.exists' => 'User tidak ditemukan',
            'jumlah_pinjaman.required' => 'Jumlah pinjaman wajib diisi',
            'jumlah_pinjaman.numeric' => 'Jumlah pinjaman harus berupa angka',
            'jumlah_pinjaman.min' => 'Jumlah pinjaman minimal Rp 100.000',
            'jumlah_pinjaman.max' => 'Jumlah pinjaman terlalu besar',
            'tenor_bulan.required' => 'Tenor wajib diisi',
            'tenor_bulan.integer' => 'Tenor harus berupa angka bulat',
            'tenor_bulan.in' => 'Tenor hanya boleh 6, 12, atau 24 bulan',
            'tujuan_pinjaman.max' => 'Tujuan pinjaman maksimal 1000 karakter',
            'bunga_persen.numeric' => 'Bunga persen harus berupa angka',
            'bunga_persen.min' => 'Bunga persen tidak boleh negatif',
            'bunga_persen.max' => 'Bunga persen maksimal 10%',
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
