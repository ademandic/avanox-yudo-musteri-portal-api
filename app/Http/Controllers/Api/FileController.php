<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UploadFileRequest;
use App\Http\Resources\FileResource;
use App\Models\ErpFile;
use App\Models\Job;
use App\Models\PortalRequest;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    public function __construct(
        protected FileStorageService $fileStorageService
    ) {}

    /**
     * Talebe ait dosyaları listele
     * GET /api/requests/{requestId}/files
     */
    public function index(int $requestId): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $portalRequest = PortalRequest::forCompany($user->company_id)
            ->active()
            ->find($requestId);

        if (!$portalRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Talep bulunamadı.',
            ], 404);
        }

        $files = ErpFile::where('job_id', $portalRequest->job_id)
            ->whereIn('baglanti_tablo_adi', ErpFile::PORTAL_VISIBLE_TYPES)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => FileResource::collection($files),
        ]);
    }

    /**
     * Dosya yükle
     * POST /api/requests/{requestId}/files
     */
    public function store(UploadFileRequest $request, int $requestId): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $portalRequest = PortalRequest::forCompany($user->company_id)
            ->active()
            ->find($requestId);

        if (!$portalRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Talep bulunamadı.',
            ], 404);
        }

        $job = Job::with('technicalData')->find($portalRequest->job_id);

        if (!$job || !$job->technicalData) {
            return response()->json([
                'success' => false,
                'message' => 'İş veya teknik veri bulunamadı.',
            ], 404);
        }

        try {
            $uploadedFiles = [];

            foreach ($request->file('files') as $file) {
                $fileInfo = $this->fileStorageService->store(
                    $file,
                    $job->job_no,
                    $job->technicalData->id
                );

                $erpFile = ErpFile::create([
                    'job_id' => $portalRequest->job_id,
                    'baglanti_id' => $portalRequest->id,
                    'baglanti_tablo_adi' => 'portal_requests',
                    'dosya_yolu' => $fileInfo['relative_path'],
                    'dosya_adi' => $fileInfo['original_name'],
                    'extension' => $fileInfo['extension'],
                    'dosya_boyut' => $fileInfo['size'],
                    'aciklama' => $request->description ?? 'Portal üzerinden yüklendi.',
                    'user_id' => null,
                    'is_active' => 1,
                ]);

                $uploadedFiles[] = new FileResource($erpFile);
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' dosya başarıyla yüklendi.',
                'data' => $uploadedFiles,
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Dosya yükleme hatası', [
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Dosya yüklenirken bir hata oluştu.',
            ], 500);
        }
    }

    /**
     * Dosya indir
     * GET /api/files/{id}/download
     */
    public function download(int $id): JsonResponse|BinaryFileResponse
    {
        $user = Auth::guard('api')->user();

        // Sadece whitelist'teki dosya türlerini göster
        $file = ErpFile::whereIn('baglanti_tablo_adi', ErpFile::PORTAL_VISIBLE_TYPES)
            ->active()
            ->find($id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'Dosya bulunamadı.',
            ], 404);
        }

        // Erişim kontrolü
        if (!$this->fileStorageService->canAccess($user, $file)) {
            return response()->json([
                'success' => false,
                'message' => 'Bu dosyaya erişim yetkiniz yok.',
            ], 403);
        }

        $fullPath = $this->fileStorageService->getFullPath($file->dosya_yolu);

        if (!$this->fileStorageService->exists($file->dosya_yolu)) {
            return response()->json([
                'success' => false,
                'message' => 'Dosya sistemde bulunamadı.',
            ], 404);
        }

        return response()->download($fullPath, $file->dosya_adi);
    }

    /**
     * Dosya sil
     * DELETE /api/files/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $file = ErpFile::active()->find($id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'Dosya bulunamadı.',
            ], 404);
        }

        // Erişim kontrolü
        if (!$this->fileStorageService->canAccess($user, $file)) {
            return response()->json([
                'success' => false,
                'message' => 'Bu dosyaya erişim yetkiniz yok.',
            ], 403);
        }

        // Sadece portal üzerinden yüklenen dosyalar silinebilir
        if ($file->baglanti_tablo_adi !== 'portal_requests') {
            return response()->json([
                'success' => false,
                'message' => 'Bu dosya silinemez.',
            ], 403);
        }

        // İlişkili talebin durumunu kontrol et
        $portalRequest = PortalRequest::find($file->baglanti_id);
        if ($portalRequest && !$portalRequest->isEditable()) {
            return response()->json([
                'success' => false,
                'message' => 'Talep işleme alındıktan sonra dosya silinemez.',
            ], 403);
        }

        // Soft delete
        $file->update(['is_active' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Dosya başarıyla silindi.',
        ]);
    }

    /**
     * Yükleme kurallarını getir
     * GET /api/files/upload-rules
     */
    public function uploadRules(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'allowed_extensions' => $this->fileStorageService->getAllowedExtensions(),
                'max_size' => $this->fileStorageService->getMaxSize(),
                'max_size_formatted' => $this->fileStorageService->getFormattedMaxSize(),
            ],
        ]);
    }
}
