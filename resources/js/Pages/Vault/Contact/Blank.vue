<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('app.breadcrumb_location') }}
            </li>
            <li class="mr-2 inline">
              <inertia-link :href="layoutData.vault.url.contacts" class="text-blue-500 hover:underline">
                Contacts
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
              {{ data.contact.name }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <h2 class="mb-6 text-center text-lg">Profile page of {{ data.contact.name }}</h2>
        <div class="mb-6 rounded border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <!-- help -->
          <div
            class="flex rounded-t border-b border-gray-200 bg-slate-50 px-3 py-2 dark:border-gray-700 dark:bg-slate-900 dark:bg-slate-900">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 grow pr-2"
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
              <p v-if="data.templates.length > 0" class="mb-2">
                Please choose one template below to tell Monica how this contact should be displayed. Templates let you
                define which data should be diplayed on the contact page.
              </p>
              <p v-else>
                It seems that there are no templates in the account yet. Please add at least template to your account
                first, then associate this template with this contact.
              </p>
            </div>
          </div>

          <ul v-if="data.templates.length > 0" class="rounded-b bg-white dark:bg-gray-900">
            <li
              v-for="template in data.templates"
              :key="template.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
              <div class="flex items-center justify-between px-5 py-2">
                <span>{{ template.name }}</span>

                <!-- actions -->
                <ul class="text-sm">
                  <li class="inline cursor-pointer text-blue-500 hover:underline" @click="submit(template)">Use</li>
                </ul>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout.vue';

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
      form: {
        templateId: '',
        errors: [],
      },
    };
  },

  methods: {
    submit(template) {
      this.form.templateId = template.id;

      axios
        .put(this.data.url.update, this.form)
        .then((response) => {
          localStorage.success = 'The template has been set';
          this.$inertia.visit(response.data.data);
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
