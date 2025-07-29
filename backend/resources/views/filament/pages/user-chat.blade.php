<x-filament-panels::page>
    <div class="space-y-6" wire:poll.5s="refreshChat">
        
        {{-- История чата --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">
                    История сообщений
                    @if($isProcessing)
                        <span class="inline-flex items-center ml-2">
                            <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="ml-1 text-sm text-blue-600 dark:text-blue-400">Обрабатывается...</span>
                        </span>
                    @endif
                </h4>
                
                @if(empty($chatHistory))
                    <div class="text-center py-8">
                        <x-heroicon-o-chat-bubble-left-right class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                            Нет сообщений
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Этот пользователь еще не отправлял сообщений.
                        </p>
                    </div>
                @else
                    <div class="space-y-4 max-h-96 overflow-y-auto" id="chat-history">
                        @foreach($chatHistory as $message)
                            <div class="flex {{ $message['type'] === 'user' ? 'justify-end' : 'justify-start' }}"
                                 wire:key="message-{{ $message['id'] }}">
                                <div class="max-w-xs lg:max-w-md xl:max-w-lg {{ $message['type'] === 'user' ? 'order-1' : 'order-2' }}">
                                    {{-- Индикатор связи --}}
                                    @if($message['is_reply'])
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex items-center">
                                            <x-heroicon-s-arrow-turn-down-left class="h-3 w-3 mr-1" />
                                            Ответ на сообщение #{{ $message['parent_id'] }}
                                        </div>
                                    @elseif($message['type'] === 'user' && $message['replies_count'] > 0)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex items-center">
                                            <x-heroicon-s-chat-bubble-bottom-center class="h-3 w-3 mr-1" />
                                            {{ $message['replies_count'] }} {{ $message['replies_count'] === 1 ? 'ответ' : 'ответов' }}
                                        </div>
                                    @endif

                                    {{-- Сообщение --}}
                                    <div class="px-4 py-2 rounded-lg {{ $message['type'] === 'user' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-gray-100' }}">
                                        {{-- Основное сообщение --}}
                                        <div class="whitespace-pre-wrap break-words">
                                            {{ $message['message'] }}
                                        </div>
                                        
                                        {{-- Источники документов --}}
                                        @if($message['context_documents'] && count($message['context_documents']) > 0)
                                            <div class="text-xs opacity-70 mt-2 border-t border-white/20 pt-2">
                                                📚 Источники: {{ implode(', ', $message['context_documents']) }}
                                            </div>
                                        @endif
                                        
                                        {{-- Время и автор --}}
                                        <div class="text-xs opacity-70 mt-2">
                                            @if($message['type'] === 'user')
                                                {{ $message['user_name'] }}
                                            @else
                                                🤖 ИИ-помощник
                                            @endif
                                            · {{ $message['created_at_human'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        
                        {{-- Индикатор обработки сообщения --}}
                        @if($isProcessing)
                            <div class="flex justify-start" id="processing-indicator">
                                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-gray-100">
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
        </div>

        {{-- Форма отправки сообщения --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Отправить сообщение
                </h4>
                
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-yellow-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                Сообщение будет обработано как если бы его отправил <strong>{{ $user->name }}</strong>. 
                            </p>
                        </div>
                    </div>
                </div>

                <form wire:submit="sendMessage">
                    {{ $this->form }}
                    
                    <!-- Информация о выбранном контексте -->
                    @if(!empty($data['selected_documents']))
                        <div class="mt-6 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md">
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
                        </div>
                    @else
                        <div class="mt-6 p-3 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-md">
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
                        </div>
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
                                        <strong>Обрабатываю запрос от лица пользователя...</strong> Это может занять несколько секунд.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 flex justify-between">
                        {{ $this->getSendAction() }}
                        {{ $this->getClearAction() }}
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let lastScrollHeight = 0;
        
        // Автоматическая прокрутка чата к последнему сообщению
        function scrollToBottom() {
            const chatContainer = document.querySelector('#chat-history');
            if (chatContainer) {
                // Прокручиваем только если есть новый контент
                if (chatContainer.scrollHeight > lastScrollHeight) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                    lastScrollHeight = chatContainer.scrollHeight;
                }
            }
        }
        
        // Прокрутка при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
        });

        // Прокрутка после отправки нового сообщения
        document.addEventListener('livewire:navigated', function() {
            setTimeout(scrollToBottom, 100);
        });
        
        // Слушаем события обработки сообщений
        window.addEventListener('message-processing', function() {
            setTimeout(scrollToBottom, 100);
        });
        
        window.addEventListener('message-processed', function() {
            setTimeout(scrollToBottom, 100);
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