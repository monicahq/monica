<script setup>
import Layout from '@/Shared/Layout.vue';
import Avatar from '@/Shared/Avatar.vue';

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
              <inertia-link :href="layoutData.vault.url.groups" class="text-blue-500 hover:underline">
                {{ $t('app.breadcrumb_group_index') }}
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
        <!-- group title -->
        <h1 class="mb-2 text-center text-2xl">{{ data.name }}</h1>

        <!-- group information -->
        <div class="mb-8 flex justify-center">
          <!-- number of contacts -->
          <div class="mr-8 flex items-center">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="mr-1 h-4 w-4">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
            </svg>

            <p class="text-center text-gray-600">{{ data.contact_count }} contacts</p>
          </div>

          <!-- type -->
          <div class="flex items-center">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="mr-1 h-4 w-4">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
            </svg>

            <p class="text-center text-gray-600">Group type: {{ data.type.label }}</p>
          </div>
        </div>

        <!-- contacts by roles -->
        <div v-for="role in data.roles" :key="role.id" class="mb-8">
          <p
            class="mr-2 mb-2 inline-block rounded bg-neutral-200 py-1 px-2 text-xs font-semibold text-neutral-800 last:mr-0">
            {{ role.label }}
          </p>

          <div class="grid grid-cols-3 gap-x-12 gap-y-6 sm:grid-cols-4">
            <div
              v-for="contact in role.contacts"
              :key="contact.id"
              class="rounded-lg border border-gray-200 bg-white p-3 text-center hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 hover:dark:bg-slate-800">
              <avatar :data="contact.avatar" :classes="'inline-block rounded-full h-14 w-14'" />

              <inertia-link :href="contact.url" class="text-blue-500 hover:underline">{{ contact.name }}</inertia-link>

              <span v-if="contact.age" class="ml-1 text-xs text-gray-500">({{ contact.age }})</span>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>
