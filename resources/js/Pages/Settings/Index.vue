<style lang="scss" scoped>
</style>

<template>
  <Layout title="Dashboard" :layoutData="layoutData">
    <nav class="sm:border-b bg-white">
      <div class="max-w-8xl mx-auto px-4 sm:px-6 py-2 hidden md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="inline mr-2">You are here:</li>
            <li class="inline">Settings</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-20 relative">
      <div class="max-w-md mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <h2 class="text-lg text-center mb-6">Settings</h2>
        <div class="bg-white border border-gray-200 rounded-lg mb-6 p-5">
          <ul>
            <li><span class="mr-1">🥸</span> <Link :href="data.url.users.index" class="text-sky-500 hover:text-blue-900">Manage users</Link></li>
            <li><span class="mr-1">🎃</span> Personalize your contacts data</li>
            <li><span class="mr-1">💩</span> Cancel your account</li>
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

  data() {
    return {
      addMode: false,
    };
  },

  methods: {
  },
};
</script>
