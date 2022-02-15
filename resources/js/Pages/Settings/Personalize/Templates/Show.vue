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
    <nav class="bg-white sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600">You are here:</li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings" class="text-sky-500 hover:text-blue-900"> Settings </inertia-link>
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
            <li class="mr-2 inline">
              <inertia-link :href="data.url.personalize" class="text-sky-500 hover:text-blue-900">
                Personalize your account
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
            <li class="mr-2 inline">
              <inertia-link :href="data.url.templates" class="text-sky-500 hover:text-blue-900">
                Templates
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
            <li class="inline">
              {{ data.template.name }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-4xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 sm:mt-0">
          <h3 class="mb-4 text-center text-xl sm:mb-2">
            {{ data.template.name }}
          </h3>
        </div>

        <!-- help text -->
        <div class="mb-10 flex rounded border bg-slate-50 px-3 py-2 text-sm">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 pr-2"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>

          <div>
            <p class="mb-1">This template will define what information are displayed on a contact page.</p>
            <p class="mb-1">
              A template is made of pages, and in each page, there are modules. How data is displayed is entirely up to
              you.
            </p>
            <p>
              Note that removing a module from a page will not delete the actual data on your contact pages. It will
              simply hide it.
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="p-3 sm:p-0">
            <pages :data="data" @page-selected="loadModules" />
          </div>

          <!-- middle -->
          <div class="p-3 sm:p-0">
            <modules :data="localModules" />
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import Pages from '@/Pages/Settings/Personalize/Templates/Partials/Pages';
import Modules from '@/Pages/Settings/Personalize/Templates/Partials/Modules';

export default {
  components: {
    Layout,
    Pages,
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
      localModules: [],
    };
  },

  methods: {
    showAddModal(type) {
      if (type == 'lifeEvent') {
        this.addMode = true;
      }
    },

    loadModules(page) {
      axios
        .get(page.url.show)
        .then((response) => {
          this.localModules = response.data.data;
        })
        .catch((error) => {
          this.loadingState = null;
          this.errors = error.response.data;
        });
    },
  },
};
</script>
