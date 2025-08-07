<script setup>
import { AnimatePresence, motion } from 'motion-v'
import { useChatScroll } from '@/composables/chat/useChatScroll'
import { useElementVisibility } from '@vueuse/core'

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

// Отслеживаем видимость контейнера сообщений для определения готовности к прокрутке
const messagesChildContainerVisible = useElementVisibility(messagesChildContainer)

const overflowClasses = computed(() => {
  if (!messagesContainer.value || !messagesChildContainer.value) return ''
  const containerHeight = messagesContainer.value.clientHeight
  const childHeight =
    messagesChildContainer.value.scrollHeight ||
    messagesChildContainer.value.offsetHeight
  return childHeight > containerHeight ? 'pr-1' : ''
})

// Инициализируем композабл для плавной прокрутки чата
const { scrollToBottom } = useChatScroll(messagesContainer)

// Флаг для отслеживания первоначальной прокрутки
const hasPerformedInitialScroll = ref(false)

// Реактивно отслеживаем готовность к прокрутке
const isReadyForScroll = computed(() => {
  return (
    currentState.value === 'messages' &&
    messagesChildContainerVisible.value &&
    messagesChildContainer.value &&
    messagesContainer.value
  )
})

// Функция для выполнения первоначальной прокрутки
const performInitialScroll = () => {
  if (!hasPerformedInitialScroll.value && isReadyForScroll.value) {
    scrollToBottom()
    hasPerformedInitialScroll.value = true
  }
}

// Отслеживаем готовность к прокрутке и выполняем её при необходимости
watchEffect(() => {
  if (isReadyForScroll.value) {
    // Используем nextTick для гарантии, что DOM обновлен
    nextTick(performInitialScroll)
  }
})

// Обработчик завершения анимации появления сообщений
const onMessagesAnimationComplete = () => {
  // Дополнительная проверка и прокрутка после завершения анимации
  nextTick(performInitialScroll)
}

// Сбрасываем флаг при изменении списка сообщений (для новых загрузок)
watch(
  () => props.messages.length,
  (newLength, oldLength) => {
    // Сбрасываем флаг только если сообщения были очищены (новая загрузка)
    if (newLength === 0 || (oldLength > 0 && newLength < oldLength)) {
      hasPerformedInitialScroll.value = false
    }
  }
)
</script>

<template>
  <div
    ref="messagesContainer"
    class="flex flex-1 items-center justify-center w-full overflow-y-auto px-6 py-4 pb-0 space-y-4 scroll-smooth"
    :class="overflowClasses">
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
        class="w-full self-start"
        @complete="onMessagesAnimationComplete">
        <div ref="messagesChildContainer">
          <ChatMessage
            v-for="(message, index) in messages"
            :key="message.id"
            :message="message"
            :index="index"
            class="max-w-[70rem] justify-self-center"
            :class="{ 'mb-8 last:mb-4': message.type !== 'user' }" />
        </div>
      </motion.div>
    </AnimatePresence>
  </div>
</template>