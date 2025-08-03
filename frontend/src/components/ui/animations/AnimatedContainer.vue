<script setup>
import { motion } from 'motion-v'

const props = defineProps({
  preset: {
    type: String,
    default: 'fadeIn',
  },
  delay: {
    type: Number,
    default: 0,
  },
  overrides: {
    type: Object,
    default: () => ({}),
  },
  containerClass: {
    type: [String, Array, Object],
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const { createAnimationProps } = useChatAnimations()

const animationProps = computed(() => {
  if (props.disabled) {
    return {}
  }

  return createAnimationProps(props.preset, props.overrides, props.delay)
})
</script>

<template>
  <motion.div v-bind="animationProps" :class="containerClass">
    <slot />
  </motion.div>
</template>
