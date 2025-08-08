<script setup>
import { AnimatePresence, motion } from 'motion-v'
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
  enableSmartScroll,
  isSmartScrollActive,
  checkScrollNeeded,
} = useChatScroll(messagesContainer, {
  behavior: 'smooth',
  threshold: 0.1,
})

// Проверяем необходимость прокрутки
const needsScroll = checkScrollNeeded(messagesChildContainer)

// Отслеживаем изменения размера дочернего контейнера
useResizeObserver(messagesChildContainer, (entries) => {
  if (entries[0]) {
    // Блокируем автоматическую прокрутку во время умной прокрутки
    if (isSmartScrollActive.value) {
      return
    }
    // Прокручиваем вниз при изменении размера контента, если были внизу
    if (isScrolledToBottom.value) {
      nextTick(scrollToBottom)
    }
  }
})

// Функция для поиска соответствующего ответа бота для пользовательского сообщения
const getBotResponseForUser = (userMessage, userIndex) => {
  // Ищем следующее сообщение бота после пользовательского
  const nextBotMessage = props.messages
    .slice(userIndex + 1)
    .find((msg) => msg.type === 'bot')

  if (nextBotMessage) {
    return nextBotMessage
  }
  // Если нет ответа бота, создаем объект с состоянием загрузки
  return {
    isLoading: true,
    status: 'loading',
    loadingText: 'Обрабатываю запрос...',
  }
}

// Включаем умную прокрутку для всех типов сообщений
onMounted(() => {
  enableSmartScroll(toRef(props, 'messages'))
})
</script>

<template>
  <div
    ref="messagesContainer"
    class="flex flex-1 items-center justify-center w-full overflow-y-auto overflow-x-hidden px-6 py-4 pb-0 space-y-4 scroll-smooth"
    :class="{ 'pr-1': needsScroll }">
    <AnimatePresence mode="wait">
      <motion.div
        v-if="currentState === 'loading'"
        key="loading"
        v-bind="stateAnimationProps"
        class="flex items-center justify-center py-8">
        <ProgressSpinner
          strokeWidth="4"
          class="app-progressspinner self-center w-[4rem] h-[4rem]"
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
        class="flex justify-center w-full h-full max-w-[70rem]">
        <div ref="messagesChildContainer" class="w-full h-full">
          <template v-for="(message, index) in messages" :key="message.id">
            <ChatMessage
              :message="message"
              :index="index"
              :data-message-id="message.id"
              :class="{ 'mb-8 last:mb-4': message.type !== 'user' }" />

            <ChatMessageSeparator
              v-if="message.type === 'user'"
              :message="getBotResponseForUser(message, index)"
              :data-separator-id="message.id" />
          </template>
        </div>
      </motion.div>
    </AnimatePresence>
  </div>
</template>
