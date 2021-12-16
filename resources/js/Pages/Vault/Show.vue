<style lang="scss" scoped>

</style>

<template>
  <layout title="Dashboard" :inside-vault="true" :layout-data="layoutData">
    <main class="sm:mt-24 relative">
      d
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';

export default {
  components: {
    Layout,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Array,
      default: () => [],
    },
  },

  data() {
    return {
      addMode: false,
    };
  },

  methods: {
  },
};
</script>
