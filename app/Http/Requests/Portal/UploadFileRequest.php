<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $extensions = implode(',', config('portal.upload.allowed_extensions'));
        $maxSize = config('portal.upload.max_size') / 1024; // KB cinsinden

        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => [
                'required',
                'file',
                'max:' . $maxSize,
                'mimes:' . $extensions,
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        $maxSizeMB = config('portal.upload.max_size') / 1048576;
        $extensions = implode(', ', config('portal.upload.allowed_extensions'));

        return [
            'files.required' => 'En az bir dosya seçilmelidir.',
            'files.array' => 'Dosyalar dizi formatında olmalıdır.',
            'files.min' => 'En az 1 dosya yüklenmelidir.',
            'files.max' => 'Tek seferde en fazla 10 dosya yüklenebilir.',

            'files.*.required' => 'Dosya gereklidir.',
            'files.*.file' => 'Geçerli bir dosya seçilmelidir.',
            'files.*.max' => "Dosya boyutu maksimum {$maxSizeMB} MB olabilir.",
            'files.*.mimes' => "İzin verilen dosya formatları: {$extensions}",

            'description.max' => 'Açıklama en fazla 255 karakter olabilir.',
        ];
    }

    public function attributes(): array
    {
        return [
            'files' => 'Dosyalar',
            'files.*' => 'Dosya',
            'description' => 'Açıklama',
        ];
    }
}
