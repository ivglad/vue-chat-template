<script setup>
import { motion, AnimatePresence } from 'motion-v'

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  itemPreset: {
    type: String,
    default: 'fadeIn',
  },
  staggerDelay: {
    type: Number,
    default: 0.1,
  },
  keyExtractor: {
    type: Function,
    default: (item, index) => item.id || index,
  },
  containerClass: {
    type: [String, Array, Object],
    default: '',
  },
  itemClass: {
    type: [String, Array, Object],
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const { createAnimationProps, VARIANTS } = useChatAnimations()

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

const containerProps = computed(() => {
  if (props.disabled) {
    return {}
  }

  // Используем staggered анимацию для контейнера
  return VARIANTS.staggeredList.container
})
</script>

<template>
  <motion.div v-bind="containerProps" :class="containerClass">
    <AnimatePresence>
      <motion.div
        v-for="(item, index) in items"
        :key="getItemKey(item, index)"
        v-bind="getItemProps(item, index)"
        :class="itemClass">
        <slot :item="item" :index="index" name="item" />
      </motion.div>
    </AnimatePresence>
  </motion.div>
</template>
