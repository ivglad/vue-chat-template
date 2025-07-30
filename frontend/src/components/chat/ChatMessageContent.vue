<template>
  <div class="message-content">
    <!-- Обычный текст для пользовательских сообщений -->
    <div 
      v-if="type === 'user'"
      class="user-message-text"
      v-text="content"
    />
    
    <!-- Анимированный текст для ответов бота -->
    <div 
      v-else-if="shouldAnimateText"
      class="bot-message-animated"
    >
      <span
        v-for="(word, index) in animatedWords"
        :key="index"
        :class="getWordClass(word)"
        class="inline-block mr-1"
      >
        {{ word.text }}
      </span>
    </div>
    
    <!-- Статичный текст для ответов бота (без анимации) -->
    <div 
      v-else
      class="bot-message-static"
      v-html="formattedContent"
    />
  </div>
</template>

<script setup>
import { computed, watch, onMounted } from 'vue'
import { useTextAnimation } from '@/composables/useTextAnimation'
import { useMarkdownParser } from '@/composables/useMarkdownParser'

/**
 * Компонент для отображения содержимого сообщения
 * Поддерживает анимацию текста и markdown форматирование
 * Следует принципу Single Responsibility
 */

// ============================================================================
// Props
// ============================================================================

const props = defineProps({
  /**
   * Содержимое сообщения
   */
  content: {
    type: String,
    required: true
  },
  
  /**
   * Тип сообщения ('user' | 'bot')
   */
  type: {
    type: String,
    required: true,
    validator: (value) => ['user', 'bot'].includes(value)
  },
  
  /**
   * Является ли сообщение локальным (еще не отправлено)
   */
  isLocal: {
    type: Boolean,
    default: false
  },
  
  /**
   * Включить анимацию текста для ответов бота
   */
  enableAnimation: {
    type: Boolean,
    default: true
  }
})

// ============================================================================
// Composables
// ============================================================================

const { 
  animatedWords, 
  isAnimating, 
  animateText, 
  resetAnimation,
  getWordClass 
} = useTextAnimation()

const { parseMarkdown } = useMarkdownParser()

// ============================================================================
// Computed
// ============================================================================

const shouldAnimateText = computed(() => {
  return props.type === 'bot' && 
         props.enableAnimation && 
         !props.isLocal &&
         props.content.length > 0
})

const formattedContent = computed(() => {
  if (props.type === 'user') {
    return props.content
  }
  
  // Парсим markdown для ответов бота
  return parseMarkdown(props.content)
})

// ============================================================================
// Watchers
// ============================================================================

// Запускаем анимацию при изменении контента
watch(
  () => props.content,
  (newContent) => {
    if (shouldAnimateText.value && newContent) {
      resetAnimation()
      
      // Небольшая задержка для плавности
      setTimeout(() => {
        animateText(newContent, {
          wordDelay: 80,
          fadeInDuration: 250
        })
      }, 100)
    }
  },
  { immediate: true }
)

// ============================================================================
// Lifecycle
// ============================================================================

onMounted(() => {
  if (shouldAnimateText.value && props.content) {
    animateText(props.content, {
      wordDelay: 80,
      fadeInDuration: 250
    })
  }
})
</script>

<style scoped>
.message-content {
  @apply text-base leading-relaxed;
}

.user-message-text {
  @apply text-surface-900 whitespace-pre-wrap break-words;
}

.bot-message-animated {
  @apply text-surface-800;
}

.bot-message-static {
  @apply text-surface-800 prose prose-sm max-w-none;
}

/* Стили для анимированных слов */
.bot-message-animated .inline-block {
  transition: all 0.2s ease-out;
}

/* Markdown стили */
.bot-message-static :deep(h1),
.bot-message-static :deep(h2),
.bot-message-static :deep(h3) {
  @apply font-semibold text-surface-900 mt-4 mb-2;
}

.bot-message-static :deep(h1) {
  @apply text-xl;
}

.bot-message-static :deep(h2) {
  @apply text-lg;
}

.bot-message-static :deep(h3) {
  @apply text-base;
}

.bot-message-static :deep(p) {
  @apply mb-3 last:mb-0;
}

.bot-message-static :deep(ul),
.bot-message-static :deep(ol) {
  @apply ml-4 mb-3;
}

.bot-message-static :deep(li) {
  @apply mb-1;
}

.bot-message-static :deep(code) {
  @apply bg-surface-100 text-surface-800 px-1 py-0.5 rounded text-sm font-mono;
}

.bot-message-static :deep(pre) {
  @apply bg-surface-100 p-3 rounded-lg overflow-x-auto mb-3;
}

.bot-message-static :deep(pre code) {
  @apply bg-transparent p-0;
}

.bot-message-static :deep(blockquote) {
  @apply border-l-4 border-surface-300 pl-4 italic text-surface-600 mb-3;
}

.bot-message-static :deep(strong) {
  @apply font-semibold text-surface-900;
}

.bot-message-static :deep(em) {
  @apply italic;
}

.bot-message-static :deep(a) {
  @apply text-primary-600 hover:text-primary-700 underline;
}
</style>