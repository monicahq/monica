<style scoped>
</style>

<template>
  <layout title="Home" :no-menu="true" :notifications="notifications">
    <p>index</p>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';

export default {
  components: {
    Layout,
  },

  props: {
    employees: {
      type: Array,
      default: null,
    },
    notifications: {
      type: Array,
      default: null,
    },
  },
};
</script>
