<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim notifikasi Firebase Cloud Messaging (HTTP v1).
 *
 * Sengaja tidak memakai SDK: cukup service account JSON + JWT RS256,
 * sehingga tidak menambah dependensi besar dan mudah dijelaskan di skripsi.
 */
class FcmService
{
    protected ?array $credentials = null;

    protected bool $loaded = false;

    /**
     * Opsi HTTP: pakai CA bundle lokal bila ada (Windows tanpa sertifikat root).
     */
    protected function httpOptions(): array
    {
        $caBundle = config('services.firebase.ca_bundle');

        if (! $caBundle) {
            return [];
        }

        if (! preg_match('/^([A-Za-z]:[\\\\\/]|[\/\\\\])/', $caBundle)) {
            $caBundle = base_path($caBundle);
        }

        return is_file($caBundle) ? ['verify' => $caBundle] : [];
    }

    public function enabled(): bool
    {
        return $this->credentials() !== null;
    }

    protected function credentials(): ?array
    {
        if ($this->loaded) {
            return $this->credentials;
        }

        $this->loaded = true;
        $path = config('services.firebase.credentials');

        if (! $path) {
            return null;
        }

        // Izinkan path relatif terhadap root project.
        if (! preg_match('/^([A-Za-z]:[\\\\\/]|[\/\\\\])/', $path)) {
            $path = base_path($path);
        }

        if (! is_file($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        if (! is_array($json) || empty($json['private_key']) || empty($json['client_email'])) {
            return null;
        }

        return $this->credentials = $json;
    }

    /**
     * Ambil OAuth2 access token dari service account (di-cache ~50 menit).
     */
    protected function accessToken(): ?string
    {
        if ($cached = Cache::get('fcm_access_token')) {
            return $cached;
        }

        $creds = $this->credentials();

        if (! $creds) {
            return null;
        }

        $now = time();
        $encode = fn (array $data) => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        $signingInput = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
            'iss' => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        if (! openssl_sign($signingInput, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
            Log::warning('FCM: gagal menandatangani JWT.');

            return null;
        }

        $jwt = $signingInput.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        try {
            $response = Http::withOptions($this->httpOptions())->asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
        } catch (\Throwable $e) {
            Log::warning('FCM: gagal meminta access token - '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            Log::warning('FCM: access token ditolak', ['status' => $response->status()]);

            return null;
        }

        $token = $response->json('access_token');

        if ($token) {
            Cache::put('fcm_access_token', $token, 3000);
        }

        return $token;
    }

    /**
     * Kirim notifikasi ke satu atau banyak device token.
     *
     * @return int jumlah notifikasi yang berhasil dikirim
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): int
    {
        $tokens = array_values(array_unique(array_filter($tokens)));

        if ($tokens === [] || ! $this->enabled()) {
            return 0;
        }

        $accessToken = $this->accessToken();
        $projectId = $this->credentials()['project_id'] ?? null;

        if (! $accessToken || ! $projectId) {
            return 0;
        }

        $sent = 0;

        foreach ($tokens as $token) {
            try {
                $response = Http::withOptions($this->httpOptions())
                    ->withToken($accessToken)
                    ->timeout(15)
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => [
                            'token' => $token,
                            'notification' => ['title' => $title, 'body' => $body],
                            'data' => array_map('strval', $data),
                            'android' => [
                                'priority' => 'high',
                                'notification' => ['channel_id' => 'payroll'],
                            ],
                        ],
                    ]);
            } catch (\Throwable $e) {
                Log::warning('FCM: pengiriman gagal - '.$e->getMessage());

                continue;
            }

            if ($response->successful()) {
                $sent++;

                continue;
            }

            // Token sudah tidak valid (app di-uninstall / token dirotasi) -> hapus.
            if ($response->status() === 404 || $response->json('error.status') === 'NOT_FOUND') {
                DeviceToken::where('token', $token)->delete();

                continue;
            }

            Log::warning('FCM: respons tidak sukses', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 300),
            ]);
        }

        return $sent;
    }
}
