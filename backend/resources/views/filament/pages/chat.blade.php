<x-filament-panels::page>
    <div class="space-y-6" wire:poll.5s="refreshChat">

        <!-- История чата -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-6">
                @if(empty($chatHistory))
                    <div class="text-center py-8">
                        <div class="text-gray-400 text-lg mb-2">💬</div>
                        <p class="text-gray-500 dark:text-gray-400">
                            Ваша история чата пуста. Начните диалог, отправив первое сообщение!
                        </p>
                    </div>
                @else
                    <div class="space-y-4 max-h-96 overflow-y-auto" id="chat-history">
                        @foreach($chatHistory as $message)
                            <div class="flex {{ $message['type'] === 'user' ? 'justify-end' : 'justify-start' }}" 
                                 data-message-id="{{ $message['id'] }}"
                                 wire:key="message-{{ $message['id'] }}">
                                <div class="max-w-2xl lg:max-w-md px-4 py-2 rounded-lg {{ $message['type'] === 'user' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100' }}">
                                    
                                    <!-- Индикатор связи сообщений -->
                                    @if($message['is_reply'])
                                        <div class="text-xs opacity-70 mb-1">
                                            ↳ Ответ на сообщение #{{ $message['parent_id'] }}
                                        </div>
                                    @elseif($message['type'] === 'user' && $message['replies_count'] > 0)
                                        <div class="text-xs opacity-70 mb-1">
                                            ● Есть {{ $message['replies_count'] }} ответ(ов)
                                        </div>
                                    @endif

                                    <!-- Основное сообщение -->
                                    <div class="prose max-w-none dark:prose-invert break-words">
                                        @if($message['type'] === 'bot')
                                            {!! \App\Services\MarkdownFormatterService::convertMarkdownToHtml($message['message']) !!}
                                        @else
                                            <div class="whitespace-pre-wrap">{{ $message['message'] }}</div>
                                        @endif
                                    </div>
                                    
                                    <!-- Источники документов -->
                                    @if($message['context_documents'] && count($message['context_documents']) > 0)
                                        <div class="text-xs opacity-70 mt-2 border-t border-white/20 pt-2">
                                            📚 Источники: {{ implode(', ', $message['context_documents']) }}
                                        </div>
                                    @endif
                                    
                                    <!-- Время и автор -->
                                    <div class="text-xs opacity-70 mt-2">
                                        @if($message['type'] === 'user')
                                            Вы
                                        @else
                                            🤖 ИИ-помощник
                                        @endif
                                        · {{ $message['created_at_human'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        
                        <!-- Индикатор обработки сообщения -->
                        @if($isProcessing)
                            <div class="flex justify-start" id="processing-indicator">
                                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100">
                                    <div class="flex items-center space-x-2">
                                        <svg class="animate-pulse h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="4" cy="10" r="2"/>
                                            <circle cx="10" cy="10" r="2"/>
                                            <circle cx="16" cy="10" r="2"/>
                                        </svg>
                                        <span class="text-sm text-gray-500">ИИ-помощник печатает...</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Форма отправки сообщения -->
            <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <form wire:submit="sendMessage">
                    {{ $this->form }}
                    
                    <!-- Информация о выбранном контексте -->
                    @if(!empty($data['selected_documents']))
                        <!-- <div class="mt-6 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700 dark:text-blue-300">
                                        <strong>Активный контекст</strong>
                                    </p>
                                </div>
                            </div>
                        </div> -->
                    @else
                        <!-- <div class="mt-6 p-3 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-md">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <strong>Универсальный режим</strong>
                                    </p>
                                </div>
                            </div>
                        </div> -->
                    @endif
                    
                    <!-- Индикатор обработки -->
                    @if($isProcessing)
                        <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="animate-spin h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                        <strong>Обрабатываю ваш запрос...</strong> Это может занять несколько секунд.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="mt-6 flex justify-between">
                        <div>
                            {{ $this->getClearAction() }}
                        </div>
                        <div>
                            {{ $this->getSendAction() }}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let lastScrollHeight = 0;
        
        // Автоматическая прокрутка к последнему сообщению
        function scrollToBottom() {
            const chatHistory = document.getElementById('chat-history');
            if (chatHistory) {
                // Прокручиваем только если есть новый контент
                if (chatHistory.scrollHeight > lastScrollHeight) {
                    chatHistory.scrollTop = chatHistory.scrollHeight;
                    lastScrollHeight = chatHistory.scrollHeight;
                }
            }
        }
        
        // Прокрутка при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
        });
        
        // Прокрутка после обновления Livewire
        document.addEventListener('livewire:navigated', scrollToBottom);
        
        // Слушаем события обработки сообщений
        window.addEventListener('message-processing', function() {
            // Прокручиваем к низу когда начинается обработка
            setTimeout(scrollToBottom, 100);
        });
        
        window.addEventListener('message-processed', function() {
            // Прокручиваем к низу когда обработка завершена
            setTimeout(scrollToBottom, 100);
        });
        
        // Отправка формы по Ctrl+Enter
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const form = document.querySelector('form[wire\\:submit="sendMessage"]');
                if (form) {
                    const event = new Event('submit', {
                        bubbles: true,
                        cancelable: true
                    });
                    form.dispatchEvent(event);
                }
            }
        });

        // Обновляем прокрутку при изменении содержимого чата
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.target.id === 'chat-history') {
                    setTimeout(scrollToBottom, 50);
                }
            });
        });

        // Запускаем наблюдатель после загрузки DOM
        document.addEventListener('DOMContentLoaded', function() {
            const chatHistory = document.getElementById('chat-history');
            if (chatHistory) {
                observer.observe(chatHistory, { childList: true, subtree: true });
            }
        });
    </script>
</x-filament-panels::page> 