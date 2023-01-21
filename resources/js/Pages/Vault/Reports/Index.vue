<script setup>
import { Link } from '@inertiajs/inertia-vue3';
import Layout from '@/Shared/Layout.vue';

defineProps({
  layoutData: Object,
  data: Object,
});
</script>

<template>
  <Layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-md px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <h2 class="mb-6 text-center text-lg">All the reports</h2>
        <div class="mb-12 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
          <ul>
            <li class="mb-2 flex justify-start">
              <Link :href="data.url.mood_tracking_events" class="text-blue-500 hover:underline">
                Mood tracking events
              </Link>
            </li>
            <li class="flex justify-start">
              <Link :href="data.url.important_date_summary" class="text-blue-500 hover:underline">
                Important date summary
              </Link>
            </li>
          </ul>
        </div>
      </div>
    </main>
  </Layout>
</template>

<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

.item-list {
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
