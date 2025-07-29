<div class="space-y-4">
    {{-- Основная информация --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
            Основная информация
        </h4>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Имя</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $user->name }}</dd>
            </div>
            @if($user->email)
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $user->email }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Дата регистрации</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $user->created_at->format('d.m.Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">ID пользователя</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">#{{ $user->id }}</dd>
            </div>
        </dl>
    </div>

    {{-- Информация Telegram --}}
    @if(!empty($telegramData))
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                <x-heroicon-s-chat-bubble-left-right class="h-4 w-4 mr-2 text-blue-500" />
                Данные Telegram
            </h4>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
                @if(isset($telegramData['id']))
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Telegram ID</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $telegramData['id'] }}</dd>
                    </div>
                @endif
                @if(isset($telegramData['username']))
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Username</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">@{{ $telegramData['username'] }}</dd>
                    </div>
                @endif
                @if(isset($telegramData['first_name']))
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Имя в Telegram</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                            {{ $telegramData['first_name'] }}
                            @if(isset($telegramData['last_name']))
                                {{ $telegramData['last_name'] }}
                            @endif
                        </dd>
                    </div>
                @endif
                @if(isset($telegramData['language_code']))
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Язык</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ strtoupper($telegramData['language_code']) }}</dd>
                    </div>
                @endif
                @if(isset($telegramData['is_premium']))
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Telegram Premium</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                            @if($telegramData['is_premium'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    <x-heroicon-s-star class="h-3 w-3 mr-1" />
                                    Да
                                </span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">Нет</span>
                            @endif
                        </dd>
                    </div>
                @endif
                @if(isset($telegramData['registered_at']))
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Регистрация в боте</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                            {{ \Carbon\Carbon::parse($telegramData['registered_at'])->format('d.m.Y H:i') }}
                        </dd>
                    </div>
                @endif
                @if(isset($telegramData['last_seen_at']))
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Последняя активность</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                            {{ \Carbon\Carbon::parse($telegramData['last_seen_at'])->format('d.m.Y H:i') }}
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                ({{ \Carbon\Carbon::parse($telegramData['last_seen_at'])->diffForHumans() }})
                            </span>
                        </dd>
                    </div>
                @endif
            </dl>
        </div>
    @endif

    {{-- Статистика документов --}}
    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <x-heroicon-s-document-text class="h-4 w-4 mr-2 text-green-500" />
            Документы
        </h4>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Всего документов</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $documentsCount }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Обработано для поиска</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $processedDocs }}
                    @if($documentsCount > 0)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            ({{ round(($processedDocs / $documentsCount) * 100, 1) }}%)
                        </span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    {{-- Статистика чата --}}
    @php
        $userMessages = \App\Models\ChatMessage::where('user_id', $user->id)->where('type', 'user')->count();
        $botMessages = \App\Models\ChatMessage::where('user_id', $user->id)->where('type', 'bot')->count();
        $totalMessages = $userMessages + $botMessages;
        $lastMessage = \App\Models\ChatMessage::where('user_id', $user->id)->latest()->first();
    @endphp

    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <x-heroicon-s-chat-bubble-bottom-center class="h-4 w-4 mr-2 text-purple-500" />
            Статистика чата
        </h4>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Всего сообщений</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $totalMessages }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Вопросов задано</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $userMessages }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Ответов получено</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $botMessages }}</dd>
            </div>
            @if($lastMessage)
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Последнее сообщение</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                        {{ $lastMessage->created_at->format('d.m.Y H:i') }}
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            ({{ $lastMessage->created_at->diffForHumans() }})
                        </span>
                    </dd>
                </div>
            @endif
        </dl>
    </div>
</div> 