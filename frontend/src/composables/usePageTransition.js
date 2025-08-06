/**
 * Композабл для управления переходами между страницами
 * Предоставляет анимационные пропсы для элементов с поочередным появлением
 */
export function usePageTransition(options = {}) {
  const {
    staggerDelay = 0.1,
    enterDuration = 0.3,
    enterDelay = 0,
    exitDuration = 0.2,
  } = options

  // Базовые варианты анимации
  const baseVariants = {
    initial: {
      opacity: 0,
      scale: 0.95,
      filter: 'blur(4px)',
      y: 20,
    },
    animate: {
      opacity: 1,
      scale: 1,
      filter: 'blur(0px)',
      y: 0,
    },
    exit: {
      opacity: 0,
      scale: 0.95,
      filter: 'blur(4px)',
      y: -10,
    },
  }

  /**
   * Создать анимированный элемент с заданными пропсами
   * @param {Object} props - дополнительные пропсы для элемента
   * @returns {Object} объект с анимационными пропсами
   */
  const createAnimatedElement = (props = {}) => {
    return {
      ...baseVariants,
      transition: {
        duration: enterDuration,
        ease: [0.25, 0.46, 0.45, 0.94],
        delay: enterDelay,
      },
      ...props,
    }
  }

  /**
   * Получить анимационные пропсы для элемента с учетом индекса
   * @param {number} index - индекс элемента для расчета задержки
   * @returns {Object} анимационные пропсы для motion компонента
   */
  const getElementAnimationProps = (index) => {
    const delay = enterDelay + index * staggerDelay

    return {
      initial: baseVariants.initial,
      animate: baseVariants.animate,
      exit: baseVariants.exit,
      transition: {
        duration: enterDuration,
        ease: [0.25, 0.46, 0.45, 0.94],
        delay: delay,
      },
    }
  }

  /**
   * Получить пропсы для контейнера с поочередной анимацией дочерних элементов
   * @returns {Object} пропсы для motion контейнера
   */
  const getStaggerContainerProps = () => {
    return {
      initial: 'initial',
      animate: 'animate',
      exit: 'exit',
      variants: {
        initial: {},
        animate: {
          transition: {
            staggerChildren: staggerDelay,
            delayChildren: enterDelay,
          },
        },
        exit: {
          transition: {
            staggerChildren: staggerDelay / 2,
            staggerDirection: -1,
          },
        },
      },
    }
  }

  /**
   * Получить пропсы для дочернего элемента в stagger контейнере
   * @returns {Object} пропсы для дочернего motion элемента
   */
  const getStaggerChildProps = () => {
    return {
      variants: {
        initial: baseVariants.initial,
        animate: baseVariants.animate,
        exit: baseVariants.exit,
      },
      transition: {
        duration: enterDuration,
        ease: [0.25, 0.46, 0.45, 0.94],
      },
    }
  }

  return {
    // Методы
    createAnimatedElement,
    getElementAnimationProps,
    getStaggerContainerProps,
    getStaggerChildProps,

    // Базовые варианты для кастомизации
    baseVariants,
  }
}
