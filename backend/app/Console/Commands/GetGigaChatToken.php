<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GetGigaChatToken extends Command
{
    protected $signature = 'gigachat:token';
    protected $description = 'Получает access_token от Sberbank OAuth для GigaChat';

    public function handle()
    {
        // Получаем данные из .env
        $authKey = config('services.gigachat.auth_key'); // Base64 строка или client_id:client_secret
        $scope = config('services.gigachat.scope', 'GIGACHAT_API_PERS');

        if (!$authKey) {
            $this->error('Не указан AUTH_KEY. Проверьте .env и config/services.php');
            return 1;
        }

        // Генерируем уникальный RqUID
        $rqUid = (string) Str::uuid();

        try {
            $response = Http::withHeaders([
                'Authorization' => "Basic {$authKey}",
                'RqUID' => $rqUid,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ])
            ->withoutVerifying() // ⚠️ Только для dev! Убери в продакшене
            ->post('https://ngw.devices.sberbank.ru:9443/api/v2/oauth', [
                'scope' => $scope,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;
                $expiresIn = $data['expires_in'] ?? null;

                $this->info('✅ Токен успешно получен!');
                $this->line('');
                $this->info('Access Token: ' . $token);
                $this->info('Действует: ' . ($expiresIn ? $expiresIn . ' сек' : 'неизвестно'));

                // Сохраним токен в кэш, например, на 1 час (или на expires_in)
                if ($token && $expiresIn) {
                    cache()->put('gigachat_access_token', $token, $expiresIn - 60); // минус 60 сек на безопасность
                    $this->info('Токен сохранён в кэше.');
                }

                return 0;
            } else {
                $this->error('Ошибка API: ' . $response->status());
                $this->line($response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Ошибка запроса: ' . $e->getMessage());
            return 1;
        }
    }
}