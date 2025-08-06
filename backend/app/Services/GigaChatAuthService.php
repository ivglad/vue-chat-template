<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GigaChatAuthService
{
    public function getToken(): ?string
    {
        // Если токен уже есть в кэше — возвращаем
        return Cache::remember('gigachat_access_token', 3500, function () {
            return $this->fetchNewToken();
        });
    }

    private function fetchNewToken(): ?string
    {
        $authKey = config('services.gigachat.auth_key');
        $scope = config('services.gigachat.scope');
        $rqUid = (string) Str::uuid();

        if (!$authKey) {
            throw new \Exception('GIGACHAT_AUTH_KEY не задан в .env');
        }

        $response = Http::withHeaders([
            'Authorization' => "Basic {$authKey}",
            'RqUID' => $rqUid,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ])
        ->withoutVerifying()
        ->asForm()
        ->post('https://ngw.devices.sberbank.ru:9443/api/v2/oauth', [
            'scope' => $scope,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['access_token'] ?? null;
        }

        throw new \Exception('Не удалось получить токен: ' . $response->body());
    }
}