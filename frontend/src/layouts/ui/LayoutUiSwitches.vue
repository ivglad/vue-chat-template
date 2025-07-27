<script setup>
const switchesState = ref(false)

// Определение вариантов и состояний для переключателей
const switchStates = ['Default', 'Disabled']
const switchVariants = ['Default']
</script>

<template>
  <LayoutUiTemplate
    title="Switches"
    :states="switchStates"
    :variants="switchVariants">
    <!-- Default -->
    <template #default-default>
      <ToggleSwitch v-model="switchesState" />
    </template>
    <template #default-disabled>
      <ToggleSwitch v-model="switchesState" disabled />
    </template>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>
