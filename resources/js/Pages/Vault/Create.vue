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
              <inertia-link :href="data.url.back" class="text-sky-500 hover:text-blue-900">
                All the vaults
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
            <li class="inline">Add a new vault</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-lg px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <form class="mb-6 rounded-lg border border-gray-200 bg-white" @submit.prevent="submit()">
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5">
            <h1 class="mb-1 text-center text-2xl font-medium">Create a vault</h1>
            <p class="text-center">Vaults contain all your contacts data.</p>
          </div>
          <div class="border-b border-gray-200 p-5">
            <text-input
              v-model="form.name"
              :autofocus="true"
              :div-outer-class="'mb-5'"
              :input-class="'block w-full'"
              :required="true"
              :maxlength="255"
              :label="'Vault name'" />
            <text-area
              v-model="form.description"
              :label="'Description'"
              :maxlength="255"
              :textarea-class="'block w-full'" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-link :href="data.url.back" :text="'Cancel'" :classes="'mr-3'" />
            <pretty-button
              :href="'data.url.vault.create'"
              :text="'Add'"
              :state="loadingState"
              :icon="'check'"
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
import TextArea from '@/Shared/Form/TextArea';

export default {
  components: {
    Layout,
    PrettyLink,
    PrettyButton,
    TextInput,
    TextArea,
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
        name: '',
        description: '',
      },
    };
  },

  methods: {
    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          localStorage.success = 'The vault has been created';
          this.$inertia.visit(response.data.data);
        })
        .catch((error) => {
          this.loadingState = null;
        });
    },
  },
};
</script>
