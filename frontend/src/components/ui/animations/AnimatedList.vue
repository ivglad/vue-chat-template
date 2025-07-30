<template>
  <motion.div
    v-bind="containerProps"
    :class="containerClass"
  >
    <AnimatePresence>
      <motion.div
        v-for="(item, index) in items"
        :key="getItemKey(item, index)"
        v-bind="getItemProps(item, index)"
        :class="itemClass"
      >
        <slot 
          :item="item" 
          :index="index"
          name="item"
        />
      </motion.div>
    </AnimatePresence>
  </motion.div>
</template>

<script setup>
import { computed } from 'vue'
import { motion, AnimatePresence } from 'motion-v'
import { useChatAnimations } from '@/composables/animations/useChatAnimations'

/**
 * Анимированный список элементов
 * Поддерживает staggered анимации и переходы
 * Следует принципу Composition - использует слоты для содержимого
 */

// ============================================================================
// Props
// ============================================================================

const props = defineProps({
  /**
   * Массив элементов для отображения
   */
  items: {
    type: Array,
    required: true
  },
  
  /**
   * Название пресета анимации для элементов
   */
  itemPreset: {
    type: String,
    default: 'fadeIn'
  },
  
  /**
   * Задержка между анимациями элементов (stagger)
   */
  staggerDelay: {
    type: Number,
    default: 0.1
  },
  
  /**
   * Функция для получения ключа элемента
   */
  keyExtractor: {
    type: Function,
    default: (item, index) => item.id || index
  },
  
  /**
   * CSS классы для контейнера
   */
  containerClass: {
    type: [String, Array, Object],
    default: ''
  },
  
  /**
   * CSS классы для элементов
   */
  itemClass: {
    type: [String, Array, Object],
    default: ''
  },
  
  /**
   * Отключить анимации
   */
  disabled: {
    type: Boolean,
    default: false
  }
})

// ============================================================================
// Composables
// ============================================================================

const { createAnimationProps, VARIANTS } = useChatAnimations()

// ============================================================================
// Methods
// ============================================================================

/**
 * Получить ключ для элемента списка
 * @param {*} item - элемент списка
 * @param {number} index - индекс элемента
 * @returns {string|number} ключ элемента
 */
const getItemKey = (item, index) => {
  return props.keyExtractor(item, index)
}

/**
 * Получить анимационные пропсы для элемента
 * @param {*} item - элемент списка
 * @param {number} index - индекс элемента
 * @returns {Object} анимационные пропсы
 */
const getItemProps = (item, index) => {
  if (props.disabled) {
    return {}
  }
  
  const delay = index * props.staggerDelay
  return createAnimationProps(props.itemPreset, {}, delay)
}

// ============================================================================
// Computed
// ============================================================================

const containerProps = computed(() => {
  if (props.disabled) {
    return {}
  }
  
  // Используем staggered анимацию для контейнера
  return VARIANTS.staggeredList.container
})
</script>