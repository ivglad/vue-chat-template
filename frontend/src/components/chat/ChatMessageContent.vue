<script setup>
const props = defineProps({
  content: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    required: true,
    validator: (value) => ['user', 'bot'].includes(value),
  },
  isLocal: {
    type: Boolean,
    default: false,
  },
  isNew: {
    type: Boolean,
    default: false,
  },
  messageId: {
    type: [String, Number],
    required: false,
  },
  enableAnimation: {
    type: Boolean,
    default: true,
  },
})

const {
  animatedWords,
  isAnimating,
  animateText,
  animateTokens,
  resetAnimation,
  getWordClass,
} = useTextAnimation()

const { parseMarkdown, parse, tokensToWords, getTokenClasses } =
  useMarkdownParser()

// Импортируем store для сброса флага isNew
const { markMessageAsAnimated } = useChatStore()

const shouldAnimateText = computed(() => {
  return (
    props.type === 'bot' &&
    props.enableAnimation &&
    !props.isLocal &&
    props.isNew && // Анимируем только новые сообщения
    props.content.length > 0
  )
})

// Computed для анимированных токенов markdown
const animatedTokens = computed(() => {
  if (!shouldAnimateText.value || !props.content) {
    return []
  }

  // Парсим markdown в токены
  const tokens = parse(props.content)

  // Конвертируем токены в слова для анимации
  return tokensToWords(tokens)
})

const formattedContent = computed(() => {
  if (props.type === 'user') {
    return props.content
  }

  // Для статического рендера парсим markdown в HTML
  if (!shouldAnimateText.value) {
    return parseMarkdown(props.content)
  }

  // Для анимированного рендера возвращаем исходный текст
  // (парсинг будет происходить в animatedTokens)
  return props.content
})

// Запускаем анимацию при изменении контента
watch(
  () => props.content,
  (newContent) => {
    if (shouldAnimateText.value && newContent) {
      resetAnimation()

      // Небольшая задержка для плавности
      setTimeout(() => {
        // Используем токены для анимации
        animateTokens(animatedTokens.value, {
          wordDelay: 80,
          fadeInDuration: 250,
          onComplete: () => {
            // Сбрасываем флаг isNew после завершения анимации
            if (props.messageId) {
              markMessageAsAnimated(props.messageId)
            }
          },
        })
      }, 100)
    }
  },
  { immediate: true },
)

onMounted(() => {
  if (shouldAnimateText.value && props.content) {
    animateTokens(animatedTokens.value, {
      wordDelay: 80,
      fadeInDuration: 250,
      onComplete: () => {
        // Сбрасываем флаг isNew после завершения анимации
        if (props.messageId) {
          markMessageAsAnimated(props.messageId)
        }
      },
    })
  }
})
</script>

<template>
  <div class="leading-relaxed">
    <div
      v-if="type === 'user'"
      class="whitespace-pre-wrap break-words"
      v-text="content" />

    <div v-else-if="shouldAnimateText">
      <template v-for="(word, index) in animatedWords" :key="index">
        <br v-if="word.token?.type === 'line_break'" />
        <div v-else-if="word.token?.type === 'paragraph'" class="h-4"></div>
        <span
          v-else
          :class="[getWordClass(word), getTokenClasses(word.token)]"
          class="inline-block mr-1">
          {{ word.text }}
        </span>
      </template>
    </div>

    <div v-else v-html="formattedContent" />
    <Divider
      v-if="content && type !== 'user'"
      class="assistant-divider-end bg-surface-400" />
  </div>
</template>
