<template>
  <layout
    title="Dashboard"
    :layout-data="layoutData"
  >
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('app.breadcrumb_location') }}
            </li>
            <li class="mr-2 inline">
              <inertia-link
                :href="data.url.settings.index"
                class="text-blue-500 hover:underline"
              >
                {{
                  $t('app.breadcrumb_settings')
                }}
              </inertia-link>
            </li>
            <li class="relative mr-2 inline">
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
              {{ $t('app.breadcrumb_settings_storage') }}
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
            <span class="mr-1">
              📸
            </span>
            {{ $t('settings.storage_title') }}
          </h3>
        </div>

        <!-- stats -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg grid grid-cols-2 mb-8">
          <!-- account limit -->
          <div class="flex justify-between p-3 border-r border-gray-200 dark:border-gray-700">
            <p>{{ $t('settings.storage_account_limit') }}</p>
            <p class="font-bold">
              {{ data.account_limit }}
            </p>
          </div>

          <!-- current usage -->
          <div class="flex justify-between p-3">
            <p>{{ $t('settings.storage_account_current_usage') }}</p>
            <p class="font-bold">
              {{ data.statistics.total }} <span class="text-gray-500 font-normal text-sm">({{ data.statistics.total_percent }}%)</span>
            </p>
          </div>
        </div>

        <!-- detail breakdown -->
        <p class="mb-2">
          <span class="mr-1">🔽</span> Breakdown of the current usage
        </p>
        <ul class="user-list mb-6 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
          <li class="border-b border-gray-200 dark:border-gray-700 hover:bg-slate-50 hover:dark:bg-slate-800 dark:bg-slate-900 flex justify-between p-3">
            <p>{{ $t('settings.storage_type_photo') }}</p>
            <p>{{ data.statistics.photo.total }} <span class="text-gray-500 font-normal text-sm">({{ data.statistics.photo.size }} - {{ data.statistics.photo.total_percent }}%)</span></p>
          </li>
          <li class="border-b border-gray-200 dark:border-gray-700 hover:bg-slate-50 hover:dark:bg-slate-800 dark:bg-slate-900 flex justify-between p-3">
            <p>{{ $t('settings.storage_type_document') }}</p>
            <p>{{ data.statistics.document.total }} <span class="text-gray-500 font-normal text-sm">({{ data.statistics.document.size }} - {{ data.statistics.document.total_percent }}%)</span></p>
          </li>
          <li class="border-b border-gray-200 dark:border-gray-700 hover:bg-slate-50 hover:dark:bg-slate-800 dark:bg-slate-900 flex justify-between p-3">
            <p>{{ $t('settings.storage_type_avatar') }}</p>
            <p>{{ data.statistics.avatar.total }} <span class="text-gray-500 font-normal text-sm">({{ data.statistics.avatar.size }} - {{ data.statistics.avatar.total_percent }}%)</span></p>
          </li>
        </ul>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout.vue';

export default {
  components: {
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
