<style lang="scss" scoped>
.grid {
  grid-template-columns: 1fr 2fr;
}

@media (max-width: 480px) {
  .grid {
    grid-template-columns: 1fr;
  }
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
            <li class="inline mr-2"><inertia-link :href="data.url.personalize" class="text-sky-500 hover:text-blue-900">Personalize your account</inertia-link></li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline mr-2"><inertia-link :href="data.url.templates" class="text-sky-500 hover:text-blue-900">Templates</inertia-link></li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">{{ data.template.name }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-16 relative">
      <div class="max-w-4xl mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 sm:mt-0 mt-8">
          <h3 class="text-center mb-4 sm:mb-2 text-xl">
            {{ data.template.name }}
          </h3>
        </div>

        <!-- help text -->
        <div class="px-3 py-2 border mb-6 flex rounded text-sm bg-slate-50">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 pr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>

          <div>
            <p class="mb-2">This template will define what information are displayed on a contact page.</p>
            <p class="mb-2">A template is made of pages, and in each page, there are modules. How data is displayed is entirely up to you.</p>
            <p>Note that removing a module from a page will not delete the actual data on your contact pages. It will simply hide it.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <!-- left -->
          <div class="p-3 sm:p-0">
            <modules :data="data" />
          </div>

          <!-- middle -->
          <div class="p-3 sm:p-0">
            <h3 class="border-b mb-4">
              Modules in this page
            </h3>

            <p>Please select a page on the lefts</p>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import Modules from '@/Pages/Settings/Personalize/Templates/Partials/Modules';

export default {
  components: {
    Layout,
    Modules,
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
    return {
      addMode: false,
    };
  },

  methods: {
    showAddModal(type) {
      if (type == 'lifeEvent') {
        this.addMode = true;
      }
    },
  },
};
</script>
