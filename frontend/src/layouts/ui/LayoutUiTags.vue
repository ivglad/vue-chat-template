<script setup></script>

<template>
  <LayoutUiTemplate title="Tags">
    <div class="content">
      <div class="tags">
        <Tag value="Primary"></Tag>
        <Tag severity="secondary" value="Secondary"></Tag>
        <Tag severity="success" value="Success"></Tag>
        <Tag severity="info" value="Info"></Tag>
        <Tag severity="warn" value="Warn"></Tag>
        <Tag severity="danger" value="Danger"></Tag>
        <Tag severity="contrast" value="Contrast"></Tag>
      </div>
    </div>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped>
.tags {
  align-items: start;
}
</style>
