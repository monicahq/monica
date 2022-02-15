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
  <layout title="Dashboard" :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600">You are here:</li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings.index" class="text-sky-500 hover:text-blue-900">
                Settings
              </inertia-link>
            </li>
            <li class="relative mr-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Users</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 flex items-center justify-between">
          <h3><span class="mr-1"> 🥸 </span> All users in this account</h3>
          <pretty-link :href="data.url.users.create" :text="'Invite a new user'" :icon="'plus'" />
        </div>

        <!-- list of users -->
        <ul class="user-list mb-6 rounded-lg border border-gray-200 bg-white">
          <li v-for="user in data.users" :key="user.id" class="border-b border-gray-200 px-5 py-2 hover:bg-slate-50">
            <!-- case user has been invited -->
            <div v-if="!user.name" class="flex items-center justify-between">
              <div>
                <span class="block">{{ user.email }}</span>
                <span class="relative text-sm text-gray-400">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="icon-mail relative inline h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>

                  Invitation sent
                </span>
              </div>
            </div>

            <!-- case normal user -->
            <div v-if="user.name" class="flex items-center justify-between">
              <div>
                <span class="block">{{ user.name }}</span>
                <span class="text-sm text-gray-400">{{ user.email }}</span>
              </div>

              <!-- actions -->
              <ul class="text-sm">
                <li class="mr-4 inline text-sky-500 hover:text-blue-900">Change permission</li>
                <li class="inline text-red-500 hover:text-red-900">Delete</li>
              </ul>
            </div>
          </li>
        </ul>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyLink from '@/Shared/Form/PrettyLink';

export default {
  components: {
    Layout,
    PrettyLink,
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

  data() {
    return {};
  },

  methods: {},
};
</script>
