<script setup>
import Layout from '@/Shared/Layout.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';

defineProps({
  layoutData: Object,
  data: Object,
});
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-4xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 flex items-center justify-between">
          <h3>
            <span class="mr-1"> ✍️ </span>
            {{ $t('vault.journal_index_title') }}
          </h3>

          <pretty-link
            v-if="layoutData.vault.permission.at_least_editor"
            :href="data.url.create"
            :text="$t('vault.journal_index_create')"
            :icon="'plus'" />
        </div>

        <div v-if="data.journals.length != 0">
          <ul
            class="journal-list mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="journal in data.journals"
              :key="journal.id"
              class="border-b border-gray-200 px-5 py-4 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800 sm:flex">
              <!-- name and date -->
              <div class="sm:mr-8">
                <inertia-link :href="journal.url.show" class="mb-1 block font-semibold text-blue-500 hover:underline">{{
                  journal.name
                }}</inertia-link>

                <div v-if="journal.last_updated" class="mb-2 flex items-center text-sm sm:mb-0">
                  <span class="mr-1">
                    <svg
                      class="h-4 w-4 text-gray-500"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke-width="1.5"
                      stroke="currentColor">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </span>

                  {{ $t('vault.journal_index_last_updated', { date: journal.last_updated }) }}
                </div>
              </div>

              <p v-if="journal.description">{{ journal.description }}</p>
            </li>
          </ul>
        </div>

        <!-- blank state -->
        <div
          v-if="data.journals.length == 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <img src="/img/journal_blank.svg" :alt="$t('Journal')" class="mx-auto mt-4 h-44 w-44" />
          <p class="px-5 pb-5 pt-2 text-center">
            {{ $t('vault.journal_index_blank') }}
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<style lang="scss" scoped>
.journal-list {
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
}
</style>
