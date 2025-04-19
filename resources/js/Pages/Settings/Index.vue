<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';

defineProps({
  layoutData: Object,
  data: Object,
});
</script>

<template>
  <Layout title="Dashboard" :layout-data="layoutData">
    <nav class="bg-white dark:bg-gray-900 sm:border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline">
              {{ $t('You are here:') }}
            </li>
            <li class="inline">
              {{ $t('Settings') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-md px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- user settings -->
        <h2 class="mb-6 text-center text-lg">
          {{ $t('User settings') }}
        </h2>

        <div class="mb-12 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
          <ul>
            <li class="mb-2 flex justify-start">
              <span class="me-2">🥳</span>
              <Link :href="data.url.preferences.index" class="text-blue-500 hover:underline">
                {{ $t('User preferences') }}
              </Link>
            </li>
            <li class="mb-2 flex justify-start">
              <span class="me-2">📡</span>
              <Link :href="data.url.notifications.index" class="text-blue-500 hover:underline">
                {{ $t('Notification channels') }}
              </Link>
            </li>
            <li class="mb-2 flex justify-start">
              <span class="me-2">🔐</span>
              <Link :href="route('profile.show')" class="text-blue-500 hover:underline">
                {{ $t('Profile and security') }}
              </Link>
            </li>
            <li class="flex justify-start">
              <span class="me-2">⚓</span>
              <Link :href="route('api-tokens.index')" class="text-blue-500 hover:underline">
                {{ $t('API Tokens') }}
              </Link>
            </li>
          </ul>
        </div>

        <!-- account settings -->
        <div v-if="data.is_account_administrator">
          <h2 class="mb-6 text-center text-lg">
            {{ $t('Account settings') }}
          </h2>
          <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
            <ul>
              <li class="mb-2 flex justify-start">
                <span class="me-2">😀</span>
                <Link :href="data.url.users.index" class="text-blue-500 hover:underline">
                  {{ $t('Manage users') }}
                </Link>
              </li>
              <li class="mb-2 flex justify-start">
                <span class="me-2">🎃</span>
                <Link :href="data.url.personalize.index" class="text-blue-500 hover:underline">
                  {{ $t('Personalize your account') }}
                </Link>
              </li>
              <li class="mb-2 flex justify-start">
                <span class="me-2">📸</span>
                <Link :href="data.url.storage.index" class="text-blue-500 hover:underline">
                  {{ $t('Manage storage') }}
                </Link>
              </li>
              <li class="flex justify-start">
                <span class="me-2">💩</span>
                <Link :href="data.url.cancel.index" class="text-blue-500 hover:underline">
                  {{ $t('Cancel your account') }}
                </Link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </main>
  </Layout>
</template>
