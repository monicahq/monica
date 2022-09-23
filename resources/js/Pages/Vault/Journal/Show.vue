<script setup>
import Layout from '@/Shared/Layout.vue';

defineProps({
  layoutData: Object,
  data: Object,
});
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    {{ data.name }}
  </layout>
</template>

<style lang="scss" scoped></style>
