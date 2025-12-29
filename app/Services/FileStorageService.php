<?php

namespace App\Services;

use App\Models\ErpFile;
use App\Models\Job;
use App\Models\PortalUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * File Storage Service
 * Dosya yükleme ve yönetim işlemleri.
 */
class FileStorageService
{
    protected string $basePath;
    protected array $allowedExtensions;
    protected int $maxSize;

    public function __construct()
    {
        $config = config('portal.upload');
        $this->basePath = $config['storage_path'];
        $this->allowedExtensions = $config['allowed_extensions'];
        $this->maxSize = $config['max_size'];
    }

    /**
     * Dosyayı güvenli şekilde kaydet
     */
    public function store(UploadedFile $file): array
    {
        $this->validateFile($file);

        $year = date('Y');
        $month = date('m');
        $uuid = Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension());

        // Güvenli dosya adı
        $safeFileName = $uuid . '.' . $extension;
        $relativePath = "{$year}/{$month}";
        $fullDirectory = "{$this->basePath}/{$relativePath}";
        $fullPath = "{$fullDirectory}/{$safeFileName}";

        // Klasör yoksa oluştur
        if (!File::isDirectory($fullDirectory)) {
            File::makeDirectory($fullDirectory, 0755, true);
        }

        // Dosyayı kaydet
        $file->move($fullDirectory, $safeFileName);

        return [
            'original_name' => $file->getClientOriginalName(),
            'storage_path' => "{$relativePath}/{$safeFileName}",
            'full_path' => $fullPath,
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Dosyanın tam yolunu döndür (path traversal korumalı)
     */
    public function getFullPath(string $relativePath): string
    {
        // Path traversal koruması
        $fullPath = realpath($this->basePath . '/' . $relativePath);
        $basePath = realpath($this->basePath);

        // Eğer dosya yoksa veya base path dışındaysa hata fırlat
        if ($fullPath === false) {
            // Dosya henüz mevcut değilse, güvenli path oluştur
            $safePath = $this->basePath . '/' . ltrim($relativePath, '/');
            $normalizedPath = preg_replace('/\.\.\/|\.\.\\\\/', '', $safePath);

            if (strpos($normalizedPath, $this->basePath) !== 0) {
                throw new \InvalidArgumentException('Geçersiz dosya yolu');
            }

            return $normalizedPath;
        }

        if (strpos($fullPath, $basePath) !== 0) {
            throw new \InvalidArgumentException('Geçersiz dosya yolu');
        }

        return $fullPath;
    }

    /**
     * Dosya var mı kontrolü
     */
    public function exists(string $relativePath): bool
    {
        return File::exists($this->getFullPath($relativePath));
    }

    /**
     * Dosyayı sil
     */
    public function delete(string $relativePath): bool
    {
        $fullPath = $this->getFullPath($relativePath);

        if (File::exists($fullPath)) {
            return File::delete($fullPath);
        }

        return false;
    }

    /**
     * Dosya erişim kontrolü
     */
    public function canAccess(PortalUser $user, ErpFile $file): bool
    {
        // Dosyanın bağlı olduğu job'ı kontrol et
        $job = Job::find($file->job_id);

        if (!$job) {
            return false;
        }

        // Job kullanıcının firmasına ait mi?
        return $job->mold_maker_id === $user->company_id;
    }

    /**
     * Dosyayı doğrula
     */
    protected function validateFile(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $this->allowedExtensions)) {
            throw new \InvalidArgumentException(
                "Geçersiz dosya uzantısı: {$extension}. İzin verilen: " . implode(', ', $this->allowedExtensions)
            );
        }

        if ($file->getSize() > $this->maxSize) {
            $maxMB = $this->maxSize / 1048576;
            throw new \InvalidArgumentException(
                "Dosya boyutu çok büyük. Maksimum: {$maxMB} MB"
            );
        }
    }

    /**
     * MIME type kontrolü
     */
    public function isAllowedMimeType(string $mimeType): bool
    {
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/postscript', // AI files
            'image/vnd.adobe.photoshop', // PSD
            'application/zip',
            'application/x-rar-compressed',
            'application/octet-stream', // CAD files
        ];

        return in_array($mimeType, $allowedMimes);
    }

    /**
     * İzin verilen uzantıları getir
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    /**
     * Maksimum boyutu getir (bytes)
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * Maksimum boyutu formatla
     */
    public function getFormattedMaxSize(): string
    {
        $bytes = $this->maxSize;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
