<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <InertiaLink :href="data.url.settings" class="text-blue-500 hover:underline">
                {{ $t('Settings') }}
              </InertiaLink>
            </li>
            <li class="relative me-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">{{ $t('Cancel account') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-lg px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <form
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="destroy()">
          <!-- title -->
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5 dark:border-gray-700 dark:bg-blue-900">
            <h1 class="mb-4 flex justify-center text-2xl font-medium">
              <span>{{ $t('Cancel your account') }}</span>

              <help :url="$page.props.help_links.settings_account_deletion" :top="'9px'" :class="'ms-3'" />
            </h1>
            <p class="mb-2">{{ $t('Thanks for giving Monica a try.') }}</p>
            <p class="mb-2">{{ $t('Once you cancel,') }}</p>
            <ul class="list-disc ps-6">
              <li>{{ $t('Your account will be closed immediately,') }}</li>
              <li>{{ $t('All users and vaults will be deleted immediately,') }}</li>
              <li>
                {{
                  $t(
                    'The account’s data will be permanently deleted from our servers within 30 days and from all backups within 60 days.',
                  )
                }}
              </li>
            </ul>
          </div>

          <!-- form -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              v-model="form.password"
              :label="$t('Please enter your password to cancel the account')"
              :type="'password'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-link :href="data.url.back" :text="$t('Go back')" :class="'me-3'" />
            <pretty-button
              :href="'data.url.vault.create'"
              :text="$t('Cancel account')"
              :state="loadingState"
              :icon="'arrow'"
              :class="'save'" />
          </div>
        </form>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Help from '@/Shared/Help.vue';

export default {
  components: {
    InertiaLink: Link,
    Layout,
    PrettyLink,
    PrettyButton,
    TextInput,
    Errors,
    Help,
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

<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>
