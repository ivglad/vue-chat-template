<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Docwise - Умная система управления документами</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgb(156 163 175);
            border-radius: 3px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: rgb(107 114 128);
        }
        .dark .custom-scroll::-webkit-scrollbar-thumb {
            background: rgb(75 85 99);
        }
        .dark .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: rgb(107 114 128);
        }
        @keyframes blink {
            50% { opacity: 0; }
        }
        .typing-cursor::after {
            content: '|';
            color: rgb(59 130 246);
            animation: blink 1s step-end infinite;
            margin-left: 2px;
        }
        .dark .typing-cursor::after {
            color: rgb(96 165 250);
        }
        .prism-code {
            background: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .glass-effect {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .dark .glass-effect {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .dark .gradient-bg {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        }
        /* Prevent horizontal scroll on mobile */
        body {
            overflow-x: hidden;
        }
        /* Ensure proper word wrapping */
        * {
            word-wrap: break-word;
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f5e9ff',
                            100: '#ead3ff',
                            200: '#d5b3ff',
                            300: '#bf93ff',
                            400: '#a873ff',
                            500: '#6f38a1',
                            600: '#5e2e8a',
                            700: '#4e2673',
                            800: '#3f1f5c',
                            900: '#2f1745',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <img src="{{ asset('images/logo.png') }}" alt="Docwise" class="w-full h-full object-contain rounded-lg">
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Docwise</h1>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 break-words">Умная система документооборота</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-3">
                    <!-- Contact Button -->
                    <button id="header-contact-btn" class="flex items-center space-x-2 px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors duration-200 w-10 h-10 justify-center sm:w-auto sm:h-auto sm:justify-start">
                        <i class="fas fa-phone text-xs"></i>
                        <span class="hidden sm:block">Связаться</span>
                    </button>
                    
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="p-1.5 sm:p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200 flex-shrink-0 justify-center w-10 h-10">
                        <i id="theme-icon" class="fas fa-moon text-gray-600 dark:text-gray-300 text-sm sm:text-base"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 lg:gap-12 items-start">
            <!-- Description Section -->
            <div class="space-y-6 sm:space-y-8">
                <div class="space-y-6">
                    <div class="space-y-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-200">
                            <i class="fas fa-sparkles mr-2"></i>
                            Новое поколение
                        </span>
                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white leading-tight break-words">
                            Революция в управлении 
                            <span class="text-primary-600 dark:text-primary-400">документами</span>
                        </h2>
                        <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-300 leading-relaxed break-words">
                            Docwise использует искусственный интеллект для автоматизации документооборота, 
                            обеспечивая быстрый поиск, умную классификацию и интеллектуальную обработку ваших документов.
                        </p>
                    </div>
                    
                    <!-- Features -->
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mt-1">
                                <i class="fas fa-check text-green-600 dark:text-green-400 text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">ИИ-поиск по содержимому</h3>
                                <p class="text-gray-600 dark:text-gray-300">Найдите любой документ по смыслу, а не только по названию</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mt-1">
                                <i class="fas fa-check text-green-600 dark:text-green-400 text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Автоматическая классификация</h3>
                                <p class="text-gray-600 dark:text-gray-300">Система сама сортирует документы по категориям</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mt-1">
                                <i class="fas fa-check text-green-600 dark:text-green-400 text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Чат с документами</h3>
                                <p class="text-gray-600 dark:text-gray-300">Задавайте вопросы и получайте ответы из ваших документов</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Widget -->
            <div class="space-y-6">
                <div class="text-center space-y-2">
                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Демо чат-помощника</h3>
                    <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300 break-words">Посмотрите, как Docwise отвечает на вопросы о системе</p>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <!-- Chat Header -->
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center">
                            <img src="{{ asset('images/logo.png') }}" alt="Docwise" class="w-full h-full object-contain rounded-full">
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">Docwise AI</h4>
                                <div class="flex items-center space-x-1">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Онлайн</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Messages -->
                    <div id="chat-container" class="h-96 p-4 overflow-y-auto custom-scroll bg-gray-50 dark:bg-gray-900/50">
                        <div id="chat-messages" class="space-y-3"></div>
                    </div>
                    
                    <!-- Chat Input (disabled/demo) -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex items-center space-x-3">
                            <input type="text" placeholder="Демо-режим..." 
                                   class="flex-1 px-2 sm:px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm sm:text-base min-w-0" 
                                   disabled>
                            <button class="p-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors duration-200 justify-center w-10 h-10" disabled>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Case Studies Section -->
        <section class="py-8 sm:py-12 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 mt-8 sm:mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center space-y-4 mb-8 sm:mb-12">
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white">Примеры использования</h2>
                    <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                        Узнайте, как Docwise помогает различным организациям оптимизировать документооборот
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    <!-- Case 1 -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-briefcase text-white text-lg"></i>
                            </div>
                            <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">Юридическая фирма</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm sm:text-base">
                            Автоматизация обработки договоров сократила время поиска на 70%. ИИ классифицирует документы по типу и статусу, а чат-бот отвечает на вопросы клиентов.
                        </p>
                    </div>
                    <!-- Case 2 -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-hospital text-white text-lg"></i>
                            </div>
                            <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">Медицинский центр</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm sm:text-base">
                            Docwise оцифровал архив медицинских карт, обеспечив быстрый поиск по симптомам и диагнозам. Безопасное хранение данных соответствует стандартам HIPAA.
                        </p>
                    </div>
                    <!-- Case 3 -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-industry text-white text-lg"></i>
                            </div>
                            <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">Производственная компания</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm sm:text-base">
                            Интеграция Docwise оптимизировала управление технической документацией, сократив время на согласование чертежей и инструкций на 50%.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Company Info -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center">
                            <img src="{{ asset('images/logo.png') }}" alt="Docwise" class="w-full h-full object-contain rounded-lg">
                        </div>
                        <span class="text-xl font-bold text-gray-900 dark:text-white">Docwise</span>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300">
                        Революционная система управления документами с искусственным интеллектом.
                    </p>
                </div>
                
                <!-- Purchase -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Получить доступ</h3>
                    <div class="space-y-3">
                        <button id="purchase-contact-btn" class="w-full bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center space-x-2">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Приобрести систему</span>
                        </button>
                        <button class="w-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                            Узнать подробнее
                        </button>
                    </div>
                </div>
                
                <!-- Social Links -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Мы в соцсетях</h3>
                    <div class="flex space-x-3 sm:space-x-4">
                        <a href="#" class="w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center justify-center transition-colors duration-200">
                            <i class="fab fa-vk"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-blue-500 hover:bg-blue-600 text-white rounded-lg flex items-center justify-center transition-colors duration-200">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white rounded-lg flex items-center justify-center transition-all duration-200">
                            <i class="fas fa-m"></i>
                        </a>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Следите за новостями и обновлениями
                    </p>
                </div>
            </div>
            
            <!-- Copyright -->
            <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                <p class="text-center text-gray-500 dark:text-gray-400">
                    © 2024 Docwise. Все права защищены.
                </p>
            </div>
        </div>
    </footer>

    <!-- Contact Modal -->
    <div id="contact-modal" class="fixed inset-0 flex items-center justify-center bg-gray-600 bg-opacity-90 overflow-y-auto h-full w-full z-50 hidden">
        <div class="p-6 border max-w-md w-full shadow-xl rounded-lg bg-white dark:bg-gray-800">
            <div class="flex justify-start mb-4">
                <img src="{{ asset('images/logo.png') }}" alt="Docwise Logo" class="w-16 h-16 rounded-lg">
            </div>
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Связаться с нами</h3>
                    <button id="close-modal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form id="contact-form" class="space-y-4">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Номер телефона <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" id="phone" name="phone" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            placeholder="+7 (999) 123-45-67">
                    </div>
                    <div>
                        <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Комментарий (необязательно)
                        </label>
                        <textarea id="comment" name="comment" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                                placeholder="Расскажите о ваших потребностях..."></textarea>
                    </div>
                    <div class="flex space-x-3 pt-2">
                        <button type="submit" id="submit-btn"
                                class="flex-1 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center justify-center space-x-2">
                            <i class="fas fa-paper-plane text-sm"></i>
                            <span>Отправить</span>
                        </button>
                        <button type="button" id="cancel-btn"
                                class="flex-1 bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md font-medium transition-colors duration-200">
                            Закрыть
                        </button>
                    </div>
                </form>
                <div id="success-message" class="hidden text-center p-4">
                    <div class="text-green-600 dark:text-green-400 mb-2">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <p class="text-gray-900 dark:text-white font-medium">Заявка отправлена!</p>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mt-1">Мы свяжемся с вами в ближайшее время.</p>
                </div>
                <div id="error-message" class="hidden text-center p-4">
                    <div class="text-red-600 dark:text-red-400 mb-2">
                        <i class="fas fa-exclamation-circle text-2xl"></i>
                    </div>
                    <p class="text-gray-900 dark:text-white font-medium">Ошибка отправки</p>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mt-1" id="error-text">Попробуйте еще раз</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const html = document.documentElement;

        // Check for saved theme preference or default to light
        const currentTheme = localStorage.getItem('theme') || 'light';
        if (currentTheme === 'dark') {
            html.classList.add('dark');
            themeIcon.className = 'fas fa-sun text-gray-600 dark:text-gray-300';
        }

        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                themeIcon.className = 'fas fa-sun text-gray-600 dark:text-gray-300';
                localStorage.setItem('theme', 'dark');
            } else {
                themeIcon.className = 'fas fa-moon text-gray-600 dark:text-gray-300';
                localStorage.setItem('theme', 'light');
            }
        });

        // Chat Demo
        const chatMessages = [
            {
                user: "Как добавить новый документ в систему?",
                bot: "Просто перетащите файл в интерфейс или нажмите 'Загрузить'. Система автоматически извлечет текст и создаст векторные представления для поиска."
            },
            {
                user: "Можно ли найти документы по содержанию?",
                bot: "Да! Используйте семантический поиск - просто опишите, что ищете. Например, 'договоры с поставщиками за 2023 год' найдет все соответствующие документы."
            },
            {
                user: "Как работает чат с документами?",
                bot: "Задайте вопрос на естественном языке, и система найдет ответ в ваших документах. ИИ анализирует контекст и предоставляет точные ответы с указанием источников."
            },
            {
                user: "Поддерживаются ли разные форматы файлов?",
                bot: "Docwise работает с PDF, Word, Excel, PowerPoint, изображениями и многими другими форматами. Система автоматически извлекает текст и метаданные."
            },
            {
                user: "Безопасно ли хранить документы в системе?",
                bot: "Да, мы используем шифрование данных, контроль доступа и регулярные резервные копии. Ваши документы защищены по стандартам банковской безопасности."
            }
        ];

        const chatContainer = document.getElementById('chat-container');
        const chatMessagesDiv = document.getElementById('chat-messages');
        let currentMessageIndex = 0;
        let currentCharIndex = 0;
        let isTypingUser = true;
        let isUserScrolledUp = false;

        // Check if user scrolled up
        chatContainer.addEventListener('scroll', () => {
            const isAtBottom = chatContainer.scrollHeight - chatContainer.clientHeight <= chatContainer.scrollTop + 10;
            isUserScrolledUp = !isAtBottom;
        });

        function createMessageElement(content, isUser) {
            const messageDiv = document.createElement('div');
            messageDiv.className = isUser
                ? 'flex justify-end'
                : 'flex justify-start';
            
            const bubble = document.createElement('div');
            bubble.className = isUser
                ? 'bg-primary-500 text-white rounded-lg rounded-br-sm px-4 py-2 max-w-[80%] shadow-sm'
                : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg rounded-bl-sm px-4 py-2 max-w-[80%] shadow-sm border border-gray-200 dark:border-gray-600';
            
            messageDiv.appendChild(bubble);
            chatMessagesDiv.appendChild(messageDiv);
            return bubble;
        }

        function scrollToBottom() {
            if (!isUserScrolledUp) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        }

        function typeText() {
            const currentMessage = chatMessages[currentMessageIndex];
            const isUserMessage = isTypingUser;
            const text = isUserMessage ? currentMessage.user : currentMessage.bot;

            if (currentCharIndex === 0) {
                const newElement = createMessageElement('', isUserMessage);
                scrollToBottom();
            }

            const currentText = text.slice(0, currentCharIndex + 1);
            const currentElements = chatMessagesDiv.querySelectorAll('.flex');
            const currentElement = currentElements[currentElements.length - 1].querySelector('div');
            
            currentElement.textContent = currentText;
            currentElement.classList.add('typing-cursor');

            currentCharIndex++;

            if (currentCharIndex >= text.length) {
                currentElement.classList.remove('typing-cursor');
                
                if (isTypingUser) {
                    isTypingUser = false;
                    currentCharIndex = 0;
                    setTimeout(typeText, 1000); // Pause before bot response
                } else {
                    currentMessageIndex = (currentMessageIndex + 1) % chatMessages.length;
                    isTypingUser = true;
                    currentCharIndex = 0;
                    
                    // Remove old messages if more than 6
                    const allMessages = chatMessagesDiv.querySelectorAll('.flex');
                    if (allMessages.length > 6) {
                        allMessages[0].remove();
                        allMessages[1].remove();
                    }
                    
                    setTimeout(typeText, 2000); // Pause before next question
                }
            } else {
                setTimeout(typeText, 50); // Typing speed
            }

            scrollToBottom();
        }

        // Start chat demo after a short delay
        setTimeout(typeText, 1000);

        // Contact Modal Functions
        const contactModal = document.getElementById('contact-modal');
        const contactForm = document.getElementById('contact-form');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');
        const submitBtn = document.getElementById('submit-btn');
        
        // Modal Controls
        const headerContactBtn = document.getElementById('header-contact-btn');
        const purchaseContactBtn = document.getElementById('purchase-contact-btn');
        const closeModalBtn = document.getElementById('close-modal');
        const cancelBtn = document.getElementById('cancel-btn');

        // Open modal
        function openContactModal() {
            contactModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            // Reset form and messages
            contactForm.classList.remove('hidden');
            successMessage.classList.add('hidden');
            errorMessage.classList.add('hidden');
            contactForm.reset();
        }

        // Close modal
        function closeContactModal() {
            contactModal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Event listeners for opening modal
        headerContactBtn?.addEventListener('click', openContactModal);
        purchaseContactBtn?.addEventListener('click', openContactModal);

        // Event listeners for closing modal
        closeModalBtn?.addEventListener('click', closeContactModal);
        cancelBtn?.addEventListener('click', closeContactModal);

        // Close modal when clicking outside
        contactModal?.addEventListener('click', (e) => {
            if (e.target === contactModal) {
                closeContactModal();
            }
        });

        // Handle form submission
        contactForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(contactForm);
            const phone = formData.get('phone');
            const comment = formData.get('comment');

            // Basic phone validation
            if (!phone || phone.trim().length < 10) {
                showError('Пожалуйста, введите корректный номер телефона');
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-sm"></i><span>Отправка...</span>';

            try {
                const response = await fetch('/contact-request', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        phone: phone,
                        comment: comment
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                } else {
                    showError(data.message || 'Произошла ошибка при отправке заявки');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Ошибка соединения. Попробуйте еще раз.');
            } finally {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane text-sm"></i><span>Отправить</span>';
            }
        });

        function showSuccess(message) {
            contactForm.classList.add('hidden');
            errorMessage.classList.add('hidden');
            successMessage.classList.remove('hidden');
            
            // Auto close after 3 seconds
            setTimeout(() => {
                closeContactModal();
            }, 3000);
        }

        function showError(message) {
            contactForm.classList.add('hidden');
            successMessage.classList.add('hidden');
            document.getElementById('error-text').textContent = message;
            errorMessage.classList.remove('hidden');
            
            // Show form again after 2 seconds
            setTimeout(() => {
                errorMessage.classList.add('hidden');
                contactForm.classList.remove('hidden');
            }, 2000);
        }

        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        phoneInput?.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('8')) {
                value = '7' + value.slice(1);
            }
            if (value.startsWith('7')) {
                value = value.replace(/^7(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/, (match, p1, p2, p3, p4) => {
                    let result = '+7';
                    if (p1) result += ` (${p1}`;
                    if (p2) result += `) ${p2}`;
                    if (p3) result += `-${p3}`;
                    if (p4) result += `-${p4}`;
                    return result;
                });
            }
            e.target.value = value;
        });
    </script>
</body>
</html>