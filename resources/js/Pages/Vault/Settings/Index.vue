<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="sm:mt-28 relative">
      <div class="max-w-3xl mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <default-template :data="data" />

        <delete :data="data" />
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import Delete from '@/Pages/Vault/Settings/Partials/Delete';
import DefaultTemplate from '@/Pages/Vault/Settings/Partials/DefaultTemplate';

export default {
  components: {
    Layout,
    Delete,
    DefaultTemplate,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
  },
};
</script>
