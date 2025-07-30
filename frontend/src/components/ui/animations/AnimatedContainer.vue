<template>
  <motion.div
    v-bind="animationProps"
    :class="containerClass"
  >
    <slot />
  </motion.div>
</template>

<script setup>
import { computed } from 'vue'
import { motion } from 'motion-v'
import { useChatAnimations } from '@/composables/animations/useChatAnimations'

/**
 * Переиспользуемый контейнер с анимацией
 * Следует принципу Single Responsibility - только анимация
 * Использует композабл для получения анимационных пропсов
 */

// ============================================================================
// Props
// ============================================================================

const props = defineProps({
  /**
   * Название пресета анимации
   */
  preset: {
    type: String,
    default: 'fadeIn'
  },
  
  /**
   * Задержка анимации в секундах
   */
  delay: {
    type: Number,
    default: 0
  },
  
  /**
   * Переопределения для анимации
   */
  overrides: {
    type: Object,
    default: () => ({})
  },
  
  /**
   * CSS классы для контейнера
   */
  containerClass: {
    type: [String, Array, Object],
    default: ''
  },
  
  /**
   * Отключить анимацию (для accessibility)
   */
  disabled: {
    type: Boolean,
    default: false
  }
})

// ============================================================================
// Composables
// ============================================================================

const { createAnimationProps } = useChatAnimations()

// ============================================================================
// Computed
// ============================================================================

const animationProps = computed(() => {
  if (props.disabled) {
    return {}
  }
  
  return createAnimationProps(props.preset, props.overrides, props.delay)
})
</script>