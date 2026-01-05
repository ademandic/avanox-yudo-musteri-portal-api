<?php

/**
 * YUDO Customer Portal API
 *
 * @author    Avanox Bilisim Yazilim ve Danismanlik LTD STI
 * @copyright 2025 Avanox Bilisim
 * @license   Proprietary - All rights reserved
 */

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ErpWebhookService
{
    protected string $baseUrl;
    protected string $webhookKey;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('portal.erp_webhook.base_url');
        $this->webhookKey = config('portal.erp_webhook.key');
        $this->timeout = config('portal.erp_webhook.timeout', 10);
    }

    /**
     * Send notification when a new request is created
     */
    public function notifyNewRequest(array $data): bool
    {
        return $this->sendWebhook('request.created', [
            'job_id' => $data['job_id'] ?? null,
            'job_no' => $data['job_no'] ?? null,
            'request_number' => $data['request_number'] ?? null,
            'request_type' => $data['request_type'] ?? null,
            'company_id' => $data['company_id'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'portal_user' => $data['portal_user'] ?? null,
        ]);
    }

    /**
     * Send notification when a new user registers
     */
    public function notifyUserRegistered(array $data): bool
    {
        return $this->sendWebhook('user.registered', [
            'user_id' => $data['user_id'] ?? null,
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'company_id' => $data['company_id'] ?? null,
            'company_name' => $data['company_name'] ?? null,
        ]);
    }

    /**
     * Send webhook to ERP
     */
    protected function sendWebhook(string $event, array $data): bool
    {
        if (empty($this->baseUrl) || empty($this->webhookKey)) {
            Log::warning('ERP Webhook: Configuration missing', [
                'has_url' => !empty($this->baseUrl),
                'has_key' => !empty($this->webhookKey),
            ]);
            return false;
        }

        $url = rtrim($this->baseUrl, '/') . '/api/portal/webhook';

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Portal-Api-Key' => $this->webhookKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, [
                    'event' => $event,
                    'data' => $data,
                ]);

            if ($response->successful()) {
                Log::info('ERP Webhook: Notification sent successfully', [
                    'event' => $event,
                    'job_no' => $data['job_no'] ?? null,
                ]);
                return true;
            }

            Log::error('ERP Webhook: Failed to send notification', [
                'event' => $event,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('ERP Webhook: Exception occurred', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
