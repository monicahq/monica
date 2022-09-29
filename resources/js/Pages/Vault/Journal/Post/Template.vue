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
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('app.breadcrumb_location') }}
            </li>
            <li class="mr-2 inline">
              <inertia-link :href="layoutData.vault.url.journals" class="text-blue-500 hover:underline">
                {{ $t('app.breadcrumb_journal_index') }}
              </inertia-link>
            </li>
            <li class="relative mr-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">
              <inertia-link :href="data.url.back" class="text-blue-500 hover:underline">
                {{ data.journal.name }}
              </inertia-link>
            </li>
            <li class="relative mr-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">
              {{ $t('app.breadcrumb_post_create_template') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-lg px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <h3 class="mb-4">Please choose a template for this new post</h3>
        <ul class="mb-6 rounded-lg border border-gray-200 dark:border-gray-800 dark:bg-gray-900">
          <li
            v-for="template in data.templates"
            :key="template.id"
            class="template-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-800 hover:dark:bg-slate-900">
            <div class="flex items-center justify-between">
              <div>
                <p class="font-semibold">{{ template.label }}</p>
                <p class="text-sm text-gray-500">{{ template.sections.length }} template sections</p>
              </div>

              <!-- choose button -->
              <pretty-link
                v-if="layoutData.vault.permission.at_least_editor"
                :href="template.url.create"
                :text="$t('app.choose')"
                :icon="'plus'" />
            </div>
          </li>
        </ul>
      </div>
    </main>
  </layout>
</template>

<style lang="scss" scoped>
.template-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
