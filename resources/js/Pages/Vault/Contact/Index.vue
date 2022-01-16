<style lang="scss" scoped>
.contact-list {
  li:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  li:last-child {
    border-bottom: 0;
  }

  li:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }

  .icon-mail {
    top: -1px;
  }
}
</style>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="sm:mt-24 relative">
      <div class="max-w-6xl mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="flex items-center justify-between mb-6">
          <h3>
            <span class="mr-1">
              🥸
            </span> All contacts in the vault
          </h3>
          <pretty-link v-if="layoutData.vault.permission.at_least_editor" :href="data.url.contact.create" :text="'Add a contact'" :icon="'plus'" />
        </div>

        <!-- contact list -->
        <ul class="bg-white border border-gray-200 rounded-lg mb-6 contact-list">
          <li v-for="contact in data.contacts" :key="contact.id" class="px-5 py-2 border-b border-gray-200 hover:bg-slate-50">
            <inertia-link :href="contact.url.show" class="text-sky-500 hover:text-blue-900">{{ contact.name }}</inertia-link>
          </li>
        </ul>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyLink from '@/Shared/PrettyLink';

export default {
  components: {
    Layout,
    PrettyLink,
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

  data() {
    return {
    };
  },

  methods: {
  },
};
</script>
