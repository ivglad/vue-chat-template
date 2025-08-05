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
            // Вызываем callback если он передан
            if (options.onComplete) {
              options.onComplete()
            }
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

  const animateTokens = (tokenWords, options = {}) => {
    const {
      wordDelay = 150, // задержка между словами в мс
      fadeInDuration = 400, // длительность появления каждого слова
    } = options

    if (!tokenWords || tokenWords.length === 0) return

    // Инициализируем массив токенов с невидимым состоянием
    animatedWords.value = tokenWords.map((tokenWord) => ({
      text: tokenWord.text,
      token: tokenWord.token,
      isWord: tokenWord.isWord,
      isBlock: tokenWord.isBlock,
      visible: tokenWord.isBlock, // блочные элементы видны сразу
      animating: false,
    }))

    isAnimating.value = true

    // Последовательно показываем каждое слово
    let wordIndex = 0
    tokenWords.forEach((tokenWord, index) => {
      // Блочные элементы не анимируются
      if (tokenWord.isBlock) {
        return
      }

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
            if (wordIndex === tokenWords.filter(tw => !tw.isBlock).length - 1) {
              isAnimating.value = false
              // Вызываем callback если он передан
              if (options.onComplete) {
                options.onComplete()
              }
            }
          }, fadeInDuration)
        }
      }, wordIndex * wordDelay)

      wordIndex++
    })
  }

  return {
    animatedWords: readonly(animatedWords),
    isAnimating: readonly(isAnimating),
    animateText,
    animateTokens,
    resetAnimation,
    getWordClass,
  }
}
