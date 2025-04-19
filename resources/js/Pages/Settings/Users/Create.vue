k
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
            <li class="me-2 inline">
              <InertiaLink :href="data.url.back" class="text-blue-500 hover:underline">
                {{ $t('Users') }}
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
            <li class="inline">
              {{ $t('Create a user') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-lg px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <form
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <!-- title -->
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5 dark:border-gray-700 dark:bg-blue-900">
            <h1 class="mb-1 text-center text-2xl font-medium">
              {{ $t('Invite someone') }}
            </h1>
            <p class="text-center">
              {{
                $t(
                  'This user will be part of your account, but won’t get access to all the vaults in this account unless you give specific access to them. This person will be able to create vaults as well.',
                )
              }}
            </p>
          </div>

          <!-- form -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              v-model="form.email"
              :label="$t('Email address to send the invitation to')"
              :type="'email'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255" />
          </div>

          <!-- permissions -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <!-- role types -->
            <div>
              <p class="mb-2">
                {{ $t('What permission should the user have?') }}
              </p>

              <!-- view -->
              <div class="mb-2 flex items-start">
                <input
                  id="viewer"
                  v-model="form.administrator"
                  value="false"
                  name="permission"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                <label
                  for="viewer"
                  class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t('Regular user') }}
                </label>
              </div>

              <!-- administrator -->
              <div class="flex items-start">
                <input
                  id="manager"
                  v-model="form.administrator"
                  value="true"
                  name="permission"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                <label
                  for="manager"
                  class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t('Administrator') }}
                  <span class="ms-4 font-normal text-gray-500">
                    {{
                      $t(
                        'Can do everything, including adding or removing other users, managing billing and closing the account.',
                      )
                    }}
                  </span>
                </label>
              </div>
            </div>
          </div>

          <div class="flex justify-between p-5">
            <pretty-link :href="data.url.back" :text="$t('Cancel')" :class="'me-3'" />
            <pretty-button :text="$t('Send invitation')" :state="loadingState" :icon="'check'" :class="'save'" />
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

export default {
  components: {
    InertiaLink: Link,
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
        email: '',
        administrator: Boolean,
        errors: [],
      },
    };
  },

  mounted() {
    this.form.administrator = false;
  },

  methods: {
    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          localStorage.success = this.$t('Invitation sent');
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
