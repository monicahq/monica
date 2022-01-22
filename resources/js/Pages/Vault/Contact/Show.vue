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
              <inertia-link :href="''" class="text-sky-500 hover:text-blue-900">Contacts</inertia-link>
            </li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Profile of Regis Freyd</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-20 relative">
      <div class="max-w-6xl mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <!-- left -->
          <div class="p-3 sm:p-3">
            <!-- avatar -->
            <div class="text-center">
              <img class="h-20 w-20 mx-auto rounded-full" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
            </div>

            <!-- names -->
            <div class="border border-gray-200 rounded-lg">
              <h1 class="text-lg">
                Regis Freyd
              </h1>
            </div>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <!-- all the pages -->
            <div class="border-b border-gray-200 mb-8">
              <ul>
                <li v-for="page in data.template_pages" :key="page.id" class="inline mr-2">
                  <inertia-link :href="page.url.show" :class="{ 'border-orange-500 hover:border-orange-500' : page.selected }" class="pb-2 px-4 inline-block border-b-2 border-transparent hover:border-gray-200">{{ page.name }}</inertia-link>
                </li>
              </ul>
            </div>

            <!-- all the modules -->
            <div v-if="data.modules.length > 0">
              <div v-for="module in data.modules" :key="module.id">
                <notes v-if="module.type == 'notes'" :data="notes" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import Notes from '@/Shared/Modules/Notes';

export default {
  components: {
    Layout,
    Notes,
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
      notes: [],
    };
  },

  created() {
    if (this.data.modules.length > 0) {
      this.notes = this.data.modules[this.data.modules.findIndex(x => x.type == 'notes')].data;
    }
  },

  methods: {
  },
};
</script>
