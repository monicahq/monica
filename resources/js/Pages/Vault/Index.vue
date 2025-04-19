<template>
  <layout title="Dashboard" :layout-data="layoutData">
    <main class="relative mt-16 sm:mt-24">
      <!-- blank state -->
      <div v-if="data.vaults.length === 0" class="mx-auto mb-6 max-w-md px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="rounded-t-lg border-x border-t border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
          <p class="mb-2 text-center text-xl">👋</p>
          <h2 class="mb-6 text-center text-lg font-semibold">
            {{ $t('Thanks for giving Monica a try.') }}
          </h2>
          <p class="mb-3">
            {{ $t('Monica was made to help you document your life and your social interactions.') }}
          </p>
          <p class="mb-3">
            {{
              $t('To start, you need to create a vault. A vault is a private space where you can store your contacts.')
            }}
          </p>
          <div class="mb-1 text-center">
            <InertiaLink
              :href="data.url.vault.create"
              :text="$t('Create a vault')"
              class="cursor-pointer rounded-md border border-indigo-700 bg-indigo-500 px-3 py-2 font-semibold text-white shadow-xs hover:bg-indigo-700" />
          </div>
        </div>

        <div class="rounded-b-lg border border-gray-200 bg-slate-50 p-5 dark:border-gray-700 dark:bg-slate-900">
          <p class="mb-3">
            {{ $t('Monica is open source, made by hundreds of people from all around the world.') }}
          </p>
          <p class="mb-3">
            {{ $t('We hope you will like what we’ve done.') }}
          </p>
          <p class="mb-3">
            {{ $t('All the best,') }}
          </p>
          <p>
            <a href="https://phpc.social/@regis" rel="noopener noreferrer" class="text-blue-500 hover:underline"
              >Régis</a
            >
            &amp;
            <a href="https://mamot.fr/@asbin" rel="noopener noreferrer" class="text-blue-500 hover:underline">Alexis</a>
          </p>
        </div>
      </div>

      <!-- list of existing vaults -->
      <div v-else class="mx-auto max-w-4xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="mb-10 items-center justify-between sm:mb-6 sm:flex">
          <h3 class="mb-3 dark:text-slate-200 sm:mb-0">
            {{ $t('All the vaults in the account') }}
          </h3>
          <InertiaLink
            :href="data.url.vault.create"
            :text="$t('Create a vault')"
            class="cursor-pointer inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1 font-semibold text-gray-700 hover:shadow-xs transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25" />
        </div>

        <div class="vault-list grid grid-cols-1 gap-6 sm:grid-cols-3">
          <div
            v-for="vault in data.vaults"
            :key="vault.id"
            class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="vault-detail grid relative">
              <div
                class="flex items-center justify-between border-b border-gray-200 hover:rounded-t-lg hover:bg-slate-50 dark:hover:bg-slate-800">
                <InertiaLink
                  :href="vault.url.show"
                  class="px-3 py-1 text-lg font-medium dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300">
                  {{ vault.name }}
                </InertiaLink>

                <!-- Edit button -->
                <InertiaLink
                  :href="vault.url.edit"
                  class="ml-2 px-2 py-1 text-sm font-medium text-blue-600 hover:text-white dark:text-blue-400 dark:border-blue-400 hover:dark:text-slate-900">
                  <Pencil
                    class="cursor-pointer h-4 w-4 text-gray-400 hover:text-gray-900 dark:text-gray-600 hover:dark:text-gray-100" />
                </InertiaLink>
              </div>

              <!-- description -->
              <div>
                <div v-if="vault.contacts.length > 0" class="relative flex -space-x-2 overflow-hidden p-3">
                  <!-- list of contacts -->
                  <div v-for="contact in vault.contacts" :key="contact.id" class="inline-block">
                    <avatar
                      :data="contact.avatar"
                      :class="'h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-900'" />
                  </div>
                  <div
                    v-if="vault.remaining_contacts !== 0"
                    class="relative -start-[5px] -top-px flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-gray-700 text-xs font-medium text-white hover:bg-gray-600 dark:border-gray-800 dark:bg-gray-300 dark:text-gray-900 dark:hover:bg-gray-400">
                    + {{ vault.remaining_contacts }}
                  </div>
                </div>
                <p v-if="vault.description" class="p-3 dark:text-gray-300">
                  {{ vault.description }}
                </p>
                <p v-else class="p-3 text-gray-500">
                  {{ $t('No description yet.') }}
                </p>
              </div>

              <!-- actions -->
              <div class="flex items-center justify-between border-t border-gray-200 px-3 py-2 dark:border-gray-700">
                <InertiaLink :href="vault.url.settings">
                  <Settings
                    class="h-5 w-5 text-gray-400 hover:text-gray-900 dark:text-gray-600 dark:hover:text-gray-100" />
                </InertiaLink>

                <InertiaLink :href="vault.url.show">
                  <ArrowRight
                    class="h-5 w-5 text-gray-400 hover:text-gray-900 dark:text-gray-600 dark:hover:text-gray-100" />
                </InertiaLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import Avatar from '@/Shared/Avatar.vue';
import { Pencil, Settings, ArrowRight } from 'lucide-vue-next';

export default {
  components: {
    InertiaLink: Link,
    Layout,
    Avatar,
    Pencil,
    Settings,
    ArrowRight,
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
      addMode: false,
    };
  },

  methods: {},
};
</script>

<style lang="scss" scoped>
.vault-list {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.vault-detail {
  height: 250px;
  grid-template-columns: 1fr;
  grid-template-rows: auto 1fr auto;
}

@media (max-width: 480px) {
  .vault-list {
    grid-template-columns: 1fr;
  }
}
</style>
