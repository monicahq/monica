<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>

<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-slate-200">{{ $t('app.breadcrumb_location') }}</li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings" class="text-blue-500 hover:underline">{{
                $t('app.breadcrumb_settings')
              }}</inertia-link>
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
            <li class="inline">Cancel account</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-lg px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <form class="mb-6 rounded-lg border border-gray-200 bg-white" @submit.prevent="destroy()">
          <!-- title -->
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5">
            <h1 class="mb-4 text-center text-2xl font-medium">Cancel your account</h1>
            <p class="mb-2">Thanks for giving Monica a try.</p>
            <p class="mb-2">Once you cancel,</p>
            <ul class="list-disc pl-6">
              <li>Your account will be closed immediately,</li>
              <li>All users and vaults will be deleted immediately,</li>
              <li>
                The account's data will be permanently deleted from our servers within 30 days and from all backups
                within 60 days.
              </li>
            </ul>
          </div>

          <!-- form -->
          <div class="border-b border-gray-200 p-5">
            <errors :errors="form.errors" />

            <text-input
              v-model="form.password"
              :label="'Please enter your password to cancel the account'"
              :type="'password'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-link :href="data.url.back" :text="'Go back'" :classes="'mr-3'" />
            <pretty-button
              :href="'data.url.vault.create'"
              :text="'Cancel account'"
              :state="loadingState"
              :icon="'arrow'"
              :classes="'save'" />
          </div>
        </form>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyLink from '@/Shared/Form/PrettyLink';
import PrettyButton from '@/Shared/Form/PrettyButton';
import TextInput from '@/Shared/Form/TextInput';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    Layout,
    PrettyLink,
    PrettyButton,
    TextInput,
    Errors,
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
      loadingState: '',
      form: {
        password: '',
        errors: [],
      },
    };
  },

  methods: {
    destroy() {
      this.loadingState = 'loading';

      axios
        .put(this.data.url.destroy, this.form)
        .then((response) => {
          this.$inertia.visit(response.data.data);
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>
