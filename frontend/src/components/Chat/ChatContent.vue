<script setup>
import { useMarkdownParser } from '@/composables/useMarkdownParser'

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

// Композабл для парсинга markdown
const {
  parse: parseMarkdown,
  getTokenClasses,
  isBlockToken,
  TOKEN_TYPES,
} = useMarkdownParser()

const messages = computed(() => {
  if (props.messagesHistory.length)
    return [...props.messagesHistory].filter(
      (message) => message.type === 'user',
    )
  return []
})

const scrollContainer = ref(null)
const robotBlocks = ref(new Map()) // Refs для блоков с роботом

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
    scrollContainer.value.scrollTo({
      top: scrollContainer.value.scrollHeight,
      behavior: 'smooth',
    })
  }
}

// Прокрутка к блоку с роботом для конкретного сообщения
const scrollToRobotBlock = (messageId) => {
  const robotBlock = robotBlocks.value.get(messageId)
  if (robotBlock && scrollContainer.value) {
    const containerRect = scrollContainer.value.getBoundingClientRect()
    const blockRect = robotBlock.getBoundingClientRect()

    // Вычисляем позицию для прокрутки (блок будет виден внизу контейнера)
    const scrollTop =
      scrollContainer.value.scrollTop +
      blockRect.bottom -
      containerRect.bottom +
      20

    scrollContainer.value.scrollTo({
      top: scrollTop,
      behavior: 'smooth',
    })
  }
}

// Функция для сохранения ref блока с роботом
const setRobotBlockRef = (messageId) => (el) => {
  if (el) {
    robotBlocks.value.set(messageId, el)
  } else {
    robotBlocks.value.delete(messageId)
  }
}

// Функция для создания анимации для конкретного ответа
const createReplyAnimation = (replyId, text) => {
  const { animatedWords, animateText, getWordClass } = useTextAnimation()

  // Парсим markdown в токены
  const tokens = parseMarkdown(text)

  replyAnimations.value.set(replyId, {
    animatedWords,
    getWordClass,
    isAnimated: true,
    isReady: true, // Добавляем флаг готовности анимации
    tokens, // Добавляем распарсенные токены
  })

  // Запускаем анимацию
  animateText(text, { wordDelay: 80, fadeInDuration: 250 })
}

// Функция для создания placeholder анимации (предотвращает прыжок верстки)
const createReplyPlaceholder = (replyId, text = '') => {
  // Парсим markdown даже для placeholder
  const tokens = text ? parseMarkdown(text) : []

  replyAnimations.value.set(replyId, {
    animatedWords: ref([]),
    getWordClass: () => '',
    isAnimated: true,
    isReady: false, // Анимация еще не готова
    tokens, // Добавляем токены для корректного рендера
  })
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

// Прокручиваем к блоку с роботом при добавлении новых пользовательских сообщений
watch(
  () => messages.value.length,
  (newLength, oldLength) => {
    // Если добавилось новое сообщение пользователя
    if (newLength > oldLength) {
      const lastMessage = messages.value[messages.value.length - 1]
      if (lastMessage) {
        nextTick(() => {
          // Ждем появления блока с роботом и прокручиваем к нему
          setTimeout(() => {
            scrollToRobotBlock(lastMessage.id)
          }, 200) // Даем время для рендера блока с роботом
        })
      }
    }
  },
)

// Прокручиваем вниз при изменении сообщений (для ответов ИИ)
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

            // Получаем только новые ответы
            const newReplies = message.replies.slice(oldRepliesCount)

            // Сразу создаем placeholder для всех новых ответов (предотвращает прыжок верстки)
            newReplies.forEach((reply) => {
              createReplyPlaceholder(reply.id, reply.message)
            })

            // Анимируем только новые ответы с минимальной задержкой для плавности
            newReplies.forEach((reply, index) => {
              setTimeout(() => {
                createReplyAnimation(reply.id, reply.message)
              }, 150 + index * 200) // Уменьшенная задержка для плавности
            })

            // Прокрутка вниз после получения ответов
            nextTick(() => {
              setTimeout(() => {
                scrollToBottom()
              }, 300 + newReplies.length * 200) // Прокрутка после завершения анимации ответов
            })
          }
        }
      })
    }
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
    class="flex flex-1 flex-col w-screen max-h-full min-h-0 px-6 py-4 overflow-y-auto">
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

        <div
          v-if="
            shouldShowLoadingForMessage(message) ||
            (message.replies && message.replies.length)
          "
          :ref="setRobotBlockRef(message.id)"
          class="flex items-center gap-2 py-3 relative">
          <i-custom-robot-original class="w-[40px] h-[40px]" />
          <span class="italic transition-all duration-300">
            <template v-if="shouldShowLoadingForMessage(message)">
              {{ currentPhrase }}
            </template>
            <template v-else> Вот что я нашел по этому вопросу </template>
          </span>
          <Divider
            type="solid"
            class="absolute bottom-0 left-0 m-0 before:bg-[#CFCFDB]" />
        </div>

        <div
          v-for="reply in message.replies"
          :key="reply.id"
          class="flex items-center gap-2.5 pb-4 relative">
          <div class="text-sm text-text-secondary">
            <!-- Анимированный рендер с markdown -->
            <div
              v-if="getReplyAnimation(reply.id)?.isReady"
              class="leading-relaxed">
              <template
                v-for="(token, index) in getReplyAnimation(reply.id).tokens"
                :key="index">
                <!-- Перенос строки -->
                <br v-if="token.type === TOKEN_TYPES.LINE_BREAK" />
                <!-- Параграф -->
                <div
                  v-else-if="token.type === TOKEN_TYPES.PARAGRAPH"
                  class="mb-4"></div>
                <!-- Ссылка -->
                <a
                  v-else-if="token.type === TOKEN_TYPES.LINK"
                  :href="token.url"
                  target="_blank"
                  rel="noopener noreferrer"
                  :class="getTokenClasses(token)">
                  {{ token.content }}
                </a>
                <!-- Остальные inline элементы -->
                <span
                  v-else-if="!isBlockToken(token)"
                  :class="getTokenClasses(token)">
                  {{ token.content }}
                </span>
              </template>
            </div>

            <!-- Placeholder для анимации -->
            <div
              v-else-if="
                getReplyAnimation(reply.id) &&
                !getReplyAnimation(reply.id).isReady
              "
              class="leading-relaxed opacity-0">
              <template
                v-for="(token, index) in getReplyAnimation(reply.id).tokens"
                :key="index">
                <br v-if="token.type === TOKEN_TYPES.LINE_BREAK" />
                <div
                  v-else-if="token.type === TOKEN_TYPES.PARAGRAPH"
                  class="mb-4"></div>
                <a
                  v-else-if="token.type === TOKEN_TYPES.LINK"
                  :class="getTokenClasses(token)">
                  {{ token.content }}
                </a>
                <span
                  v-else-if="!isBlockToken(token)"
                  :class="getTokenClasses(token)">
                  {{ token.content }}
                </span>
              </template>
            </div>

            <!-- Fallback для сообщений без анимации -->
            <div v-else class="leading-relaxed">
              <template
                v-for="(token, index) in parseMarkdown(reply.message)"
                :key="index">
                <br v-if="token.type === TOKEN_TYPES.LINE_BREAK" />
                <div
                  v-else-if="token.type === TOKEN_TYPES.PARAGRAPH"
                  class="mb-4"></div>
                <a
                  v-else-if="token.type === TOKEN_TYPES.LINK"
                  :href="token.url"
                  target="_blank"
                  rel="noopener noreferrer"
                  :class="getTokenClasses(token)">
                  {{ token.content }}
                </a>
                <span
                  v-else-if="!isBlockToken(token)"
                  :class="getTokenClasses(token)">
                  {{ token.content }}
                </span>
              </template>
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
