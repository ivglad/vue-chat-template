import { ref, computed, readonly } from 'vue'
import { useIntervalFn } from '@vueuse/core'

/**
 * Композабл для управления анимированными фразами поиска
 * Используется в разделителе чата во время ожидания ответа от сервера
 * Использует машину состояний для плавного перехода к финальной фразе
 */
export function useSearchPhrases() {
  // 20 интересных фраз из 1-2 слов связанных с поиском
  const searchPhrases = [
    'Ищу...',
    'Анализирую...',
    'Сканирую...',
    'Обрабатываю...',
    'Изучаю...',
    'Исследую...',
    'Проверяю...',
    'Читаю...',
    'Думаю...',
    'Вычисляю...',
    'Сопоставляю...',
    'Фильтрую...',
    'Разбираю...',
    'Извлекаю...',
    'Сравниваю...',
    'Классифицирую...',
    'Структурирую...',
    'Синтезирую...',
    'Формулирую...',
    'Готовлю ответ...',
  ]

  // Текущий индекс фразы
  const currentPhraseIndex = ref(0)

  // Машина состояний: 'idle' | 'searching' | 'finalizing' | 'finished'
  const state = ref('idle')

  // Функция для перехода к следующей фразе
  const nextPhrase = () => {
    switch (state.value) {
      case 'searching':
        currentPhraseIndex.value =
          (currentPhraseIndex.value + 1) % searchPhrases.length
        break

      case 'finalizing':
        // Переходим к финальному состоянию
        state.value = 'finished'
        pauseInterval()
        break

      default:
        // Не делаем ничего в других состояниях
        break
    }
  }

  // Интервал для смены фраз (800мс)
  const {
    pause: pauseInterval,
    resume: resumeInterval,
    isActive,
  } = useIntervalFn(nextPhrase, 800, { immediate: false })

  // Вычисляемое свойство для текущей фразы на основе состояния
  const currentPhrase = computed(() => {
    switch (state.value) {
      case 'searching':
        return searchPhrases[currentPhraseIndex.value]
      case 'finalizing':
      case 'finished':
        return 'Вот что я нашёл по этому вопросу'
      default:
        return ''
    }
  })

  // Запуск анимации смены фраз
  const startAnimation = () => {
    state.value = 'searching'
    currentPhraseIndex.value = 0
    resumeInterval()
  }

  // Остановка анимации с плавным переходом
  const stopAnimation = () => {
    // Просто меняем состояние - переключение произойдет при следующем цикле
    if (state.value === 'searching') {
      state.value = 'finalizing'
    }
  }

  // Сброс состояния (для повторного использования)
  const resetAnimation = () => {
    pauseInterval()
    state.value = 'idle'
    currentPhraseIndex.value = 0
  }

  return {
    currentPhrase,
    startAnimation,
    stopAnimation,
    resetAnimation,
    isAnimating: computed(() =>
      ['searching', 'finalizing'].includes(state.value),
    ),
    // Дополнительные состояния для отладки
    state: readonly(state),
    currentIndex: readonly(currentPhraseIndex),
  }
}
