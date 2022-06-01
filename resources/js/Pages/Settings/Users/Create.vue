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
            <li class="mr-2 inline text-gray-600">You are here:</li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings" class="text-blue-500 hover:underline">Settings</inertia-link>
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
              <inertia-link :href="data.url.back" class="text-blue-500 hover:underline"> Users </inertia-link>
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
            <li class="inline">Invite a new user</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-lg px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <form class="mb-6 rounded-lg border border-gray-200 bg-white" @submit.prevent="submit()">
          <!-- title -->
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5">
            <h1 class="mb-1 text-center text-2xl font-medium">Invite someone</h1>
            <p class="text-center">
              This user will be part of your account, but won't get access to all the vaults in this account unless you
              give specific access to them. This person will be able to create vaults as well.
            </p>
          </div>

          <!-- form -->
          <div class="border-b border-gray-200 p-5">
            <errors :errors="form.errors" />

            <text-input
              v-model="form.email"
              :label="'Email address to send the invitation to'"
              :type="'email'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255" />
          </div>

          <!-- permissions -->
          <div class="border-b border-gray-200 p-5">
            <!-- role types -->
            <div>
              <p class="mb-2">What permission should the user have?</p>

              <!-- view -->
              <div class="mb-2 flex items-start">
                <input
                  id="viewer"
                  v-model="form.administrator"
                  value="false"
                  name="permission"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500" />
                <label for="viewer" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                  Regular user
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
                  class="h-4 w-4 border-gray-300 text-sky-500" />
                <label for="manager" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                  Administrator
                  <span class="ml-4 font-normal text-gray-500">
                    Can do everything, including adding or removing other users, managing billing and closing the
                    account.
                  </span>
                </label>
              </div>
            </div>
          </div>

          <div class="flex justify-between p-5">
            <pretty-link :href="data.url.back" :text="'Cancel'" :classes="'mr-3'" />
            <pretty-button :text="'Send invitation'" :state="loadingState" :icon="'check'" :classes="'save'" />
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
          localStorage.success = 'Invitation sent';
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
