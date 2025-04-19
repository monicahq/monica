<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import Avatar from '@/Shared/Avatar.vue';

defineProps({
  layoutData: Object,
  data: Object,
});
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-4xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <h3 class="mb-6 font-semibold">
          <span class="me-1"> 🏭 </span>
          {{ $t('All the companies') }}
        </h3>

        <div v-if="data.companies.length !== 0">
          <ul class="group-list mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="company in data.companies"
              :key="company.id"
              class="border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <p>{{ company.name }}</p>

              <div v-if="company.contacts" class="relative flex -space-x-2 overflow-hidden py-1">
                <div v-for="contact in company.contacts" :key="contact.id" class="inline-block">
                  <Link :href="contact.url.show">
                    <avatar :data="contact.avatar" :class="'h-8 w-8 rounded-full ring-2 ring-white'" />
                  </Link>
                </div>
              </div>
            </li>
          </ul>
        </div>

        <!-- blank state -->
        <div
          v-if="data.companies.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <img src="/img/vault_company_blank.svg" :alt="$t('Groups')" class="mx-auto mt-4 h-36 w-36" />
          <p class="px-5 pb-5 pt-2 text-center">
            {{ $t('You can add job information to your contacts and manage the companies here in this tab.') }}
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<style lang="scss" scoped>
.group-list {
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
