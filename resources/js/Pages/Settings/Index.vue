<style lang="scss" scoped>
</style>

<template>
  <Layout title="Dashboard" :layoutData="layoutData">
    <nav class="sm:border-b bg-white">
      <div class="max-w-8xl mx-auto px-4 sm:px-6 py-2 hidden md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li v-if="data.is_account_administrator" class="inline mr-2">You are here:</li>
            <li class="inline">Settings</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-20 relative">
      <div class="max-w-md mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <h2 class="text-lg text-center mb-6">Account settings</h2>
        <div class="bg-white border border-gray-200 rounded-lg mb-6 p-5">
          <ul>
            <li class="mb-2">
              <span class="mr-1">🥳</span>
              <Link :href="data.url.preferences.index" class="text-sky-500 hover:text-blue-900">User preferences</Link>
            </li>
            <li v-if="data.is_account_administrator" class="mb-2">
              <span class="mr-1">🥸</span>
              <Link :href="data.url.users.index" class="text-sky-500 hover:text-blue-900">Manage users</Link>
            </li>
            <li v-if="data.is_account_administrator" class="mb-2">
              <span class="mr-1">🎃</span>
              <Link :href="data.url.personalize.index" class="text-sky-500 hover:text-blue-900">Personalize your contacts data</Link>
            </li>
            <li v-if="data.is_account_administrator">
              <span class="mr-1">💩</span>
              <Link :href="data.url.cancel.index" class="text-sky-500 hover:text-blue-900">Cancel your account</Link>
            </li>
          </ul>
        </div>
      </div>
    </main>
  </Layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import { Link } from '@inertiajs/inertia-vue3';
import PrettyLink from '@/Shared/PrettyLink';

export default {
  components: {
    Layout,
    Link,
    PrettyLink,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Array,
      default: [],
    },
  },
};
</script>
