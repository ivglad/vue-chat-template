<script setup>
import { AnimatePresence, motion } from 'motion-v'
import { useChatScrollOptimized } from '@/composables/chat/useChatScrollOptimized'
import { useResizeObserver, toRef } from '@vueuse/core'

const props = defineProps({
  messages: {
    type: Array,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  hasMessages: {
    type: Boolean,
    default: false,
  },
})

// Используем централизованную систему анимаций
const { createAnimationProps } = useChatAnimations()

// Определяем текущее состояние для анимаций
const currentState = computed(() => {
  if (props.isLoading) return 'loading'
  if (!props.hasMessages) return 'empty'
  return 'messages'
})

// Получаем анимационные пропсы из централизованной системы
const stateAnimationProps = createAnimationProps('chatStateTransition')

const messagesContainer = ref(null)
const messagesChildContainer = ref(null)

// Используем оптимизированный композабл для прокрутки
const {
  scrollToBottom,
  isScrolledToBottom,
  canScrollToBottom,
  containerVisible,
  enableSmartScroll,
  isSmartScrollActive,
} = useChatScrollOptimized(messagesContainer, {
  behavior: 'smooth',
  threshold: 0.1,
})

// Отслеживаем изменения размера контейнера сообщений
useResizeObserver(messagesChildContainer, () => {
  // Блокируем автоматическую прокрутку во время умной прокрутки
  if (isSmartScrollActive.value) {
    console.log('🚫 ResizeObserver (component): Blocked by smart scroll flag')
    return
  }

  // Прокручиваем вниз при изменении размера контента, если были внизу
  if (isScrolledToBottom.value) {
    console.log('📏 ResizeObserver (component): Scrolling to bottom')
    nextTick(scrollToBottom)
  }
})

// Включаем умную прокрутку для всех типов сообщений
onMounted(() => {
  enableSmartScroll(toRef(props, 'messages'))
})
</script>

<template>
  <div
    ref="messagesContainer"
    class="flex flex-1 items-center justify-center w-full overflow-y-auto px-6 py-4 pb-0 space-y-4 scroll-smooth"
    :class="{ 'pr-1': canScrollToBottom }">
    <AnimatePresence mode="wait">
      <motion.div
        v-if="currentState === 'loading'"
        key="loading"
        v-bind="stateAnimationProps"
        class="flex items-center justify-center py-8">
        <ProgressSpinner
          class="app-progressspinner self-center w-[3rem] h-[3rem]"
          fill="transparent" />
      </motion.div>

      <motion.div
        v-else-if="currentState === 'empty'"
        key="empty"
        v-bind="stateAnimationProps">
        <ChatEmptyState />
      </motion.div>

      <motion.div
        v-else-if="currentState === 'messages'"
        key="messages"
        v-bind="stateAnimationProps"
        class="w-full self-start">
        <div ref="messagesChildContainer">
          <ChatMessage
            v-for="(message, index) in messages"
            :key="message.id"
            :message="message"
            :index="index"
            :data-message-id="message.id"
            class="max-w-[70rem] justify-self-center"
            :class="{ 'mb-8 last:mb-4': message.type !== 'user' }" />
        </div>
      </motion.div>
    </AnimatePresence>
  </div>
</template>
