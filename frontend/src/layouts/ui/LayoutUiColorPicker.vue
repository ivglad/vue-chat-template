<script setup>
const colorValue = ref(null)

// Определение вариантов и состояний для выбора цвета
const colorPickerStates = ['Default', 'Disabled']
const colorPickerVariants = ['Default', 'Inline']
</script>

<template>
  <LayoutUiTemplate
    title="ColorPicker"
    :states="colorPickerStates"
    :variants="colorPickerVariants">
    <!-- Default -->
    <template #default-default>
      <ColorPicker v-model="colorValue" />
    </template>
    <template #default-disabled>
      <ColorPicker v-model="colorValue" disabled />
    </template>

    <!-- Inline -->
    <template #inline-default>
      <div>
        <ColorPicker v-model="colorValue" inline />
        <div>
          Color:
          <span v-if="colorValue">{{ JSON.stringify(colorValue) }}</span>
        </div>
      </div>
    </template>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>
