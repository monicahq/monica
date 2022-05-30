<style lang="scss" scoped>
.vault-list {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.vault-detail {
  height: 250px;
  grid-template-columns: 1fr;
  grid-template-rows: auto 1fr auto;
}

.remaining-contact {
  top: -1px;
  left: -5px;
}

@media (max-width: 480px) {
  .vault-list {
    grid-template-columns: 1fr;
  }
}

.icon-sidebar {
  color: #737e8d;
  top: -2px;
}
</style>

<template>
  <layout title="Dashboard" :layout-data="layoutData">
    <main class="relative mt-16 sm:mt-24">
      <!-- blank state -->
      <div v-if="data.vaults.length == 0" class="mx-auto mb-6 max-w-md px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="rounded-t-lg border-t border-l border-r border-gray-200 bg-white p-5">
          <p class="text-center">👋</p>
          <h2 class="mb-6 text-center text-lg">Thanks for giving Monica a try</h2>
          <p class="mb-3">Monica was made to help you document your life and your social interactions.</p>
          <p class="mb-3">To start, you need to create a vault.</p>
          <div class="mb-3 text-center">
            <pretty-link :href="data.url.vault.create" :text="'Create a vault'" :icon="'plus'" />
          </div>
        </div>
        <div class="rounded-b-lg border border-gray-200 p-5">
          <p class="mb-3">Monica is open source, made by hundreds of people from all around the world.</p>
          <p class="mb-3">We hope you will like what we've done.</p>
          <p class="mb-3">All the best,</p>
          <p>Régis & Alexis</p>
        </div>
      </div>

      <!-- list of existing vaults -->
      <div v-if="data.vaults.length > 0" class="mx-auto max-w-4xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="mb-10 items-center justify-between sm:mb-6 sm:flex">
          <h3 class="mb-3 dark:text-slate-200 sm:mb-0">All the vaults in the account</h3>
          <pretty-link :href="data.url.vault.create" :text="'Create a vault'" :icon="'plus'" class="w-full md:w-auto" />
        </div>

        <div class="vault-list grid grid-cols-1 gap-6 sm:grid-cols-3">
          <div
            v-for="vault in data.vaults"
            :key="vault.id"
            class="rounded-lg border border-gray-200 bg-white dark:bg-slate-700">
            <div class="vault-detail grid">
              <inertia-link :href="vault.url.show" class="border-b border-gray-200 px-3 py-1 text-lg font-medium">
                {{ vault.name }}
              </inertia-link>

              <!-- description -->
              <div>
                <div v-if="vault.contacts.length > 0" class="relative flex -space-x-2 overflow-hidden p-3">
                  <div v-for="contact in vault.contacts" :key="contact.id" class="inline-block">
                    <div v-html="contact.avatar" class="h-8 w-8 rounded-full ring-2 ring-white"></div>
                  </div>
                  <div
                    v-if="vault.remaining_contacts != 0"
                    class="remaining-contact relative flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-gray-700 text-xs font-medium text-white hover:bg-gray-600 dark:border-gray-800">
                    + {{ vault.remaining_contacts }}
                  </div>
                </div>
                <p v-if="vault.description" class="p-3">
                  {{ vault.description }}
                </p>
                <p v-else class="p-3 text-gray-500">No description yet.</p>
              </div>

              <!-- actions -->
              <div class="flex items-center justify-between border-t border-gray-200 px-3 py-2">
                <inertia-link :href="vault.url.settings">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="pointer h-5 w-5 text-gray-400 hover:text-gray-900"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </inertia-link>

                <inertia-link :href="vault.url.show">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="pointer h-5 w-5 text-gray-400 hover:text-gray-900"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 7l5 5m0 0l-5 5m5-5H6" />
                  </svg>
                </inertia-link>
              </div>
            </div>
          </div>
        </div>
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
      type: Array,
      default: () => [],
    },
  },

  data() {
    return {
      addMode: false,
    };
  },

  methods: {},
};
</script>
