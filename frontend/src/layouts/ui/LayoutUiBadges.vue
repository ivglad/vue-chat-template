<script setup>
// Определение вариантов и состояний для бейджей
const badgeStates = ['Default']
const badgeVariants = [
  'Default',
  'Xlarge',
  'Large',
  'Small',
  'Dot',
  'Avatar',
  'Avatar with Badge',
]
</script>

<template>
  <LayoutUiTemplate
    title="Badges / Avatar"
    :states="badgeStates"
    :variants="badgeVariants">
    <!-- Default -->
    <template #default-default>
      <OverlayBadge value="35" severity="danger">
        <i-custom-notifications />
      </OverlayBadge>
    </template>

    <!-- Xlarge -->
    <template #xlarge-default>
      <OverlayBadge value="35" severity="warn" size="xlarge">
        <i-custom-notifications />
      </OverlayBadge>
    </template>

    <!-- Large -->
    <template #large-default>
      <OverlayBadge value="35" severity="success" size="large">
        <i-custom-notifications />
      </OverlayBadge>
    </template>

    <!-- Small -->
    <template #small-default>
      <OverlayBadge value="35" severity="info" size="small">
        <i-custom-notifications />
      </OverlayBadge>
    </template>

    <!-- Dot -->
    <template #dot-default>
      <OverlayBadge severity="danger">
        <i-custom-notifications />
      </OverlayBadge>
    </template>

    <!-- Avatar -->
    <template #avatar-default>
      <Avatar
        :label="getInitialsHelper('Линус Бенедикт Торвальдс')"
        size="xlarge"
        shape="circle" />
    </template>

    <!-- Avatar with Badge -->
    <template #avatar-with-badge-default>
      <OverlayBadge value="4" severity="danger">
        <Avatar
          :label="getInitialsHelper('Линус Бенедикт Торвальдс')"
          size="xlarge"
          shape="circle" />
      </OverlayBadge>
    </template>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>
