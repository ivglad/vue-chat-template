<script setup>
import { motion } from 'motion-v'

const props = defineProps({
  message: {
    type: Object,
    required: true,
  },
})
// Композабл для анимированных фраз поиска
const { currentPhrase, startAnimation, stopAnimation, isAnimating } =
  useSearchPhrases()

// Вычисляем состояние на основе существующих полей сообщения
const isLoading = computed(() => {
  return props.message.isLoading || props.message.status === 'loading'
})

// Управление анимацией в зависимости от состояния загрузки
watch(
  isLoading,
  (newValue) => {
    if (newValue) {
      startAnimation()
    } else {
      stopAnimation()
    }
  },
  { immediate: true },
)

// Текст разделителя с учетом анимации
const separatorText = computed(() => {
  if (isLoading.value && isAnimating.value) {
    return currentPhrase.value
  }
  if (isLoading.value) {
    return 'Обрабатываю запрос...'
  }
  if (props.message.status === 'error') {
    return props.message.loadingText || 'Произошла ошибка :('
  }
  return 'Вот что я нашёл по этому вопросу'
})

// Очистка анимации при размонтировании
onUnmounted(() => {
  stopAnimation()
})
</script>

<template>
  <div class="assistant-title flex flex-col gap-2.5 mt-8">
    <div class="flex flex-1 items-center gap-2.5">
      <i-custom-robot-original class="w-[40px] h-[40px] flex-shrink-0" />
      <motion.span
        :key="separatorText"
        class="italic"
        :class="{ 'gradient-animate': isLoading }">
        {{ separatorText }}
      </motion.span>
    </div>
    <Divider class="assistant-divider-start w-full bg-surface-400 mt-0" />
  </div>
</template>

<style scoped>
.gradient-animate {
  background: linear-gradient(
    90deg,
    #2d2e2f,
    #4a4b5c,
    #676889,
    #aaaad9,
    #676889,
    #4a4b5c,
    #2d2e2f
  );
  background-size: 200% 100%;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  color: transparent;
  animation: gradient-flow 1s ease-in-out infinite;
}

@keyframes gradient-flow {
  0% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0 50%;
  }
}

/* Fallback для браузеров без поддержки background-clip: text */
@supports not (-webkit-background-clip: text) {
  .gradient-animate {
    color: inherit;
  }
}
</style>
