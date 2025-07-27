<script setup>
const checkboxState = ref(false)

// Определение вариантов и состояний для чекбоксов
const checkboxStates = ['Default', 'Disabled']
const checkboxVariants = ['Large', 'Default', 'Small']
</script>

<template>
  <LayoutUiTemplate
    title="Checkboxes"
    :states="checkboxStates"
    :variants="checkboxVariants">
    <!-- Large -->
    <template #large-default>
      <Checkbox v-model="checkboxState" size="large" binary />
    </template>
    <template #large-disabled>
      <Checkbox v-model="checkboxState" size="large" binary disabled />
    </template>

    <!-- Default -->
    <template #default-default>
      <label class="app-checkbox">
        <Checkbox v-model="checkboxState" binary />
        <Message
          class="app-checkbox-message"
          variant="simple"
          severity="contrast">
          Checkbox
        </Message>
      </label>
    </template>
    <template #default-disabled>
      <label class="app-checkbox">
        <Checkbox v-model="checkboxState" binary disabled />
        <Message
          class="app-checkbox-message"
          variant="simple"
          severity="contrast">
          Checkbox
        </Message>
      </label>
    </template>

    <!-- Small -->
    <template #small-default>
      <Checkbox v-model="checkboxState" size="small" binary />
    </template>
    <template #small-disabled>
      <Checkbox v-model="checkboxState" size="small" binary disabled />
    </template>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>
