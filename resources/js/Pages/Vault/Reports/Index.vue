<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';

defineProps({
  layoutData: Object,
  data: Object,
});
</script>

<template>
  <Layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-md px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <h2 class="mb-6 text-center text-lg">{{ $t('All the reports') }}</h2>
        <div class="mb-12 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
          <ul>
            <li class="mb-2 flex justify-start">
              <Link :href="data.url.addresses" class="text-blue-500 hover:underline">
                {{ $t('List of addresses of the contacts in the vault') }}
              </Link>
            </li>
            <li class="mb-2 flex justify-start">
              <Link :href="data.url.mood_tracking_events" class="text-blue-500 hover:underline">
                {{ $t('Mood tracking events') }}
              </Link>
            </li>
            <li class="flex justify-start">
              <Link :href="data.url.important_date_summary" class="text-blue-500 hover:underline">
                {{ $t('Important date summary') }}
              </Link>
            </li>
          </ul>
        </div>
      </div>
    </main>
  </Layout>
</template>
