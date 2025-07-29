import { ref, readonly, onUnmounted } from 'vue'

export function useLoadingPhrases() {
  const currentPhrase = ref('Вот что я нашел по этому вопросу') // оригинальная фраза по умолчанию
  const phraseIndex = ref(0)
  const intervalId = ref(null)
  const isAnimating = ref(false)

  // Короткие фразы для поиска (максимум 2 слова)
  const searchPhrases = [
    'Ищу...',
    'Анализирую...',
    'Обрабатываю...',
    'Сканирую...',
    'Изучаю...',
    'Проверяю...',
    'Думаю...',
    'Читаю...',
  ]

  const startLoadingAnimation = () => {
    if (isAnimating.value) return // предотвращаем множественный запуск

    isAnimating.value = true
    phraseIndex.value = 0
    currentPhrase.value = searchPhrases[0]

    // Меняем фразы каждые 1.2 секунды
    intervalId.value = setInterval(() => {
      if (isAnimating.value) {
        phraseIndex.value = (phraseIndex.value + 1) % searchPhrases.length
        currentPhrase.value = searchPhrases[phraseIndex.value]
      }
    }, 1200)
  }

  const stopLoadingAnimation = () => {
    isAnimating.value = false

    if (intervalId.value) {
      clearInterval(intervalId.value)
      intervalId.value = null
    }

    // Плавный переход к оригинальной фразе с небольшой задержкой
    setTimeout(() => {
      currentPhrase.value = 'Вот что я нашел по этому вопросу'
    }, 150) // Небольшая задержка для плавности
  }

  const cleanup = () => {
    isAnimating.value = false
    if (intervalId.value) {
      clearInterval(intervalId.value)
      intervalId.value = null
    }
  }

  // Очистка при размонтировании
  onUnmounted(() => {
    cleanup()
  })

  return {
    currentPhrase: readonly(currentPhrase),
    isAnimating: readonly(isAnimating),
    startLoadingAnimation,
    stopLoadingAnimation,
    cleanup,
  }
}
