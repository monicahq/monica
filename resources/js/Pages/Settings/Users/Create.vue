<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>

<template>
  <Layout :layoutData="layoutData">
    <!-- breadcrumb -->
    <nav class="sm:border-b bg-white">
      <div class="max-w-8xl mx-auto px-4 sm:px-6 py-2 hidden md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="inline mr-2 text-gray-600">You are here:</li>
            <li class="inline mr-2">
              <Link :href="data.url.settings" class="text-sky-500 hover:text-blue-900">Settings</Link>
            </li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline mr-2">
              <Link :href="data.url.back" class="text-sky-500 hover:text-blue-900">Users</Link>
            </li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Invite a new user</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-24 relative">
      <div class="max-w-lg mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">

        <form @submit.prevent="submit()" class="bg-white border border-gray-200 rounded-lg mb-6">

          <!-- title -->
          <div class="p-5 border-b border-gray-200 bg-blue-50 section-head">
            <h1 class="text-center text-2xl mb-1 font-medium">Invite someone</h1>
            <p class="text-center">This user will be part of your account, but won't get access to your vaults unless you give specific access to them. This person will be able to create vaults as well.</p>
          </div>

          <!-- form -->
          <div class="p-5 border-b border-gray-200">
            <errors :errors="form.errors" />

            <text-input v-model="form.email"
              :label="'Email address to send the invitation to'"
              :type="'email'" :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255" />
         </div>

          <div class="p-5 flex justify-between">
            <pretty-link :href="data.url.back" :text="'Cancel'" :classes="'mr-3'" />
            <pretty-button :text="'Send invitation'" :state="loadingState" :icon="'check'" :classes="'save'" />
          </div>
        </form>
      </div>
    </main>
  </Layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyLink from '@/Shared/PrettyLink';
import PrettyButton from '@/Shared/PrettyButton';
import TextInput from '@/Shared/TextInput';
import Errors from '@/Shared/Errors';
import TextArea from '@/Shared/TextArea';
import { Link } from '@inertiajs/inertia-vue3';

export default {
  components: {
    Layout,
    PrettyLink,
    PrettyButton,
    TextInput,
    Errors,
    TextArea,
    Link,
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
        errors: [],
      },
    };
  },

  methods: {
    submit() {
      this.loadingState = 'loading';

      axios.post(this.data.url.store, this.form)
        .then(response => {
          localStorage.success = 'Invitation sent';
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
