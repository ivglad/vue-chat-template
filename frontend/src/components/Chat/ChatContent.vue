<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useTextAnimation } from '@/composables/useTextAnimation'
import { useLoadingPhrases } from '@/composables/useLoadingPhrases'

const props = defineProps({
  messagesHistory: {
    type: Array,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
})

const messages = computed(() => {
  if (props.messagesHistory.length)
    return [...props.messagesHistory].filter(
      (message) => message.type === 'user',
    )
  return []
})

const scrollContainer = ref(null)

// Композабл для фраз загрузки
const {
  currentPhrase,
  isAnimating,
  startLoadingAnimation,
  stopLoadingAnimation,
} = useLoadingPhrases()

// Хранилище анимированных ответов
const replyAnimations = ref(new Map())

// ID последнего сообщения для которого показывается анимация загрузки
const loadingMessageId = ref(null)

const scrollToBottom = () => {
  if (scrollContainer.value) {
    scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight
  }
}

// Функция для создания анимации для конкретного ответа
const createReplyAnimation = (replyId, text) => {
  const { animatedWords, animateText, getWordClass } = useTextAnimation()

  replyAnimations.value.set(replyId, {
    animatedWords,
    getWordClass,
    isAnimated: true,
  })

  // Запускаем анимацию
  animateText(text, { wordDelay: 80, fadeInDuration: 250 })
}

// Получение данных анимации для ответа
const getReplyAnimation = (replyId) => {
  return replyAnimations.value.get(replyId) || null
}

// Находим последнее сообщение без ответов для отображения анимации загрузки
const lastMessageWithoutReplies = computed(() => {
  const userMessages = messages.value.filter((m) => m.type === 'user')
  if (userMessages.length === 0) return null

  // Ищем с конца первое сообщение без ответов
  for (let i = userMessages.length - 1; i >= 0; i--) {
    const message = userMessages[i]
    if (!message.replies || message.replies.length === 0) {
      return message
    }
  }
  return null
})

// Проверяем нужно ли показывать анимацию загрузки для конкретного сообщения
const shouldShowLoadingForMessage = (message) => {
  return (
    lastMessageWithoutReplies.value &&
    lastMessageWithoutReplies.value.id === message.id &&
    isAnimating.value
  )
}

// Прокручиваем вниз при изменении сообщений
watch(
  messages,
  (newMessages, oldMessages) => {
    // Ищем новые ответы для анимации (только для не-локальных сообщений)
    if (newMessages.length > 0) {
      newMessages.forEach((message) => {
        if (!message.isLocal && message.replies && message.replies.length > 0) {
          // Находим старое сообщение с тем же ID
          const oldMessage = oldMessages?.find((m) => m.id === message.id)
          const oldRepliesCount = oldMessage?.replies?.length || 0

          // Если появились новые ответы
          if (message.replies.length > oldRepliesCount) {
            // Останавливаем анимацию загрузки при первом ответе
            if (isAnimating.value && loadingMessageId.value === message.id) {
              stopLoadingAnimation()
              loadingMessageId.value = null
            }

            // Анимируем только новые ответы с минимальной задержкой для плавности
            const newReplies = message.replies.slice(oldRepliesCount)
            newReplies.forEach((reply, index) => {
              setTimeout(() => {
                createReplyAnimation(reply.id, reply.message)
              }, 150 + index * 200) // Уменьшенная задержка для плавности
            })
          }
        }
      })
    }

    // Прокрутка вниз после обработки анимаций
    nextTick(() => {
      setTimeout(() => {
        scrollToBottom()
      }, 400) // Увеличенная задержка для избежания конфликта с анимациями
    })
  },
  { deep: true },
)

// Прокручиваем вниз когда загрузка завершается
watch(
  () => props.isLoading,
  (newIsLoading, oldIsLoading) => {
    if (oldIsLoading && !newIsLoading) {
      nextTick(() => {
        scrollToBottom()
      })
    }
  },
)

// Запускаем анимацию загрузки для последнего сообщения без ответов
watch(
  lastMessageWithoutReplies,
  (newMessage, oldMessage) => {
    if (newMessage && newMessage.id !== oldMessage?.id) {
      // Появилось новое сообщение без ответов
      loadingMessageId.value = newMessage.id
      startLoadingAnimation()
    } else if (!newMessage && oldMessage) {
      // Больше нет сообщений без ответов
      stopLoadingAnimation()
      loadingMessageId.value = null
    }
  },
  { immediate: true },
)
</script>

<template>
  <div
    ref="scrollContainer"
    class="flex flex-1 flex-col min-h-0 px-6 py-4 overflow-y-auto">
    <div
      v-if="isLoading"
      class="flex flex-col items-center justify-center flex-1">
      <div
        class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      <span class="mt-2 text-sm text-text-secondary"
        >Загрузка истории чата...</span
      >
    </div>
    <div
      v-else-if="!messages.length"
      class="flex flex-col items-center justify-center flex-1">
      <i-custom-robot-original class="w-24 h-24" />
      <span class="text-xl">Чем я могу помочь?</span>
    </div>
    <div v-else class="flex flex-col gap-2.5">
      <div
        v-for="message in messages"
        :key="message.id"
        class="flex flex-col gap-4">
        <div class="flex justify-end">
          <div
            class="flex flex-col max-w-[300px] bg-surface-0 rounded-2xl p-4 gap-2.5">
            <div
              v-if="
                message.context_documents && message.context_documents.length
              "
              v-for="document in message.context_documents"
              :key="document.id"
              class="flex items-center gap-2.5 w-full h-14 p-3 rounded-xl bg-[#EDEFF6]">
              <i-custom-doc class="text-primary" />
              <div>
                {{ document.name || document }}
              </div>
            </div>
            <div>
              {{ message.message }}
            </div>
          </div>
        </div>

        <!-- Блок с фразами - показывается для сообщений без ответов или с ответами -->
        <div
          v-if="
            shouldShowLoadingForMessage(message) ||
            (message.replies && message.replies.length)
          "
          class="flex items-center gap-2 py-3 relative">
          <i-custom-robot-original class="w-[40px] h-[40px]" />
          <span class="italic transition-all duration-300">
            <!-- Анимирующиеся фразы только для последнего сообщения без ответов -->
            <template v-if="shouldShowLoadingForMessage(message)">
              {{ currentPhrase }}
            </template>
            <!-- Статичная фраза для сообщений с ответами -->
            <template v-else> Вот что я нашел по этому вопросу </template>
          </span>
          <Divider
            type="solid"
            class="absolute bottom-0 left-0 m-0 before:bg-[#CFCFDB]" />
        </div>

        <!-- Ответы бота -->
        <div
          v-for="reply in message.replies"
          :key="reply.id"
          class="flex items-center gap-2.5 pb-4 relative">
          <div class="text-sm text-text-secondary whitespace-pre-line">
            <!-- Анимированный текст -->
            <div v-if="getReplyAnimation(reply.id)" class="leading-relaxed">
              <span
                v-for="(word, index) in getReplyAnimation(reply.id)
                  .animatedWords"
                :key="index"
                :class="getReplyAnimation(reply.id).getWordClass(word)"
                class="mr-1">
                {{ word.text }}
              </span>
            </div>
            <!-- Обычный текст -->
            <div v-else>
              {{ reply.message }}
            </div>
          </div>
          <Divider
            type="solid"
            class="absolute bottom-0 left-0 m-0 before:bg-[#CFCFDB]" />
        </div>
      </div>
    </div>
  </div>
</template>
