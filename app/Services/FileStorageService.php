<?php

namespace App\Services;

use App\Models\ErpFile;
use App\Models\Job;
use App\Models\PortalUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * File Storage Service
 * Dosya yükleme ve yönetim işlemleri.
 *
 * Klasör yapısı: {base_path}/Sales/{yıl}/{ay}/{job_no}/{subfolder}/{technical_data_id}/
 * Örnek: /mnt/yudo_data/Sales/2025/01/YT25-1/drawing_log/12345/kalip-cizim.pdf
 */
class FileStorageService
{
    protected string $basePath;
    protected string $subfolder;
    protected array $allowedExtensions;
    protected int $maxSize;

    public function __construct()
    {
        $config = config('portal.upload');
        $this->basePath = $config['base_path'];
        $this->subfolder = $config['subfolder'];
        $this->allowedExtensions = $config['allowed_extensions'];
        $this->maxSize = $config['max_size'];
    }

    /**
     * Dosya yükleme path'i oluştur
     * Format: {base}/Sales/{yıl}/{ay}/{job_no}/drawing_log/{technical_data_id}/
     */
    public function buildPath(string $jobNo, int $technicalDataId): string
    {
        $year = date('Y');
        $month = date('m'); // 01, 02, ... 12

        $path = sprintf(
            '%s/Sales/%s/%s/%s/%s/%d',
            $this->basePath,
            $year,
            $month,
            $jobNo,
            $this->subfolder,
            $technicalDataId
        );

        // Klasör yoksa oluştur
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        return $path;
    }

    /**
     * Dosya kaydet - Yeni yapı (job_no + technical_data_id ile)
     */
    public function store(UploadedFile $file, string $jobNo, int $technicalDataId): array
    {
        $this->validateFile($file);

        $path = $this->buildPath($jobNo, $technicalDataId);

        // Orijinal dosya adını koru (güvenli hale getir)
        $originalName = $file->getClientOriginalName();
        $safeName = $this->sanitizeFileName($originalName);

        // Aynı isimde dosya varsa numara ekle
        $finalName = $this->getUniqueFileName($path, $safeName);

        // Dosyayı kaydet
        $file->move($path, $finalName);

        // Relative path (DB'ye kaydedilecek)
        $relativePath = sprintf(
            'Sales/%s/%s/%s/%s/%d/%s',
            date('Y'),
            date('m'),
            $jobNo,
            $this->subfolder,
            $technicalDataId,
            $finalName
        );

        return [
            'original_name' => $originalName,
            'saved_name' => $finalName,
            'relative_path' => $relativePath,
            'full_path' => $path . '/' . $finalName,
            'extension' => strtolower($file->getClientOriginalExtension()),
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
        try {
            return File::exists($this->getFullPath($relativePath));
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Dosyayı sil
     */
    public function delete(string $relativePath): bool
    {
        try {
            $fullPath = $this->getFullPath($relativePath);

            if (File::exists($fullPath)) {
                return File::delete($fullPath);
            }
        } catch (\InvalidArgumentException $e) {
            return false;
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
     * Dosya adını güvenli hale getir
     */
    protected function sanitizeFileName(string $name): string
    {
        // Türkçe karakterleri değiştir
        $name = str_replace(
            ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç', ' '],
            ['i', 'g', 'u', 's', 'o', 'c', 'I', 'G', 'U', 'S', 'O', 'C', '_'],
            $name
        );

        // Sadece alfanumerik, tire, alt çizgi ve nokta
        $name = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $name);

        // Çoklu alt çizgileri teke indir
        $name = preg_replace('/_+/', '_', $name);

        return $name;
    }

    /**
     * Benzersiz dosya adı oluştur
     */
    protected function getUniqueFileName(string $path, string $name): string
    {
        if (!File::exists($path . '/' . $name)) {
            return $name;
        }

        $info = pathinfo($name);
        $base = $info['filename'];
        $ext = $info['extension'] ?? '';

        $counter = 1;
        while (File::exists($path . '/' . $base . '_' . $counter . '.' . $ext)) {
            $counter++;
        }

        return $base . '_' . $counter . '.' . $ext;
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
