<style lang="scss" scoped>
.grid {
  grid-template-columns: 300px 1fr;
}

@media (max-width: 480px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="sm:mt-20 sm:border-b bg-white">
      <div class="max-w-8xl mx-auto px-4 sm:px-6 py-2 hidden md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="inline mr-2 text-gray-600">You are here:</li>
            <li class="inline mr-2">
              <inertia-link :href="data.url.back" class="text-sky-500 hover:text-blue-900">Contacts</inertia-link>
            </li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Create a contact</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-24 relative">
      <div class="max-w-6xl mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <!-- left -->
          <div class="p-3 sm:p-3 border border-gray-200 rounded-lg">
            <!-- avatar -->
            <div class="text-center">
              <img class="h-20 w-20 mx-auto rounded-full" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
            </div>

            <!-- names -->
            <div>
              <h1 class="text-lg">
                Regis Freyd
              </h1>
            </div>
          </div>

          <!-- right -->
          <div class="p-3 sm:p-3 border border-gray-200">
            <!-- all the pages -->
            <div>
              <ul>
                <li v-for="page in data.template_pages" :key="page.id" class="inline mr-2">
                  <inertia-link :href="page.url.show">{{ page.name }}</inertia-link>
                </li>
              </ul>
            </div>


            <div class="mb-10">
              <h3 class="font-medium mb-3 border-b border-gray-200">
                <span class="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon-sidebar h-4 w-4 inline relative" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </span>
                Reminders
              </h3>
              <p class="text-xs text-gray-400">Nov 09</p>
              <p class="mb-3 text-sm">Birthday John</p>
              <p class="text-xs text-gray-400">Nov 13</p>
              <p class="mb-3 text-sm">Wedding Henri</p>
            </div>
            <h3 class="font-medium mb-3 border-b border-gray-200">
              <span class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon-sidebar h-4 w-4 inline relative" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
              </span>
              Tasks
            </h3>
            <div class="flex items-start mb-3 relative">
              <input id="remember-me" name="remember-me" type="checkbox" class="relative h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
              <label for="remember-me" class="cursor-pointer ml-2 block text-sm text-gray-900">
                Remember mea sdfasdf asdf asdf asdf sdf
              </label>
            </div>
            <div class="flex items-start">
              <input id="remember-me" name="remember-me" type="checkbox" class="relative h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
              <label for="remember-me" class="cursor-pointer ml-2 block text-sm text-gray-900">
                Remember me
              </label>
            </div>
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
      type: Object,
      default: null,
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
