<script setup>
import { AnimatePresence, motion } from 'motion-v'

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

const overflowClasses = computed(() => {
  if (!messagesContainer.value || !messagesChildContainer.value) return ''
  const containerHeight = messagesContainer.value.clientHeight
  const childHeight =
    messagesChildContainer.value.scrollHeight ||
    messagesChildContainer.value.offsetHeight
  return childHeight > containerHeight ? 'pr-1' : ''
})
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
        class="w-full self-start">
        <div
          ref="messagesChildContainer"
          class="space-y-8">
          <ChatMessage
            v-for="(message, index) in messages"
            :key="message.id"
            :message="message"
            :index="index"
            class="max-w-[70rem] justify-self-center" />
        </div>
      </motion.div>
    </AnimatePresence>
  </div>
</template>
