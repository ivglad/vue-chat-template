import { ref, computed } from 'vue'
import { ANIMATION_PRESETS, ANIMATION_TIMING, ANIMATION_VARIANTS } from './useAnimationPresets'

/**
 * Композабл для управления анимациями в чате
 * Предоставляет простой API для применения анимаций к элементам чата
 * Следует принципам DRY и Configuration over Code
 */
export function useChatAnimations() {
  // ============================================================================
  // State
  // ============================================================================
  
  const activeAnimations = ref(new Map())
  const animationQueue = ref([])
  
  // ============================================================================
  // Animation Helpers
  // ============================================================================
  
  /**
   * Создать анимационные пропсы на основе пресета
   * @param {string} presetName - название пресета
   * @param {Object} overrides - переопределения параметров
   * @param {number} delay - задержка анимации
   * @returns {Object} пропсы для motion компонента
   */
  const createAnimationProps = (presetName, overrides = {}, delay = 0) => {
    const preset = ANIMATION_PRESETS[presetName]
    
    if (!preset) {
      console.warn(`Animation preset "${presetName}" not found`)
      return ANIMATION_PRESETS.fadeIn
    }
    
    // Применяем задержку если указана
    const transition = { ...preset.transition }
    if (delay > 0) {
      transition.delay = delay
    }
    
    return {
      ...preset,
      ...overrides,
      transition: {
        ...transition,
        ...overrides.transition
      }
    }
  }
  
  /**
   * Получить анимационные пропсы для сообщения
   * @param {Object} message - объект сообщения
   * @param {number} index - индекс сообщения в списке
   * @returns {Object} анимационные пропсы
   */
  const getMessageAnimationProps = (message, index = 0) => {
    const baseDelay = index * 0.05 // Небольшая задержка для каждого сообщения
    
    if (message.isLocal) {
      // Локальные сообщения появляются быстрее
      return createAnimationProps('messageSlideIn', {
        transition: { duration: 0.2 }
      }, baseDelay)
    }
    
    return createAnimationProps('messageAppear', {}, baseDelay)
  }
  
  /**
   * Получить анимационные пропсы для ответа бота
   * @param {Object} reply - объект ответа
   * @param {number} index - индекс ответа
   * @returns {Object} анимационные пропсы
   */
  const getReplyAnimationProps = (reply, index = 0) => {
    const delay = index * 0.1 + 0.2 // Задержка после появления сообщения
    
    return createAnimationProps('replyAppear', {}, delay)
  }
  
  /**
   * Получить анимационные пропсы для документа
   * @param {Object} document - объект документа
   * @param {number} index - индекс документа
   * @returns {Object} анимационные пропсы
   */
  const getDocumentAnimationProps = (document, index = 0) => {
    const delay = index * 0.05
    
    return createAnimationProps('documentSlide', {}, delay)
  }
  
  // ============================================================================
  // Text Animation
  // ============================================================================
  
  /**
   * Создать анимацию печатающего текста
   * @param {string} text - текст для анимации
   * @param {Object} options - опции анимации
   * @returns {Object} данные для анимации текста
   */
  const createTypewriterAnimation = (text, options = {}) => {
    const {
      speed = 50, // миллисекунды между символами
      startDelay = 0
    } = options
    
    const words = text.split(' ')
    const animatedWords = ref([])
    
    const startAnimation = () => {
      animatedWords.value = words.map(word => ({
        text: word,
        visible: false,
        animating: false
      }))
      
      words.forEach((word, index) => {
        setTimeout(() => {
          if (animatedWords.value[index]) {
            animatedWords.value[index].animating = true
            
            setTimeout(() => {
              if (animatedWords.value[index]) {
                animatedWords.value[index].visible = true
                animatedWords.value[index].animating = false
              }
            }, 50)
          }
        }, startDelay + index * speed)
      })
    }
    
    return {
      animatedWords,
      startAnimation,
      isComplete: computed(() => 
        animatedWords.value.every(word => word.visible)
      )
    }
  }
  
  // ============================================================================
  // Loading Animations
  // ============================================================================
  
  /**
   * Получить анимационные пропсы для индикатора загрузки
   * @param {string} type - тип загрузки ('pulse', 'bounce')
   * @returns {Object} анимационные пропсы
   */
  const getLoadingAnimationProps = (type = 'pulse') => {
    return ANIMATION_PRESETS[type] || ANIMATION_PRESETS.pulse
  }
  
  // ============================================================================
  // Layout Animations
  // ============================================================================
  
  /**
   * Получить пропсы для layout анимации
   * @param {Object} options - опции анимации
   * @returns {Object} анимационные пропсы
   */
  const getLayoutAnimationProps = (options = {}) => {
    return {
      layout: true,
      transition: {
        duration: ANIMATION_TIMING.normal,
        ease: 'easeOut',
        type: 'spring',
        stiffness: 300,
        damping: 30,
        ...options
      }
    }
  }
  
  // ============================================================================
  // Animation Queue Management
  // ============================================================================
  
  /**
   * Добавить анимацию в очередь
   * @param {string} id - уникальный ID анимации
   * @param {Function} animationFn - функция анимации
   * @param {number} delay - задержка перед выполнением
   */
  const queueAnimation = (id, animationFn, delay = 0) => {
    const animation = {
      id,
      fn: animationFn,
      delay,
      timestamp: Date.now()
    }
    
    animationQueue.value.push(animation)
    
    setTimeout(() => {
      executeQueuedAnimation(id)
    }, delay)
  }
  
  /**
   * Выполнить анимацию из очереди
   * @param {string} id - ID анимации
   */
  const executeQueuedAnimation = (id) => {
    const animationIndex = animationQueue.value.findIndex(a => a.id === id)
    
    if (animationIndex !== -1) {
      const animation = animationQueue.value[animationIndex]
      
      // Выполняем анимацию
      animation.fn()
      
      // Отмечаем как активную
      activeAnimations.value.set(id, {
        ...animation,
        startTime: Date.now()
      })
      
      // Удаляем из очереди
      animationQueue.value.splice(animationIndex, 1)
    }
  }
  
  /**
   * Отменить анимацию
   * @param {string} id - ID анимации
   */
  const cancelAnimation = (id) => {
    // Удаляем из очереди
    const queueIndex = animationQueue.value.findIndex(a => a.id === id)
    if (queueIndex !== -1) {
      animationQueue.value.splice(queueIndex, 1)
    }
    
    // Удаляем из активных
    activeAnimations.value.delete(id)
  }
  
  /**
   * Очистить все анимации
   */
  const clearAllAnimations = () => {
    animationQueue.value = []
    activeAnimations.value.clear()
  }
  
  // ============================================================================
  // Computed Properties
  // ============================================================================
  
  const hasActiveAnimations = computed(() => activeAnimations.value.size > 0)
  const hasQueuedAnimations = computed(() => animationQueue.value.length > 0)
  
  // ============================================================================
  // Return
  // ============================================================================
  
  return {
    // Animation creators
    createAnimationProps,
    getMessageAnimationProps,
    getReplyAnimationProps,
    getDocumentAnimationProps,
    getLoadingAnimationProps,
    getLayoutAnimationProps,
    
    // Text animations
    createTypewriterAnimation,
    
    // Animation queue
    queueAnimation,
    executeQueuedAnimation,
    cancelAnimation,
    clearAllAnimations,
    
    // State
    activeAnimations,
    animationQueue,
    hasActiveAnimations,
    hasQueuedAnimations,
    
    // Constants (для использования в компонентах)
    PRESETS: ANIMATION_PRESETS,
    TIMING: ANIMATION_TIMING,
    VARIANTS: ANIMATION_VARIANTS
  }
}