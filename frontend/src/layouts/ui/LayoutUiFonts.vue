<script setup></script>

<template>
  <LayoutUiTemplate title="Fonts">
    <div class="content">
      <div>
        <div class="row">
          <span>Inter</span>
          <span>Regular</span>
          <span class="fw-semibold">Semibold</span>
          <span class="fw-bold">Bold</span>
        </div>
        <span>Fluid text:</span>
        <div class="row">
          <div class="title mr-10">
            <span class="h1 fw-bold">H1:</span>
            <span class="h2 fw-bold">H2:</span>
            <span class="h3 fw-bold">H3 </br> bold:</span>
            <span class="h3 fw-semibold">H3 </br> semibold:</span>
            <span class="h3">H3 </br> regular:</span>
            <span class="h4 fw-bold">H4 </br> bold:</span>
            <span class="h4 fw-semibold">H4 </br> semibold:</span>
            <span class="h4">H4 </br> regular:</span>
            <span class="h5 fw-bold">H5 </br> bold:</span>
            <span class="h5 fw-semibold">H5 </br> semibold:</span>
            <span class="h5">H5 </br> regular:</span>
          </div>
          <div class="text">
            <span class="h1 fw-bold">Система предназначена</span>
            <span class="h2 fw-bold">Система предназначена</span>
            <span class="h3 fw-bold">Система предназначена</span>
            <span class="h3 fw-semibold"> Система предназначена </span>
            <span class="h3">Система предназначена</span>
            <span class="h4 fw-bold">Система предназначена</span>
            <span class="h4 fw-semibold"> Система предназначена </span>
            <span class="h4">Система предназначена</span>
            <span class="h5 fw-bold">Система предназначена</span>
            <span class="h5 fw-semibold"> Система предназначена </span>
            <span class="h5">Система предназначена</span>
          </div>
        </div>
      </div>
    </div>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped>
.title,
.text {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.h1 {
  @include fluid-text(40, 20);
}
.h2 {
  @include fluid-text(24, 18);
}
.h3 {
  @include fluid-text(16, 16);
}
.h4 {
  @include fluid-text(14, 14);
}
.h5 {
  @include fluid-text(12, 12);
}
</style>
