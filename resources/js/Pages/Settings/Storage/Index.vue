<template>
  <layout
    title="Dashboard"
    :layout-data="layoutData"
  >
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <InertiaLink
                :href="data.url.settings.index"
                class="text-blue-500 hover:underline"
              >
                {{
                  $t('Settings')
                }}
              </InertiaLink>
            </li>
            <li class="relative me-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </li>
            <li class="inline">
              {{ $t('Storage') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 flex items-center justify-between">
          <h3>
            <span class="me-1">
              📸
            </span>
            {{ $t('Storage') }}
          </h3>
        </div>

        <!-- stats -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg grid grid-cols-2 mb-8">
          <!-- account limit -->
          <div class="flex justify-between p-3 border-e border-gray-200 dark:border-gray-700">
            <p>{{ $t('Your account limits') }}</p>
            <p class="font-bold">
              {{ data.account_limit }}
            </p>
          </div>

          <!-- current usage -->
          <div class="flex justify-between p-3">
            <p>{{ $t('Your account current usage') }}</p>
            <p class="font-bold">
              {{ data.statistics.total }} <span class="text-gray-500 font-normal text-sm">({{ data.statistics.total_percent }}%)</span>
            </p>
          </div>
        </div>

        <!-- detail breakdown -->
        <p class="mb-2">
          <span class="me-1">🔽</span> {{ $t('Breakdown of the current usage') }}
        </p>
        <ul class="user-list mb-6 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
          <li class="border-b border-gray-200 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-900 flex justify-between p-3">
            <p>{{ $t('Photos') }}</p>
            <p>{{ data.statistics.photo.total }} <span class="text-gray-500 font-normal text-sm">({{ data.statistics.photo.size }} - {{ data.statistics.photo.total_percent }}%)</span></p>
          </li>
          <li class="border-b border-gray-200 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-900 flex justify-between p-3">
            <p>{{ $t('Documents') }}</p>
            <p>{{ data.statistics.document.total }} <span class="text-gray-500 font-normal text-sm">({{ data.statistics.document.size }} - {{ data.statistics.document.total_percent }}%)</span></p>
          </li>
          <li class="border-b border-gray-200 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-900 flex justify-between p-3">
            <p>{{ $t('Avatars') }}</p>
            <p>{{ data.statistics.avatar.total }} <span class="text-gray-500 font-normal text-sm">({{ data.statistics.avatar.size }} - {{ data.statistics.avatar.total_percent }}%)</span></p>
          </li>
        </ul>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';

export default {
  components: {
    InertiaLink: Link,
    Layout,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
  },
};
</script>

<style lang="scss" scoped>
.user-list {
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

  .icon-mail {
    top: -1px;
  }
}
</style>
