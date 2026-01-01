<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestRequest extends FormRequest
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
            'request_type' => ['required', 'integer', 'in:1,2,3'],
            'customer_reference_code' => ['nullable', 'string', 'max:100'],
            'customer_mold_code' => ['nullable', 'string', 'max:100'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'expected_delivery_date' => ['nullable', 'date', 'after:today'],
            'priority' => ['nullable', 'integer', 'in:1,2,3,4'],

            // Parça bilgileri
            'parca_agirligi' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'et_kalinligi' => ['required', 'numeric', 'min:0.1', 'max:100'],

            // Malzeme bilgileri
            'malzeme' => ['required', 'string', 'in:' . $materials],
            'katki_var_mi' => ['nullable', 'boolean'],
            'katki_turu' => ['nullable', 'required_if:katki_var_mi,true', 'string', 'in:' . $additives],
            'katki_orani' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Kalıp bilgileri
            'kalip_x' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            'kalip_y' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            'kalip_d' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            'kalip_l' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            // Meme bilgileri
            'goz_sayisi' => ['required', 'integer', 'min:1', 'max:256'],
            'meme_sayisi' => ['required', 'integer', 'min:1', 'max:256'],
            'meme_tipi' => ['required', 'string', 'in:' . $nozzleTypes],

            // Dosya yükleme - extension bazlı kontrol (CAD dosyaları için MIME tanınmıyor)
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => [
                'file',
                'max:51200', // 50MB
                function ($attribute, $value, $fail) {
                    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'dwg', 'step', 'stp', 'iges', 'igs', 'x_t', 'ai', 'psd', 'zip', 'rar'];
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (!in_array($extension, $allowedExtensions)) {
                        $fail('İzin verilen formatlar: PDF, JPG, PNG, DWG, STEP, IGES, X_T, AI, PSD, ZIP, RAR');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.required' => 'Talep tipi seçilmelidir.',
            'request_type.in' => 'Geçersiz talep tipi.',

            'parca_agirligi.required' => 'Parça ağırlığı gereklidir.',
            'parca_agirligi.numeric' => 'Parça ağırlığı sayısal olmalıdır.',
            'parca_agirligi.min' => 'Parça ağırlığı 0.01 gramdan küçük olamaz.',

            'et_kalinligi.required' => 'Et kalınlığı gereklidir.',
            'et_kalinligi.numeric' => 'Et kalınlığı sayısal olmalıdır.',
            'et_kalinligi.min' => 'Et kalınlığı 0.1 mm\'den küçük olamaz.',

            'malzeme.required' => 'Malzeme seçilmelidir.',
            'malzeme.in' => 'Geçersiz malzeme.',

            'katki_turu.required_if' => 'Katkı maddesi seçilmelidir.',
            'katki_turu.in' => 'Geçersiz katkı maddesi.',

            'kalip_x.required' => 'Kalıp X boyutu gereklidir.',
            'kalip_x.numeric' => 'Kalıp X boyutu sayısal olmalıdır.',

            'kalip_y.required' => 'Kalıp Y boyutu gereklidir.',
            'kalip_y.numeric' => 'Kalıp Y boyutu sayısal olmalıdır.',

            'goz_sayisi.required' => 'Göz sayısı gereklidir.',
            'goz_sayisi.integer' => 'Göz sayısı tam sayı olmalıdır.',
            'goz_sayisi.min' => 'Göz sayısı en az 1 olmalıdır.',

            'meme_sayisi.required' => 'Meme sayısı gereklidir.',
            'meme_sayisi.integer' => 'Meme sayısı tam sayı olmalıdır.',
            'meme_sayisi.min' => 'Meme sayısı en az 1 olmalıdır.',

            'meme_tipi.required' => 'Meme tipi seçilmelidir.',
            'meme_tipi.in' => 'Geçersiz meme tipi.',

            'expected_delivery_date.after' => 'Beklenen teslim tarihi bugünden sonra olmalıdır.',

            'files.array' => 'Dosyalar dizi formatında olmalıdır.',
            'files.max' => 'Tek seferde en fazla 10 dosya yüklenebilir.',
            'files.*.file' => 'Geçerli bir dosya seçilmelidir.',
            'files.*.max' => 'Dosya boyutu maksimum 50 MB olabilir.',
            'files.*.mimes' => 'İzin verilen formatlar: PDF, JPG, PNG, DWG, STEP, IGES, X_T, AI, PSD, ZIP, RAR',
        ];
    }

    public function attributes(): array
    {
        return [
            'request_type' => 'Talep Tipi',
            'customer_reference_code' => 'Müşteri Referans Kodu',
            'customer_mold_code' => 'Müşteri Kalıp Kodu',
            'customer_notes' => 'Notlar',
            'expected_delivery_date' => 'Beklenen Teslim Tarihi',
            'priority' => 'Öncelik',
            'parca_agirligi' => 'Parça Ağırlığı',
            'et_kalinligi' => 'Et Kalınlığı',
            'malzeme' => 'Malzeme',
            'katki_var_mi' => 'Katkı Maddesi Var mı',
            'katki_turu' => 'Katkı Türü',
            'katki_orani' => 'Katkı Oranı',
            'kalip_x' => 'Kalıp X',
            'kalip_y' => 'Kalıp Y',
            'kalip_d' => 'Kalıp D',
            'kalip_l' => 'Kalıp L',
            'goz_sayisi' => 'Göz Sayısı',
            'meme_sayisi' => 'Meme Sayısı',
            'meme_tipi' => 'Meme Tipi',
            'files' => 'Dosyalar',
            'files.*' => 'Dosya',
        ];
    }
}
