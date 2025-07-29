<?php

// Простой скрипт для тестирования API через Scribe документацию

$baseUrl = 'http://localhost/api/v1';

// Получаем токен пользователя (замените на реальный)
$user = \App\Models\User::first();
if (!$user) {
    echo "❌ Пользователь не найден\n";
    exit(1);
}

$token = $user->createToken('test-scribe')->plainTextToken;

echo "🧪 Тестирование API эндпоинта /chat/send\n";
echo "👤 Пользователь: {$user->name}\n";
echo "🔑 Токен: " . substr($token, 0, 20) . "...\n\n";

$testCases = [
    [
        'name' => 'Без document_ids (как в Scribe документации)',
        'data' => [
            'message' => 'Расскажи о содержимом документов'
        ]
    ],
    [
        'name' => 'С пустым массивом document_ids',
        'data' => [
            'message' => 'Расскажи о содержимом документов',
            'document_ids' => []
        ]
    ]
];

foreach ($testCases as $testCase) {
    echo "--- {$testCase['name']} ---\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/chat/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testCase['data']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✅ Запрос успешен\n";
            echo "📝 Ответ: " . substr($data['data']['bot_response']['message'], 0, 100) . "...\n";
        } else {
            echo "❌ Ошибка в ответе: " . ($data['message'] ?? 'Неизвестная ошибка') . "\n";
        }
    } else {
        echo "❌ HTTP ошибка\n";
        echo "Response: " . substr($response, 0, 200) . "...\n";
    }
    echo "\n";
}

// Удаляем тестовый токен
$user->tokens()->where('name', 'test-scribe')->delete();

echo "✅ Тестирование завершено!\n";