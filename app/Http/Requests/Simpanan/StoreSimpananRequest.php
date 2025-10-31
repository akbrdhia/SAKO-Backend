<?php

namespace App\Http\Requests\Simpanan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseFormatter;
use Illuminate\Validation\Rule;

class StoreSimpananRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya kasir dan admin yang bisa input simpanan
        return in_array(auth()->user()->role, ['kasir', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'jenis' => ['required', Rule::in(['pokok', 'wajib', 'sukarela'])],
            'jumlah' => 'required|numeric|min:1000',
            'tanggal' => 'required|date|before_or_equal:today',
            'keterangan' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Anggota wajib dipilih',
            'user_id.exists' => 'Anggota tidak ditemukan',
            'jenis.required' => 'Jenis simpanan wajib dipilih',
            'jenis.in' => 'Jenis simpanan tidak valid',
            'jumlah.required' => 'Jumlah simpanan wajib diisi',
            'jumlah.numeric' => 'Jumlah simpanan harus berupa angka',
            'jumlah.min' => 'Jumlah simpanan minimal Rp 1.000',
            'tanggal.required' => 'Tanggal wajib diisi',
            'tanggal.date' => 'Format tanggal tidak valid',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh di masa depan',
            'keterangan.max' => 'Keterangan maksimal 500 karakter',
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
            ResponseFormatter::forbidden('Anda tidak memiliki akses untuk menambah simpanan')
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Set tanggal to today if not provided
        if (!$this->has('tanggal')) {
            $this->merge([
                'tanggal' => now()->toDateString(),
            ]);
        }
    }
}
