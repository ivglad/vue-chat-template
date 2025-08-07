/**
 * Предустановленные анимации для приложения
 * Следует принципу Configuration over Code
 * Обеспечивает консистентность анимаций по всему приложению
 */

// ============================================================================
// Animation Presets
// ============================================================================

export const ANIMATION_PRESETS = {
  // Анимации для сообщений чата
  messageAppear: {
    initial: { opacity: 0, y: 20, scale: 0.95 },
    animate: { opacity: 1, y: 0, scale: 1 },
    exit: { opacity: 0, y: -10, scale: 0.95 },
    transition: {
      duration: 0.3,
      ease: 'easeOut',
      layout: { duration: 0.2 },
    },
  },

  messageSlideIn: {
    initial: { opacity: 0, x: -30 },
    animate: { opacity: 1, x: 0 },
    exit: { opacity: 0, x: 30 },
    transition: { duration: 0.25, ease: 'easeOut' },
  },

  replyAppear: {
    initial: { opacity: 0, y: 10, scale: 0.98 },
    animate: { opacity: 1, y: 0, scale: 1 },
    exit: { opacity: 0, y: -5, scale: 0.98 },
    transition: {
      duration: 0.2,
      ease: 'easeOut',
      delay: 0.1,
    },
  },

  // Анимации для документов
  documentSlide: {
    initial: { opacity: 0, x: -20, scale: 0.95 },
    animate: { opacity: 1, x: 0, scale: 1 },
    exit: { opacity: 0, x: 20, scale: 0.95 },
    transition: { duration: 0.2, ease: 'easeOut' },
  },

  documentPop: {
    initial: { opacity: 0, scale: 0.8 },
    animate: { opacity: 1, scale: 1 },
    exit: { opacity: 0, scale: 0.8 },
    transition: {
      duration: 0.15,
      ease: 'easeOut',
      type: 'spring',
      stiffness: 400,
      damping: 25,
    },
  },

  // Анимации для UI элементов
  fadeIn: {
    initial: { opacity: 0 },
    animate: { opacity: 1 },
    exit: { opacity: 0 },
    transition: { duration: 0.2 },
  },

  slideUp: {
    initial: { opacity: 0, y: 20 },
    animate: { opacity: 1, y: 0 },
    exit: { opacity: 0, y: -20 },
    transition: { duration: 0.25, ease: 'easeOut' },
  },

  slideDown: {
    initial: { opacity: 0, y: -20 },
    animate: { opacity: 1, y: 0 },
    exit: { opacity: 0, y: 20 },
    transition: { duration: 0.25, ease: 'easeOut' },
  },

  scaleIn: {
    initial: { opacity: 0, scale: 0.9 },
    animate: { opacity: 1, scale: 1 },
    exit: { opacity: 0, scale: 0.9 },
    transition: { duration: 0.2, ease: 'easeOut' },
  },

  // Анимации для загрузки
  pulse: {
    animate: {
      scale: [1, 1.05, 1],
      opacity: [0.7, 1, 0.7],
    },
    transition: {
      duration: 1.5,
      repeat: Infinity,
      ease: 'easeInOut',
    },
  },

  // Анимация для разделителя чата
  separatorPulse: {
    initial: { opacity: 0.6 },
    animate: {
      opacity: [0.6, 1, 0.6],
    },
    transition: {
      duration: 1.2,
      repeat: Infinity,
      ease: 'easeInOut',
    },
  },

  bounce: {
    animate: {
      y: [0, -10, 0],
    },
    transition: {
      duration: 0.6,
      repeat: Infinity,
      ease: 'easeInOut',
    },
  },

  // Layout анимации
  layoutShift: {
    layout: true,
    transition: {
      duration: 0.3,
      ease: 'easeOut',
      type: 'spring',
      stiffness: 300,
      damping: 30,
    },
  },

  // ============================================================================
  // Chat State Transitions (для ChatMessagesList)
  // ============================================================================

  chatStateTransition: {
    initial: { opacity: 0, y: 20, scale: 0.95 },
    animate: { opacity: 1, y: 0, scale: 1 },
    exit: { opacity: 0, y: -10, scale: 0.95 },
    transition: {
      duration: 0.25,
      ease: [0.25, 0.46, 0.45, 0.94], // gentle easing
    },
  },

  // ============================================================================
  // Page Transitions (для App.vue)
  // ============================================================================

  pageTransitionFade: {
    initial: { opacity: 0, scale: 0.95, filter: 'blur(4px)' },
    animate: { opacity: 1, scale: 1, filter: 'blur(0px)' },
    exit: { opacity: 0, scale: 0.95, filter: 'blur(4px)' },
    transition: {
      duration: 0.3,
      ease: [0.25, 0.46, 0.45, 0.94], // gentle easing
    },
  },

  pageTransitionAuthToChat: {
    initial: { opacity: 0, scale: 0.9, filter: 'blur(6px)', y: 20 },
    animate: { opacity: 1, scale: 1, filter: 'blur(0px)', y: 0 },
    exit: { opacity: 0, scale: 0.9, filter: 'blur(6px)', y: -20 },
    transition: {
      duration: 0.4,
      ease: [0.23, 1, 0.32, 1], // более плавная кривая для больших переходов
    },
  },

  pageTransitionChatToAuth: {
    initial: { opacity: 0, scale: 0.9, filter: 'blur(6px)', y: 20 },
    animate: { opacity: 1, scale: 1, filter: 'blur(0px)', y: 0 },
    exit: { opacity: 0, scale: 0.9, filter: 'blur(6px)', y: -20 },
    transition: {
      duration: 0.4,
      ease: [0.23, 1, 0.32, 1], // более плавная кривая для больших переходов
    },
  },
}

// ============================================================================
// Animation Timing Constants
// ============================================================================

export const ANIMATION_TIMING = {
  // Быстрые анимации для мелких элементов
  fast: 0.15,

  // Стандартные анимации для большинства элементов
  normal: 0.25,

  // Медленные анимации для крупных элементов
  slow: 0.4,

  // Задержки
  delays: {
    short: 0.05,
    medium: 0.1,
    long: 0.2,
  },
}

// ============================================================================
// Easing Functions
// ============================================================================

export const EASING = {
  // Стандартные easing функции
  easeOut: 'easeOut',
  easeIn: 'easeIn',
  easeInOut: 'easeInOut',

  // Кастомные easing функции
  smooth: [0.25, 0.1, 0.25, 1],
  snappy: [0.4, 0, 0.2, 1],
  gentle: [0.25, 0.46, 0.45, 0.94],
}

// ============================================================================
// Animation Variants for Complex Sequences
// ============================================================================

export const ANIMATION_VARIANTS = {
  // Последовательное появление элементов списка
  staggeredList: {
    container: {
      animate: {
        transition: {
          staggerChildren: 0.1,
          delayChildren: 0.1,
        },
      },
    },
    item: {
      initial: { opacity: 0, y: 20 },
      animate: { opacity: 1, y: 0 },
      exit: { opacity: 0, y: -20 },
      transition: { duration: 0.3, ease: 'easeOut' },
    },
  },

  // Анимация печатающего текста
  typewriter: {
    container: {
      animate: {
        transition: {
          staggerChildren: 0.05,
        },
      },
    },
    letter: {
      initial: { opacity: 0 },
      animate: { opacity: 1 },
      transition: { duration: 0.1 },
    },
  },
}
