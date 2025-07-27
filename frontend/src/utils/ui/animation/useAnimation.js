import { ref } from 'vue'

/**
 * Композабл для создания анимаций layout элементов с использованием motion
 * @param {Object} options - настройки анимации
 * @returns {Object} - объект с методами для получения пропсов анимации
 */
export function useAnimation(options = {}) {
  // Настройки анимации по умолчанию
  const defaultConfig = {
    // Длительность анимации layout (смещение)
    layoutDuration: 0.5,
    // Длительность анимации появления/исчезновения
    fadeDuration: 0.2,
    // Задержка появления контента после смещения
    enterDelay: 0.3,
    // Тип пружинной анимации
    bounce: 0.2,
    // Сила противодействия. При значении 0 пружина будет колебаться бесконечно
    damping: 10,
    // Масса движущегося объекта. Большие значения создают более медленное движение
    mass: 0.35,
    // Жесткость пружины. Высокие значения создают более резкое движение
    stiffness: 105,
    // Завершить анимацию если абсолютная скорость (в единицах в секунду) ниже этого значения и дельта меньше restDelta
    restSpeed: 0.1,
    // Завершить анимацию если расстояние ниже этого значения и скорость ниже restSpeed. При завершении анимации пружина остановится
    restDelta: 0.01,
    // Масштаб при появлении/исчезновении
    scale: 0.95,
  }

  // Слияние настроек по умолчанию и пользовательских настроек
  const config = ref({ ...defaultConfig, ...options })

  /**
   * Получение пропсов для анимации компонента
   * @param {String} key - уникальный ключ для компонента
   * @param {String} layoutType - тип layout анимации ('position', 'size', true)
   * @returns {Object} - объект с пропсами для анимации
   */
  const getAnimationProps = (key, layoutType = 'position') => {
    return {
      key,
      layout: layoutType,
      initial: { opacity: 0, scale: config.value.scale },
      animate: { opacity: 1, scale: 1 },
      exit: { opacity: 0, scale: config.value.scale },
      transition: {
        layout: {
          type: 'spring',
          bounce: config.value.bounce,
          damping: config.value.damping,
          mass: config.value.mass,
          stiffness: config.value.stiffness,
          restSpeed: config.value.restSpeed,
          restDelta: config.value.restDelta,
          duration: config.value.layoutDuration,
        },
        opacity: {
          duration: config.value.fadeDuration,
          // При выходе элемент исчезает быстрее, чем смещаются другие
          exit: { duration: config.value.fadeDuration * 0.6 },
        },
        scale: {
          duration: config.value.fadeDuration,
          // При появлении сначала смещение, затем появление элемента
          enter: { delay: config.value.enterDelay },
        },
      },
    }
  }

  /**
   * Получение пропсов для анимации компонента с layoutId
   * @param {String} layoutId - уникальный ID для shared layout анимации
   * @returns {Object} - объект с пропсами для анимации
   */
  const getSharedAnimationProps = (layoutId) => {
    return {
      layoutId,
      initial: { opacity: 0, scale: config.value.scale },
      animate: { opacity: 1, scale: 1 },
      exit: { opacity: 0, scale: config.value.scale },
      transition: {
        layout: {
          type: 'spring',
          bounce: config.value.bounce,
          damping: config.value.damping,
          mass: config.value.mass,
          stiffness: config.value.stiffness,
          restSpeed: config.value.restSpeed,
          restDelta: config.value.restDelta,
          duration: config.value.layoutDuration,
        },
        opacity: {
          duration: config.value.fadeDuration,
          exit: { duration: config.value.fadeDuration * 0.6 },
        },
        scale: {
          duration: config.value.fadeDuration,
          enter: { delay: config.value.enterDelay },
        },
      },
    }
  }

  /**
   * Обновление настроек анимации
   * @param {Object} newConfig - новые настройки анимации
   */
  const updateConfig = (newConfig) => {
    config.value = { ...config.value, ...newConfig }
  }

  return {
    config,
    getAnimationProps,
    getSharedAnimationProps,
    updateConfig,
  }
}
