<template>
  <AnimatedContainer
    :preset="animationPreset"
    :delay="animationDelay"
    container-class="chat-message"
    :data-message-id="message.id"
  >
    <div 
      :class="messageClasses"
      class="flex flex-col gap-2 p-4 rounded-2xl max-w-4xl"
    >
      <!-- Заголовок сообщения -->
      <div class="flex items-center gap-2 text-sm text-surface-500">
        <Avatar
          :label="message.type === 'user' ? 'U' : 'AI'"
          :class="avatarClasses"
          size="small"
        />
        <span class="font-medium">
          {{ message.type === 'user' ? 'Вы' : 'Ассистент' }}
        </span>
        <span class="text-xs">
          {{ formatMessageTime(message.created_at) }}
        </span>
      </div>
      
      <!-- Содержимое сообщения -->
      <div class="message-content">
        <ChatMessageContent 
          :content="message.message"
          :type="message.type"
          :is-local="message.isLocal"
        />
      </div>
      
      <!-- Контекстные документы -->
      <ChatMessageDocuments 
        v-if="message.context_documents?.length"
        :documents="message.context_documents"
      />
      
      <!-- Ответы бота -->
      <div 
        v-if="message.replies?.length"
        class="replies-container mt-4 space-y-3"
      >
        <AnimatedList
          :items="message.replies"
          item-preset="replyAppear"
          :stagger-delay="0.1"
          item-class="reply-item"
        >
          <template #item="{ item: reply, index }">
            <ChatReply 
              :reply="reply"
              :index="index"
            />
          </template>
        </AnimatedList>
      </div>
      
      <!-- Индикатор загрузки -->
      <ChatLoadingIndicator 
        v-if="showLoading"
        class="mt-2"
      />
    </div>
  </AnimatedContainer>
</template>

<script setup>
import { computed } from 'vue'
import { useChatAnimations } from '@/composables/animations/useChatAnimations'
import AnimatedContainer from '@/components/ui/animations/AnimatedContainer.vue'
import AnimatedList from '@/components/ui/animations/AnimatedList.vue'
import ChatMessageContent from './ChatMessageContent.vue'
import ChatMessageDocuments from './ChatMessageDocuments.vue'
import ChatReply from './ChatReply.vue'
import ChatLoadingIndicator from './ChatLoadingIndicator.vue'

/**
 * Компонент отдельного сообщения в чате
 * Следует принципам Single Responsibility и Composition
 * Использует композаблы для анимаций и утилит
 */

// ============================================================================
// Props
// ============================================================================

const props = defineProps({
  /**
   * Объект сообщения
   */
  message: {
    type: Object,
    required: true
  },
  
  /**
   * Индекс сообщения в списке (для анимации)
   */
  index: {
    type: Number,
    default: 0
  },
  
  /**
   * Показывать ли индикатор загрузки
   */
  showLoading: {
    type: Boolean,
    default: false
  }
})

// ============================================================================
// Composables
// ============================================================================

const { getMessageAnimationProps } = useChatAnimations()

// ============================================================================
// Computed
// ============================================================================

const messageClasses = computed(() => {
  const baseClasses = 'message-bubble transition-colors duration-200'
  
  if (props.message.type === 'user') {
    return `${baseClasses} bg-primary-50 border border-primary-100 ml-auto`
  } else {
    return `${baseClasses} bg-surface-50 border border-surface-200`
  }
})

const avatarClasses = computed(() => {
  if (props.message.type === 'user') {
    return 'bg-primary-500 text-white'
  } else {
    return 'bg-surface-400 text-white'
  }
})

const animationPreset = computed(() => {
  if (props.message.isLocal) {
    return 'messageSlideIn'
  }
  return 'messageAppear'
})

const animationDelay = computed(() => {
  return props.index * 0.05
})

// ============================================================================
// Methods
// ============================================================================

/**
 * Форматировать время сообщения
 * @param {string} timestamp - временная метка
 * @returns {string} отформатированное время
 */
const formatMessageTime = (timestamp) => {
  if (!timestamp) return ''
  
  const date = new Date(timestamp)
  const now = new Date()
  
  // Если сегодня, показываем только время
  if (date.toDateString() === now.toDateString()) {
    return date.toLocaleTimeString('ru-RU', {
      hour: '2-digit',
      minute: '2-digit'
    })
  }
  
  // Если не сегодня, показываем дату и время
  return date.toLocaleString('ru-RU', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.chat-message {
  @apply w-full;
}

.message-bubble {
  @apply shadow-sm hover:shadow-md;
  transition: box-shadow 0.2s ease;
}

.message-content {
  @apply text-surface-900 leading-relaxed;
}

.replies-container {
  @apply border-l-2 border-surface-200 pl-4;
}

.reply-item {
  @apply last:mb-0;
}

/* Анимация для локальных сообщений */
.message-bubble[data-local="true"] {
  @apply opacity-80 bg-surface-100;
}

/* Responsive */
@media (max-width: 768px) {
  .message-bubble {
    @apply max-w-none mx-2;
  }
}
</style>