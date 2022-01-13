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
              <inertia-link :href="data.url.back" class="text-sky-500 hover:text-blue-900">All the vaults</inertia-link>
            </li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Add a new vault</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-24 relative">
      <div class="max-w-lg mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <form class="bg-white border border-gray-200 rounded-lg mb-6" @submit.prevent="submit()">
          <div class="p-5 border-b border-gray-200 bg-blue-50 section-head">
            <h1 class="text-center text-2xl mb-1 font-medium">
              Create a vault
            </h1>
            <p class="text-center">Vaults contain all your contacts data.</p>
          </div>
          <div class="p-5 border-b border-gray-200">
            <text-input v-model="form.name" :autofocus="true" :div-outer-class="'mb-5'" :input-class="'block w-full'" :required="true"
                        :maxlength="255" :label="'Vault name'"
            />
            <text-area v-model="form.description" :label="'Description'" :maxlength="255" :textarea-class="'block w-full'" />
          </div>

          <div class="p-5 flex justify-between">
            <pretty-link :href="data.url.back" :text="'Cancel'" :classes="'mr-3'" />
            <pretty-button :href="'data.url.vault.create'" :text="'Add'" :state="loadingState" :icon="'check'" :classes="'save'" />
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
import TextArea from '@/Shared/TextArea';

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

      axios.post(this.data.url.store, this.form)
        .then(response => {
          localStorage.success = 'The vault has been created';
          this.$inertia.visit(response.data.data);
        })
        .catch(error => {
          this.loadingState = null;
        });
    },
  },
};
</script>
