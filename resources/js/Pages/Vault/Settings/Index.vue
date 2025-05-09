<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-28">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <users :layout-data="layoutData" :data="data" />

        <tab-visibility :layout-data="layoutData" :data="data" />

        <default-template :data="data" />

        <labels :data="data" />

        <contact-important-date-types :data="data" />

        <tags :data="data" />

        <mood-tracking-parameters :data="data" />

        <life-event-types-and-categories :data="data" />

        <quick-fact-template :data="data" />

        <delete :data="data" />
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Layouts/Layout.vue';
import Delete from '@/Pages/Vault/Settings/Partials/Delete.vue';
import DefaultTemplate from '@/Pages/Vault/Settings/Partials/DefaultTemplate.vue';
import Labels from '@/Pages/Vault/Settings/Partials/Labels.vue';
import Tags from '@/Pages/Vault/Settings/Partials/Tags.vue';
import Users from '@/Pages/Vault/Settings/Partials/Users.vue';
import TabVisibility from '@/Pages/Vault/Settings/Partials/TabVisibility.vue';
import ContactImportantDateTypes from '@/Pages/Vault/Settings/Partials/ContactImportantDateTypes.vue';
import MoodTrackingParameters from '@/Pages/Vault/Settings/Partials/MoodTrackingParameters.vue';
import LifeEventTypesAndCategories from '@/Pages/Vault/Settings/Partials/LifeEventTypesAndCategories.vue';
import QuickFactTemplate from '@/Pages/Vault/Settings/Partials/QuickFactTemplate.vue';

export default {
  components: {
    Layout,
    Delete,
    DefaultTemplate,
    Labels,
    Tags,
    Users,
    TabVisibility,
    ContactImportantDateTypes,
    MoodTrackingParameters,
    LifeEventTypesAndCategories,
    QuickFactTemplate,
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

<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>
