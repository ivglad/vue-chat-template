<template>
  <div class="chat-container h-full flex flex-col">
    <!-- Заголовок чата -->
    <ChatHeader 
      :messages-count="messages.length"
      :is-loading="isLoading"
      @clear-history="handleClearHistory"
    />
    
    <!-- Область сообщений -->
    <div 
      ref="messagesContainer"
      class="chat-messages flex-1 overflow-y-auto px-4 py-6 space-y-4"
      @scroll="handleScroll"
    >
      <!-- Пустое состояние -->
      <ChatEmptyState 
        v-if="!hasMessages && !isLoading"
        class="h-full"
      />
      
      <!-- Список сообщений -->
      <AnimatedList
        v-else
        :items="messages"
        item-preset="messageAppear"
        :stagger-delay="0.05"
        container-class="space-y-4"
        :key-extractor="(msg) => msg.id"
      >
        <template #item="{ item: message, index }">
          <ChatMessage
            :message="message"
            :index="index"
            :show-loading="isLoadingForMessage(message.id)"
          />
        </template>
      </AnimatedList>
      
      <!-- Индикатор загрузки истории -->
      <div 
        v-if="isLoading && !hasMessages"
        class="flex justify-center py-8"
      >
        <ProgressSpinner 
          style="width: 32px; height: 32px"
          stroke-width="3"
        />
      </div>
    </div>
    
    <!-- Кнопка "Прокрутить вниз" -->
    <Transition name="scroll-button">
      <Button
        v-if="!isScrolledToBottom && hasMessages"
        class="scroll-to-bottom-btn"
        icon="pi pi-chevron-down"
        rounded
        size="small"
        @click="scrollToBottom"
      />
    </Transition>
    
    <!-- Поле ввода -->
    <ChatInput 
      :disabled="isLoading"
      @send-message="handleSendMessage"
    />
    
    <!-- Уведомления об ошибках -->
    <ChatErrorNotification 
      v-if="lastError"
      :error="lastError"
      @dismiss="clearLastError"
      @retry="handleRetry"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useChatMessages } from '@/composables/chat/useChatMessages'
import { useChatScroll } from '@/composables/chat/useChatScroll'
import { useChatErrorHandler } from '@/composables/chat/useChatErrorHandler'
import { useChatStore } from '@/stores/chat/useChatStore'

import AnimatedList from '@/components/ui/animations/AnimatedList.vue'
import ChatHeader from './ChatHeader.vue'
import ChatMessage from './ChatMessage.vue'
import ChatInput from './ChatInput.vue'
import ChatEmptyState from './ChatEmptyState.vue'
import ChatErrorNotification from './ChatErrorNotification.vue'

/**
 * Главный контейнер чата
 * Координирует работу всех подкомпонентов
 * Следует принципу Container/Presentational Components
 */

// ============================================================================
// Refs
// ============================================================================

const messagesContainer = ref(null)

// ============================================================================
// Composables
// ============================================================================

const chatStore = useChatStore()

const {
  messages,
  isLoading,
  hasMessages,
  sendMessage,
  clearHistory,
  isSendingMessage,
  isClearingHistory
} = useChatMessages()

const {
  scrollToBottom,
  isScrolledToBottom,
  initScrollContainer,
  enableAutoScroll
} = useChatScroll()

const {
  lastError,
  clearLastError,
  canRetry
} = useChatErrorHandler()

// ============================================================================
// Computed
// ============================================================================

const isLoadingForMessage = computed(() => {
  return (messageId) => {
    return chatStore.loadingMessageId === messageId
  }
})

// ============================================================================
// Methods
// ============================================================================

/**
 * Обработать отправку сообщения
 * @param {Object} messageData - данные сообщения
 */
const handleSendMessage = async (messageData) => {
  try {
    await sendMessage(messageData)
    
    // Прокручиваем вниз после отправки
    setTimeout(() => {
      scrollToBottom({ smooth: true, delay: 100 })
    }, 200)
    
  } catch (error) {
    console.error('Failed to send message:', error)
  }
}

/**
 * Обработать очистку истории
 */
const handleClearHistory = async () => {
  try {
    await clearHistory()
  } catch (error) {
    console.error('Failed to clear history:', error)
  }
}

/**
 * Обработать скролл контейнера сообщений
 * @param {Event} event - событие скролла
 */
const handleScroll = (event) => {
  // Логика скролла обрабатывается в useChatScroll
  // Здесь можем добавить дополнительную логику если нужно
}

/**
 * Повторить последнюю неудачную операцию
 */
const handleRetry = () => {
  if (!lastError.value || !canRetry(lastError.value)) {
    return
  }
  
  const context = lastError.value.context
  
  switch (context?.action) {
    case 'send_message':
      // Повторяем отправку сообщения
      if (context.messageData) {
        handleSendMessage(context.messageData)
      }
      break
      
    case 'load_history':
      // Обновляем историю
      window.location.reload()
      break
      
    case 'clear_history':
      // Повторяем очистку
      handleClearHistory()
      break
  }
  
  clearLastError()
}

// ============================================================================
// Watchers
// ============================================================================

// Автоматически прокручиваем вниз при новых сообщениях
watch(
  () => messages.value.length,
  (newLength, oldLength) => {
    if (newLength > oldLength) {
      // Небольшая задержка для завершения анимаций
      setTimeout(() => {
        scrollToBottom({ smooth: true })
      }, 300)
    }
  }
)

// Прокручиваем вниз при появлении новых ответов
watch(
  messages,
  (newMessages, oldMessages) => {
    if (!oldMessages) return
    
    // Проверяем появление новых ответов
    let hasNewReplies = false
    
    newMessages.forEach((newMsg) => {
      const oldMsg = oldMessages.find(m => m.id === newMsg.id)
      if (oldMsg && newMsg.replies?.length > (oldMsg.replies?.length || 0)) {
        hasNewReplies = true
      }
    })
    
    if (hasNewReplies) {
      setTimeout(() => {
        scrollToBottom({ smooth: true })
      }, 400)
    }
  },
  { deep: true }
)

// ============================================================================
// Lifecycle
// ============================================================================

onMounted(() => {
  // Инициализируем скролл контейнер
  if (messagesContainer.value) {
    initScrollContainer(messagesContainer.value)
  }
  
  // Прокручиваем вниз при загрузке
  setTimeout(() => {
    scrollToBottom({ smooth: false })
  }, 100)
})

onUnmounted(() => {
  // Очистка происходит автоматически в композаблах
})
</script>

<style scoped>
.chat-container {
  @apply bg-surface-0 relative;
}

.chat-messages {
  @apply scrollbar-thin scrollbar-thumb-surface-300 scrollbar-track-transparent;
  scroll-behavior: smooth;
}

.scroll-to-bottom-btn {
  @apply fixed bottom-24 right-6 z-10 shadow-lg;
  @apply bg-primary-500 text-white hover:bg-primary-600;
}

/* Анимации для кнопки прокрутки */
.scroll-button-enter-active,
.scroll-button-leave-active {
  transition: all 0.3s ease;
}

.scroll-button-enter-from,
.scroll-button-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.8);
}

/* Кастомный скроллбар */
.chat-messages::-webkit-scrollbar {
  width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
  background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
  background: theme('colors.surface.300');
  border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
  background: theme('colors.surface.400');
}

/* Responsive */
@media (max-width: 768px) {
  .scroll-to-bottom-btn {
    @apply bottom-20 right-4;
  }
  
  .chat-messages {
    @apply px-2;
  }
}
</style>