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
            <li class="inline">Cancel account</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-24 relative">
      <div class="max-w-lg mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <form class="bg-white border border-gray-200 rounded-lg mb-6" @submit.prevent="destroy()">
          <!-- title -->
          <div class="p-5 border-b border-gray-200 bg-blue-50 section-head">
            <h1 class="text-center text-2xl mb-4 font-medium">
              Cancel your account
            </h1>
            <p class="mb-2">Thanks for giving Monica a try.</p>
            <p class="mb-2">Once you cancel,</p>
            <ul class="pl-6 list-disc">
              <li>Your account will be closed immediately,</li>
              <li>All users and vaults will be deleted immediately,</li>
              <li>The account's data will be permanently deleted from our servers within 30 days and from all backups within 60 days.</li>
            </ul>
          </div>

          <!-- form -->
          <div class="p-5 border-b border-gray-200">
            <errors :errors="form.errors" />

            <text-input v-model="form.password"
                        :label="'Please enter your password to cancel the account'"
                        :type="'password'" :autofocus="true"
                        :input-class="'block w-full'"
                        :required="true"
                        :autocomplete="false"
                        :maxlength="255"
            />
          </div>

          <div class="p-5 flex justify-between">
            <pretty-link :href="data.url.back" :text="'Go back'" :classes="'mr-3'" />
            <pretty-button :href="'data.url.vault.create'" :text="'Cancel account'" :state="loadingState" :icon="'arrow'" :classes="'save'" />
          </div>
        </form>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyLink from '@/Shared/PrettyLink';
import PrettyButton from '@/Shared/PrettyButton';
import TextInput from '@/Shared/TextInput';
import Errors from '@/Shared/Errors';

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

      axios.put(this.data.url.destroy, this.form)
        .then(response => {
          this.$inertia.visit(response.data.data);
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>
