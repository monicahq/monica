<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';

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
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <Link :href="layoutData.vault.url.journals" class="text-blue-500 hover:underline">
                {{ $t('Journals') }}
              </Link>
            </li>
            <li class="relative me-2 inline">
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
      <div class="mx-auto max-w-6xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <h1 class="text-2xl" :class="data.description ? 'mb-4' : 'mb-8'">{{ data.name }}</h1>

        <p v-if="data.description" class="mb-8">{{ data.description }}</p>

        <!-- tabs -->
        <div class="flex justify-center">
          <div class="mb-8 inline-flex rounded-md shadow-xs">
            <Link
              :href="data.url.show"
              :class="{ 'bg-gray-100 text-blue-700 dark:bg-gray-400 dark:font-bold': defaultTab === 'activity' }"
              class="inline-flex items-center rounded-s-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-blue-500">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="me-2 h-4 w-4">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
              </svg>

              {{ $t('Journal entries') }}
            </Link>

            <Link
              class="inline-flex items-center rounded-e-md border-y border-e border-gray-200 bg-gray-100 bg-white px-4 py-2 text-sm font-medium text-blue-700 text-gray-900 hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-400 dark:bg-gray-700 dark:font-bold dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-blue-500">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="me-2 h-4 w-4">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
              </svg>

              {{ $t('Photos') }}
            </Link>
          </div>
        </div>

        <div class="flex" v-if="data.photos.length > 0">
          <div
            v-for="photo in data.photos"
            :key="photo.id"
            class="mr-2 cursor-pointer rounded-md border border-gray-200 p-2 shadow-xs hover:bg-slate-50 hover:shadow-lg dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
            <Link :href="photo.url.post">
              <img :src="photo.url.display" :alt="photo.name" />
            </Link>
          </div>
        </div>

        <div v-else class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <img src="/img/journal_photo_index_blank.svg" alt="blank state" class="mx-auto block h-32 w-32 py-6" />
          <p class="p-5 text-center">{{ $t('Add a photo to a journal entry to see it here.') }}</p>
        </div>
      </div>
    </main>
  </layout>
</template>
