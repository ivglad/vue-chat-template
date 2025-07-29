<?php

// Простой скрипт для тестирования валидации API

$baseUrl = 'http://localhost/api/v1';
$token = 'your_token_here'; // Замените на реальный токен

$testCases = [
    [
        'name' => 'Без document_ids',
        'data' => ['message' => 'Тест без document_ids']
    ],
    [
        'name' => 'С пустым массивом document_ids',
        'data' => ['message' => 'Тест с пустым массивом', 'document_ids' => []]
    ],
    [
        'name' => 'С корректными document_ids',
        'data' => ['message' => 'Тест с корректными ID', 'document_ids' => [1, 2]]
    ],
    [
        'name' => 'С некорректными document_ids (строки)',
        'data' => ['message' => 'Тест с некорректными ID', 'document_ids' => ['abc', 'def']]
    ],
    [
        'name' => 'С несуществующими document_ids',
        'data' => ['message' => 'Тест с несуществующими ID', 'document_ids' => [999, 1000]]
    ]
];

foreach ($testCases as $testCase) {
    echo "\n--- {$testCase['name']} ---\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/chat/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testCase['data']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . substr($response, 0, 200) . "...\n";
}

echo "\n✅ Тестирование валидации завершено!\n";