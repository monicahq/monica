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
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 flex items-center justify-between">
          <h3><span class="mr-1"> 🥸 </span> All contacts in the vault</h3>
          <pretty-link
            v-if="layoutData.vault.permission.at_least_editor"
            :href="data.url.contact.create"
            :text="'Add a contact'"
            :icon="'plus'" />
        </div>

        <!-- contact list -->
        <ul class="contact-list mb-6 rounded-lg border border-gray-200 bg-white">
          <li
            v-for="contact in data.contacts"
            :key="contact.id"
            class="flex items-center border-b border-gray-200 px-5 py-2 hover:bg-slate-50">
            <div v-html="contact.avatar" class="mr-2 h-5 w-5"></div>
            <inertia-link :href="contact.url.show" class="text-blue-500 hover:underline">
              {{ contact.name }}
            </inertia-link>
          </li>
        </ul>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyLink from '@/Shared/Form/PrettyLink';

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
    return {};
  },

  methods: {},
};
</script>
