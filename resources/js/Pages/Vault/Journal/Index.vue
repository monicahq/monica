<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import { CalendarDays } from 'lucide-vue-next';
defineProps({
  layoutData: Object,
  data: Object,
});
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-4xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 flex items-center justify-between">
          <h3>
            <span class="me-1"> ✍️ </span>
            {{ $t('All the journals') }}
          </h3>

          <pretty-link
            v-if="layoutData.vault.permission.at_least_editor"
            :href="data.url.create"
            :text="$t('Create a journal')"
            :icon="'plus'" />
        </div>

        <div v-if="data.journals.length !== 0">
          <ul
            class="journal-list mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="journal in data.journals"
              :key="journal.id"
              class="border-b border-gray-200 px-5 py-4 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800 sm:flex">
              <!-- name and date -->
              <div class="sm:me-8">
                <Link :href="journal.url.show" class="mb-1 block font-semibold text-blue-500 hover:underline">{{
                  journal.name
                }}</Link>

                <div v-if="journal.last_updated" class="mb-2 flex items-center gap-2 text-sm sm:mb-0">
                  <CalendarDays class="h-4 w-4 text-gray-400" />

                  {{ $t('Updated on :date', { date: journal.last_updated }) }}
                </div>
              </div>

              <p v-if="journal.description">{{ journal.description }}</p>
            </li>
          </ul>
        </div>

        <!-- blank state -->
        <div
          v-if="data.journals.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <img src="/img/journal_blank.svg" :alt="$t('Journal')" class="mx-auto mt-4 h-44 w-44" />
          <p class="px-5 pb-5 pt-2 text-center">
            {{ $t('Create a journal to document your life.') }}
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
