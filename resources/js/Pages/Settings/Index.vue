<template>
  <layout title="Dashboard" :layout-data="layoutData">
    <nav class="bg-white sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline">
              {{ $t('app.breadcrumb_location') }}
            </li>
            <li class="inline">
              {{ $t('app.breadcrumb_settings') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-md px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- user settings -->
        <h2 class="mb-6 text-center text-lg">
          {{ $t('settings.user_settings') }}
        </h2>
        <div class="mb-12 rounded-lg border border-gray-200 bg-white p-5">
          <ul>
            <li class="mb-2 flex justify-start">
              <span class="mr-2">🥳</span>
              <inertia-link :href="data.url.preferences.index" class="text-blue-500 hover:underline">
                {{ $t('settings.user_preferences') }}
              </inertia-link>
            </li>
            <li class="flex justify-start">
              <span class="mr-2">📡</span>
              <inertia-link :href="data.url.notifications.index" class="text-blue-500 hover:underline">
                {{ $t('settings.notification_channels') }}
              </inertia-link>
            </li>
          </ul>
        </div>

        <!-- account settings -->
        <div v-if="data.is_account_administrator">
          <h2 class="mb-6 text-center text-lg">
            {{ $t('settings.account_settings') }}
          </h2>
          <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5">
            <ul>
              <li class="mb-2 flex justify-start">
                <span class="mr-2">🥸</span>
                <inertia-link :href="data.url.users.index" class="text-blue-500 hover:underline">
                  {{ $t('settings.manage_users') }}
                </inertia-link>
              </li>
              <li class="mb-2 flex justify-start">
                <span class="mr-2">🎃</span>
                <inertia-link :href="data.url.personalize.index" class="text-blue-500 hover:underline">
                  {{ $t('settings.personalize_your_contacts_data') }}
                </inertia-link>
              </li>
              <li class="mb-2 flex justify-start">
                <span class="mr-2">📸</span>
                <inertia-link :href="data.url.storage.index" class="text-blue-500 hover:underline">
                  {{ $t('settings.manage_storage') }}
                </inertia-link>
              </li>
              <li class="flex justify-start">
                <span class="mr-2">💩</span>
                <inertia-link :href="data.url.cancel.index" class="text-blue-500 hover:underline">
                  {{ $t('settings.cancel_your_account') }}
                </inertia-link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';

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
      type: Array,
      default: () => [],
    },
  },
};
</script>

<style lang="scss" scoped></style>
