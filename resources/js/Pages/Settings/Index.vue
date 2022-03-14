<style lang="scss" scoped></style>

<template>
  <layout title="Dashboard" :layout-data="layoutData">
    <nav class="bg-white sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline">You are here:</li>
            <li class="inline">Settings</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-md px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- user settings -->
        <h2 class="mb-6 text-center text-lg">User settings</h2>
        <div class="mb-12 rounded-lg border border-gray-200 bg-white p-5">
          <ul>
            <li class="mb-2">
              <span class="mr-2">🥳</span>
              <inertia-link :href="data.url.preferences.index" class="text-sky-500 hover:text-blue-900">
                User preferences
              </inertia-link>
            </li>
            <li>
              <span class="mr-2">📡</span>
              <inertia-link :href="data.url.notifications.index" class="text-sky-500 hover:text-blue-900">
                Notification channels
              </inertia-link>
            </li>
          </ul>
        </div>

        <!-- account settings -->
        <div v-if="data.is_account_administrator">
          <h2 class="mb-6 text-center text-lg">Account settings</h2>
          <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5">
            <ul>
              <li class="mb-2">
                <span class="mr-2">🥸</span>
                <inertia-link :href="data.url.users.index" class="text-sky-500 hover:text-blue-900">
                  Manage users
                </inertia-link>
              </li>
              <li class="mb-2">
                <span class="mr-2">🎃</span>
                <inertia-link :href="data.url.personalize.index" class="text-sky-500 hover:text-blue-900">
                  Personalize your contacts data
                </inertia-link>
              </li>
              <li>
                <span class="mr-2">💩</span>
                <inertia-link :href="data.url.cancel.index" class="text-sky-500 hover:text-blue-900">
                  Cancel your account
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
