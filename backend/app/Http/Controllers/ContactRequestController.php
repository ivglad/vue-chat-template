<?php

namespace App\Http\Controllers;

use App\Models\ContactRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class ContactRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            // Создаем заявку в базе данных
            $contactRequest = ContactRequest::create([
                'phone' => $request->phone,
                'comment' => $request->comment,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Отправляем в телеграм
            $this->sendToTelegram($contactRequest);

            return response()->json([
                'success' => true,
                'message' => 'Заявка успешно отправлена! Мы свяжемся с вами в ближайшее время.'
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при создании заявки: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при отправке заявки. Попробуйте еще раз.'
            ], 500);
        }
    }

    private function sendToTelegram(ContactRequest $contactRequest)
    {
        try {
            $chatId = env('TELEGRAM_CONTACT_CHAT_ID');
            
            if (!$chatId) {
                Log::warning('TELEGRAM_CONTACT_CHAT_ID не настроен в .env');
                return;
            }

            $message = "🔔 <b>Новая заявка с сайта Docwise</b>\n\n";
            $message .= "📱 <b>Телефон:</b> <code>" . $contactRequest->phone . "</code>\n";
            
            if ($contactRequest->comment) {
                $message .= "💬 <b>Комментарий:</b>\n" . htmlspecialchars($contactRequest->comment) . "\n";
            }
            
            $message .= "\n🕐 <b>Время:</b> " . $contactRequest->created_at->format('d.m.Y H:i:s');
            $message .= "\n🌐 <b>IP:</b> <code>" . $contactRequest->ip_address . "</code>";
            $message .= "\n📊 <b>ID заявки:</b> #" . $contactRequest->id;

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);

            // Обновляем статус отправки
            $contactRequest->update([
                'is_sent_to_telegram' => true,
                'sent_to_telegram_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка при отправке в телеграм: ' . $e->getMessage());
        }
    }
}
