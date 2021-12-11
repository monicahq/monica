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

<template>
  <Layout title="Dashboard" :layoutData="layoutData">
    <!-- breadcrumb -->
    <nav class="sm:border-b bg-white">
      <div class="max-w-8xl mx-auto px-4 sm:px-6 py-2 hidden md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="inline mr-2 text-gray-600">You are here:</li>
            <li class="inline mr-2">
              <Link :href="data.url.settings.index" class="text-sky-500 hover:text-blue-900">Settings</Link>
            </li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Users</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-24 relative">
      <div class="max-w-3xl mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">

        <!-- title + cta -->
        <div class="flex items-center justify-between mb-6">
          <h3>All users in this account</h3>
          <pretty-link :href="data.url.users.create" :text="'Invite a new user'" :icon="'plus'" />
        </div>

        <!-- list of users -->
        <ul class="bg-white border border-gray-200 rounded-lg mb-6 user-list">
          <li v-for="user in data.users" :key="user.id" class="px-5 py-2 border-b border-gray-200 hover:bg-slate-50">

            <!-- case user has been invited -->
            <div v-if="!user.name" class="flex justify-between items-center">
              <div>
                <span class="block">{{ user.email }}</span>
                <span class="text-gray-400 text-sm relative">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 icon-mail inline relative" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>

                  Invitation sent
                </span>
              </div>
            </div>

            <!-- case normal user -->
            <div v-if="user.name" class="flex justify-between items-center">
              <div>
                <span class="block">{{ user.name }}</span>
                <span class="text-gray-400 text-sm">{{ user.email }}</span>
              </div>

              <!-- actions -->
              <ul class="text-sm">
                <li class="inline mr-4 text-sky-500 hover:text-blue-900">Change permission</li>
                <li class="inline text-red-500 hover:text-red-900">Delete</li>
              </ul>
            </div>
          </li>
        </ul>
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
    };
  },

  methods: {
  },
};
</script>
