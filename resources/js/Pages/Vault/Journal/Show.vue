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
              {{ data.name }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-18 relative">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <h1 class="mb-8 text-2xl">{{ data.name }}</h1>

        <!-- cta -->
        <div class="mb-4 flex items-end">
          <pretty-link
            v-if="layoutData.vault.permission.at_least_editor"
            :href="data.url.create"
            :text="$t('vault.journal_show_cta')"
            :icon="'plus'" />
        </div>

        <!-- list of posts -->
        <ul class="post-list mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <li
            v-for="post in data.posts"
            :key="post.id"
            class="border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
            <!-- written at -->
            <span class="mr-2">{{ post.written_at }}</span>

            <!-- content -->
            <inertia-link :href="post.url.show" class="text-blue-500 hover:underline">{{ post.title }}</inertia-link>
          </li>
        </ul>
      </div>
    </main>
  </layout>
</template>

<style lang="scss" scoped>
.post-list {
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
