<?php

/**
 * YUDO Customer Portal API
 *
 * @author    Avanox Bilişim Yazılım ve Danışmanlık LTD ŞTİ
 * @copyright 2025 Avanox Bilişim
 * @license   Proprietary - All rights reserved
 */

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTwoFactorRequest extends FormRequest
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
            'user_id' => 'required|integer|exists:users,id',
            'code' => 'required|string|size:6|regex:/^[0-9]+$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Kullanıcı bilgisi gereklidir.',
            'user_id.exists' => 'Kullanıcı bulunamadı.',
            'code.required' => 'Doğrulama kodu gereklidir.',
            'code.size' => 'Doğrulama kodu 6 haneli olmalıdır.',
            'code.regex' => 'Doğrulama kodu sadece rakamlardan oluşmalıdır.',
        ];
    }
}
