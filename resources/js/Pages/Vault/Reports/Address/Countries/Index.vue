<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import ContactCard from '@/Shared/ContactCard.vue';

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
            <li class="me-2 inline">
              <Link :href="data.url.addresses" class="text-blue-500 hover:underline">{{
                $t('List of addresses')
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
            <li class="me-2 inline">{{ data.country }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-18 relative">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div>
            <h2 class="mb-3 font-bold">{{ data.country }}</h2>

            <div
              v-if="data.wikipedia.description"
              class="rounded-xs border border-gray-200 p-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <p class="mb-3">{{ data.wikipedia.description }}</p>

              <img :src="data.wikipedia.thumbnail" :alt="'Image of ' + data.country" class="mb-2 rounded-xs" />

              <p class="text-xs">
                <a :href="data.wikipedia.url" target="_blank">{{ $t('Information from Wikipedia') }}</a>
              </p>
            </div>
          </div>

          <!-- right -->
          <div>
            <ul class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <li
                v-for="address in data.addresses"
                :key="address.id"
                class="item-list border-b border-gray-200 px-3 pb-2 pt-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                <span class="mb-1 block">{{ address.address }}</span>
                <ul class="list">
                  <li v-for="contact in address.contacts" :key="contact.id" class="me-3 inline">
                    <contact-card
                      :contact="contact"
                      :avatar-classes="'h-5 w-5 rounded-full me-2'"
                      :display-name="true" />
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </main>
  </Layout>
</template>

<style lang="scss" scoped>
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

.special-grid {
  grid-template-columns: 200px 1fr;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}
</style>
