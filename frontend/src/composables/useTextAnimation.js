import { ref, readonly } from 'vue'

export function useTextAnimation() {
  const animatedWords = ref([])
  const isAnimating = ref(false)

  const animateText = (text, options = {}) => {
    const {
      wordDelay = 150, // задержка между словами в мс
      fadeInDuration = 400, // длительность появления каждого слова
    } = options

    if (!text) return

    // Разбиваем текст на слова
    const words = text.split(' ').filter((word) => word.trim())

    // Инициализируем массив слов с невидимым состоянием
    animatedWords.value = words.map((word) => ({
      text: word,
      visible: false,
      animating: false,
    }))

    isAnimating.value = true

    // Последовательно показываем каждое слово
    words.forEach((word, index) => {
      setTimeout(() => {
        if (animatedWords.value[index]) {
          animatedWords.value[index].animating = true

          // Небольшая задержка для начала анимации
          setTimeout(() => {
            if (animatedWords.value[index]) {
              animatedWords.value[index].visible = true
            }
          }, 50)

          // Завершаем анимацию
          setTimeout(() => {
            if (animatedWords.value[index]) {
              animatedWords.value[index].animating = false
            }

            // Если это последнее слово
            if (index === words.length - 1) {
              isAnimating.value = false
            }
          }, fadeInDuration)
        }
      }, index * wordDelay)
    })
  }

  const resetAnimation = () => {
    animatedWords.value = []
    isAnimating.value = false
  }

  const getWordClass = (word) => {
    const baseClass = 'inline-block transition-all duration-200 ease-out'
    if (word.animating) {
      return `${baseClass} opacity-0 transform translate-x-2`
    }
    if (word.visible) {
      return `${baseClass} opacity-100 transform translate-x-0`
    }
    return `${baseClass} opacity-0`
  }

  return {
    animatedWords: readonly(animatedWords),
    isAnimating: readonly(isAnimating),
    animateText,
    resetAnimation,
    getWordClass,
  }
}
