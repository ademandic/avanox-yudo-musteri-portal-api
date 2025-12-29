<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $materials = implode(',', array_keys(config('portal.materials')));
        $additives = implode(',', array_keys(config('portal.additives')));
        $nozzleTypes = implode(',', array_keys(config('portal.nozzle_types')));

        return [
            // Talep bilgileri
            'customer_reference_code' => ['nullable', 'string', 'max:100'],
            'customer_mold_code' => ['nullable', 'string', 'max:100'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'expected_delivery_date' => ['nullable', 'date', 'after:today'],
            'priority' => ['nullable', 'integer', 'in:1,2,3,4'],

            // Parça bilgileri
            'parca_agirligi' => ['nullable', 'numeric', 'min:0.01', 'max:99999'],
            'et_kalinligi' => ['nullable', 'numeric', 'min:0.1', 'max:100'],

            // Malzeme bilgileri
            'malzeme' => ['nullable', 'string', 'in:' . $materials],
            'katki_var_mi' => ['nullable', 'boolean'],
            'katki_turu' => ['nullable', 'string', 'in:' . $additives],
            'katki_orani' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Kalıp bilgileri
            'kalip_x' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            'kalip_y' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            'kalip_d' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            'kalip_l' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            'kalip_z' => ['nullable', 'numeric', 'min:1', 'max:9999'],

            // Meme bilgileri
            'goz_sayisi' => ['nullable', 'integer', 'min:1', 'max:256'],
            'meme_sayisi' => ['nullable', 'integer', 'min:1', 'max:256'],
            'meme_tipi' => ['nullable', 'string', 'in:' . $nozzleTypes],
        ];
    }

    public function messages(): array
    {
        return [
            'parca_agirligi.numeric' => 'Parça ağırlığı sayısal olmalıdır.',
            'parca_agirligi.min' => 'Parça ağırlığı 0.01 gramdan küçük olamaz.',

            'et_kalinligi.numeric' => 'Et kalınlığı sayısal olmalıdır.',
            'et_kalinligi.min' => 'Et kalınlığı 0.1 mm\'den küçük olamaz.',

            'malzeme.in' => 'Geçersiz malzeme.',
            'katki_turu.in' => 'Geçersiz katkı maddesi.',

            'kalip_x.numeric' => 'Kalıp X boyutu sayısal olmalıdır.',
            'kalip_y.numeric' => 'Kalıp Y boyutu sayısal olmalıdır.',

            'goz_sayisi.integer' => 'Göz sayısı tam sayı olmalıdır.',
            'goz_sayisi.min' => 'Göz sayısı en az 1 olmalıdır.',

            'meme_sayisi.integer' => 'Meme sayısı tam sayı olmalıdır.',
            'meme_sayisi.min' => 'Meme sayısı en az 1 olmalıdır.',

            'meme_tipi.in' => 'Geçersiz meme tipi.',

            'expected_delivery_date.after' => 'Beklenen teslim tarihi bugünden sonra olmalıdır.',
        ];
    }
}
