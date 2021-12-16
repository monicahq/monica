<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>

<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="sm:border-b bg-white">
      <div class="max-w-8xl mx-auto px-4 sm:px-6 py-2 hidden md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="inline mr-2 text-gray-600">You are here:</li>
            <li class="inline mr-2">
              <inertia-link :href="data.url.settings" class="text-sky-500 hover:text-blue-900">Settings</inertia-link>
            </li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Personalize your account</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-20 relative">
      <div class="max-w-md mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <h2 class="text-lg text-center mb-6">
          Personalize the account
        </h2>
        <div class="bg-white border border-gray-200 rounded-lg p-5">
          <ul>
            <li class="mb-2"><span class="mr-1">🥸</span> <inertia-link :href="data.url.manage_relationships" class="text-sky-500 hover:text-blue-900">Manage relationship types</inertia-link></li>
            <li class="mb-2"><span class="mr-1">🏷</span> <inertia-link :href="data.url.manage_labels" class="text-sky-500 hover:text-blue-900">Manage labels</inertia-link></li>
            <li class=""><span class="mr-1">🚻</span> <inertia-link :href="data.url.manage_genders" class="text-sky-500 hover:text-blue-900">Manage genders</inertia-link></li>
          </ul>
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
};
</script>
