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
  enableAnimation: {
    type: Boolean,
    default: true,
  },
})

const {
  animatedWords,
  isAnimating,
  animateText,
  resetAnimation,
  getWordClass,
} = useTextAnimation()

const { parseMarkdown } = useMarkdownParser()

const shouldAnimateText = computed(() => {
  return (
    props.type === 'bot' &&
    props.enableAnimation &&
    !props.isLocal &&
    props.content.length > 0
  )
})

const formattedContent = computed(() => {
  if (props.type === 'user') {
    return props.content
  }

  // Парсим markdown для ответов бота
  return parseMarkdown(props.content)
})

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
          fadeInDuration: 250,
        })
      }, 100)
    }
  },
  { immediate: true },
)

onMounted(() => {
  if (shouldAnimateText.value && props.content) {
    animateText(props.content, {
      wordDelay: 80,
      fadeInDuration: 250,
    })
  }
})
</script>

<template>
  <div class="text-base leading-relaxed">
    <div
      v-if="type === 'user'"
      class="text-gray-900 whitespace-pre-wrap break-words"
      v-text="content" />

    <div v-else-if="shouldAnimateText" class="text-gray-800">
      <span
        v-for="(word, index) in animatedWords"
        :key="index"
        :class="getWordClass(word)"
        class="inline-block mr-1 transition-all duration-200 ease-out">
        {{ word.text }}
      </span>
    </div>

    <div
      v-else
      class="text-gray-800 prose prose-sm max-w-none [&_h1]:font-semibold [&_h1]:text-gray-900 [&_h1]:mt-4 [&_h1]:mb-2 [&_h1]:text-xl [&_h2]:font-semibold [&_h2]:text-gray-900 [&_h2]:mt-4 [&_h2]:mb-2 [&_h2]:text-lg [&_h3]:font-semibold [&_h3]:text-gray-900 [&_h3]:mt-4 [&_h3]:mb-2 [&_h3]:text-base [&_p]:mb-3 [&_p:last-child]:mb-0 [&_ul]:ml-4 [&_ul]:mb-3 [&_ol]:ml-4 [&_ol]:mb-3 [&_li]:mb-1 [&_code]:bg-surface-100 [&_code]:text-gray-800 [&_code]:px-1 [&_code]:py-0.5 [&_code]:rounded [&_code]:text-sm [&_code]:font-mono [&_pre]:bg-surface-100 [&_pre]:p-3 [&_pre]:rounded-lg [&_pre]:overflow-x-auto [&_pre]:mb-3 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_blockquote]:border-l-4 [&_blockquote]:border-surface-300 [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:text-surface-600 [&_blockquote]:mb-3 [&_strong]:font-semibold [&_strong]:text-gray-900 [&_em]:italic [&_a]:text-primary-600 [&_a:hover]:text-primary-700 [&_a]:underline"
      v-html="formattedContent" />
  </div>
</template>
