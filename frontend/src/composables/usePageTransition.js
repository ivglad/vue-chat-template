import { ref, computed, nextTick, readonly } from 'vue'
import { AnimatePresence } from 'motion-v'

/**
 * Композабл для поочередной анимации элементов при переходах между страницами
 * @param {Object} options - настройки анимации
 * @returns {Object} - объект с методами и состоянием для анимации
 */
export function usePageTransition(options = {}) {
  // Настройки по умолчанию
  const defaultConfig = {
    staggerDelay: 0.05, // Задержка между анимацией элементов (в секундах)
    exitDuration: 0.15, // Длительность анимации исчезновения
    enterDuration: 0.2, // Длительность анимации появления
    exitDelay: 0, // Задержка перед началом исчезновения
    enterDelay: 0.1, // Задержка перед началом появления
    maxStaggerItems: 20, // Максимальное количество элементов для поочередной анимации
  }

  const config = { ...defaultConfig, ...options }

  // Состояние перехода
  const isTransitioning = ref(false)
  const currentPhase = ref('idle') // 'idle', 'exiting', 'entering'

  /**
   * Получение пропсов анимации для элемента на основе его индекса
   * @param {number} index - индекс элемента (для вычисления задержки)
   * @param {string} phase - фаза анимации ('exit' или 'enter')
   * @param {Object} customVariants - кастомные варианты анимации
   * @returns {Object} - пропсы для motion компонента
   */
  const getElementAnimationProps = (
    index,
    phase = 'enter',
    customVariants = {},
  ) => {
    const limitedIndex = Math.min(index, config.maxStaggerItems - 1)
    const staggerDelay = limitedIndex * config.staggerDelay

    // Базовые варианты анимации
    const baseVariants = {
      exit: {
        opacity: 0,
        y: -10,
        scale: 0.98,
        filter: 'blur(2px)',
        ...customVariants.exit,
      },
      initial: {
        opacity: 0,
        y: 15,
        scale: 0.95,
        filter: 'blur(4px)',
        ...customVariants.initial,
      },
      enter: {
        opacity: 1,
        y: 0,
        scale: 1,
        filter: 'blur(0px)',
        ...customVariants.enter,
      },
    }

    const transitions = {
      exit: {
        duration: config.exitDuration,
        delay: phase === 'exit' ? config.exitDelay + staggerDelay : 0,
        ease: [0.4, 0, 0.2, 1],
      },
      enter: {
        duration: config.enterDuration,
        delay: phase === 'enter' ? config.enterDelay + staggerDelay : 0,
        ease: [0, 0, 0.2, 1],
      },
    }

    return {
      initial: 'initial',
      animate: phase === 'enter' ? 'enter' : 'exit',
      exit: 'exit',
      variants: baseVariants,
      transition: transitions[phase] || transitions.enter,
    }
  }

  /**
   * Получение пропсов для контейнера AnimatePresence
   * @returns {Object} - пропсы для AnimatePresence
   */
  const getContainerProps = () => ({
    mode: 'wait',
    onExitComplete: () => {
      if (currentPhase.value === 'exiting') {
        currentPhase.value = 'entering'
      }
    },
  })

  /**
   * Начинает переход между страницами
   * @param {Function} callback - функция, вызываемая при переходе
   */
  const startTransition = async (callback) => {
    isTransitioning.value = true
    currentPhase.value = 'exiting'

    // Дожидаемся завершения анимации исчезновения
    const exitTime =
      config.exitDelay +
      config.maxStaggerItems * config.staggerDelay +
      config.exitDuration +
      50 // Небольшой буфер

    setTimeout(() => {
      if (callback) callback()
      nextTick(() => {
        currentPhase.value = 'entering'

        // Завершаем переход после анимации появления
        const enterTime =
          config.enterDelay +
          config.maxStaggerItems * config.staggerDelay +
          config.enterDuration +
          50

        setTimeout(() => {
          isTransitioning.value = false
          currentPhase.value = 'idle'
        }, enterTime)
      })
    }, exitTime)
  }

  /**
   * Получение CSS классов для Tailwind анимации (альтернатива motion)
   * @param {number} index - индекс элемента
   * @param {string} phase - фаза анимации
   * @returns {string} - строка CSS классов
   */
  const getTailwindClasses = (index, phase = 'enter') => {
    const limitedIndex = Math.min(index, config.maxStaggerItems - 1)
    const delay = limitedIndex * 50 // Tailwind поддерживает задержки по 50ms

    const baseClasses = 'transition-all duration-200 ease-out'

    if (phase === 'exit') {
      return `${baseClasses} opacity-0 -translate-y-2 scale-98 delay-[${delay}ms]`
    }

    return `${baseClasses} opacity-100 translate-y-0 scale-100 delay-[${
      delay + 100
    }ms]`
  }

  // Вычисляемые свойства для состояния
  const isExiting = computed(() => currentPhase.value === 'exiting')
  const isEntering = computed(() => currentPhase.value === 'entering')
  const isIdle = computed(() => currentPhase.value === 'idle')

  return {
    // Состояние
    isTransitioning: readonly(isTransitioning),
    currentPhase: readonly(currentPhase),
    isExiting,
    isEntering,
    isIdle,

    // Методы
    getElementAnimationProps,
    getContainerProps,
    startTransition,
    getTailwindClasses,

    // Конфигурация
    config: readonly(config),
  }
}

/**
 * Вспомогательная функция для быстрого создания анимированного элемента
 * @param {Object} props - пропсы элемента
 * @returns {Object} - готовые пропсы для motion компонента
 */
export function createAnimatedElement(props = {}) {
  const {
    index = 0,
    phase = 'enter',
    customVariants = {},
    className = '',
    ...restProps
  } = props

  const { getElementAnimationProps } = usePageTransition()
  const animationProps = getElementAnimationProps(index, phase, customVariants)

  return {
    ...animationProps,
    class: className,
    ...restProps,
  }
}
