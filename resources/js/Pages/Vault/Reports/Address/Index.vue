<script setup>
import Layout from '@/Layouts/Layout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  layoutData: Object,
  data: Object,
});
</script>

<template>
  <Layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <Link :href="layoutData.vault.url.reports" class="text-blue-500 hover:underline">{{
                $t('Reports')
              }}</Link>
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
            <li class="inline">{{ $t('List of addresses') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-18 relative">
      <div class="mx-auto max-w-xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div>
            <p class="mb-2 font-semibold"><span class="me-1">🌍</span> {{ $t('All the countries') }}</p>

            <!-- list of countries -->
            <ul v-if="data.countries.length > 0">
              <li v-for="country in data.countries" :key="country.id">
                <Link :href="country.url.index" class="text-blue-500 hover:underline">{{ country.name }}</Link> ({{
                  country.contacts
                }})
              </li>
            </ul>

            <!-- blank state -->
            <div
              v-if="data.countries.length === 0"
              class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <p class="p-5 text-center">{{ $t('No countries have been added yet in any contact’s addresses.') }}</p>
            </div>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <p class="mb-2 font-semibold"><span class="me-1">🏙️</span> {{ $t('All the cities') }}</p>

            <!-- list of cities -->
            <ul v-if="data.cities.length > 0">
              <li v-for="city in data.cities" :key="city.id">
                <Link :href="city.url.index" class="text-blue-500 hover:underline">{{ city.name }}</Link> ({{
                  city.contacts
                }})
              </li>
            </ul>

            <!-- blank state -->
            <div
              v-if="data.cities.length === 0"
              class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <p class="p-5 text-center">{{ $t('No cities have been added yet in any contact’s addresses.') }}</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </Layout>
</template>

<style lang="scss" scoped>
.special-grid {
  grid-template-columns: 1fr 1fr;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}
</style>
