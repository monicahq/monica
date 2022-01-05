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
            <li class="inline mr-2"><inertia-link :href="data.url.personalize" class="text-sky-500 hover:text-blue-900">Personalize your account</inertia-link></li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Address types</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-20 relative">
      <div class="max-w-3xl mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="sm:flex items-center justify-between mb-6 sm:mt-0 mt-8">
          <h3 class="mb-4 sm:mb-0">
            <span class="mr-1">
              🏖
            </span> All the address types
          </h3>
          <pretty-button v-if="!createAddressTypeModalShown" :text="'Add an address type'" :icon="'plus'" @click="showAddressTypeModal" />
        </div>

        <!-- modal to create a new group type -->
        <form v-if="createAddressTypeModalShown" class="bg-white border border-gray-200 rounded-lg mb-6" @submit.prevent="submit()">
          <div class="p-5 border-b border-gray-200">
            <errors :errors="form.errors" />

            <text-input :ref="'newAddressType'"
                        v-model="form.name"
                        :label="'Name'" :type="'text'"
                        :autofocus="true"
                        :input-class="'block w-full'"
                        :required="true"
                        :autocomplete="false"
                        :maxlength="255"
                        @esc-key-pressed="createAddressTypeModalShown = false"
            />
          </div>

          <div class="p-5 flex justify-between">
            <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="createAddressTypeModalShown = false" />
            <pretty-button :text="'Create address type'" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>

        <!-- list of groups types -->
        <ul v-if="localAddressTypes.length > 0" class="bg-white border border-gray-200 rounded-lg mb-6">
          <li v-for="addressType in localAddressTypes" :key="addressType.id" class="border-b border-gray-200 hover:bg-slate-50 item-list">
            <!-- detail of the group type -->
            <div v-if="renameAddressTypeModalShownId != addressType.id" class="flex justify-between items-center px-5 py-2">
              <span class="text-base">{{ addressType.name }}</span>

              <!-- actions -->
              <ul class="text-sm">
                <li class="cursor-pointer inline mr-4 text-sky-500 hover:text-blue-900" @click="updateAdressTypeModal(addressType)">Rename</li>
                <li class="cursor-pointer inline text-red-500 hover:text-red-900" @click="destroy(addressType)">Delete</li>
              </ul>
            </div>

            <!-- rename a addressType modal -->
            <form v-if="renameAddressTypeModalShownId == addressType.id" class="border-b border-gray-200 hover:bg-slate-50 item-list" @submit.prevent="update(addressType)">
              <div class="p-5 border-b border-gray-200">
                <errors :errors="form.errors" />

                <text-input :ref="'rename' + addressType.id"
                            v-model="form.name"
                            :label="'Name'" :type="'text'"
                            :autofocus="true"
                            :input-class="'block w-full'"
                            :required="true"
                            :autocomplete="false"
                            :maxlength="255"
                            @esc-key-pressed="renameAddressTypeModalShownId = 0"
                />
              </div>

              <div class="p-5 flex justify-between">
                <pretty-span :text="'Cancel'" :classes="'mr-3'" @click.prevent="renameAddressTypeModalShownId = 0" />
                <pretty-button :text="'Rename'" :state="loadingState" :icon="'check'" :classes="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div v-if="localAddressTypes.length == 0" class="bg-white border border-gray-200 rounded-lg mb-6">
          <p class="p-5 text-center">Address types let you classify contact addresses.</p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyButton from '@/Shared/PrettyButton';
import PrettySpan from '@/Shared/PrettySpan';
import TextInput from '@/Shared/TextInput';
import Errors from '@/Shared/Errors';

export default {
  components: {
    Layout,
    PrettyButton,
    PrettySpan,
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
      createAddressTypeModalShown: false,
      renameAddressTypeModalShownId: 0,
      localAddressTypes: [],
      form: {
        name: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localAddressTypes = this.data.address_types;
  },

  methods: {
    showAddressTypeModal() {
      this.form.name = '';
      this.createAddressTypeModalShown = true;

      this.$nextTick(() => {
        this.$refs.newAddressType.focus();
      });
    },

    updateAdressTypeModal(addressType) {
      this.form.name = addressType.name;
      this.renameAddressTypeModalShownId = addressType.id;

      this.$nextTick(() => {
        this.$refs[`rename${addressType.id}`].focus();
      });
    },

    submit() {
      this.loadingState = 'loading';

      axios.post(this.data.url.address_type_store, this.form)
        .then(response => {
          this.flash('The address type has been created', 'success');
          this.localAddressTypes.unshift(response.data.data);
          this.loadingState = null;
          this.createAddressTypeModalShown = false;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(addressType) {
      this.loadingState = 'loading';

      axios.put(addressType.url.update, this.form)
        .then(response => {
          this.flash('The address type has been updated', 'success');
          this.localAddressTypes[this.localAddressTypes.findIndex(x => x.id === addressType.id)] = response.data.data;
          this.loadingState = null;
          this.renameAddressTypeModalShownId = 0;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(addressType) {
      if(confirm('Are you sure? This will remove the address types from all contacts, but won\'t delete the contacts themselves.')) {

        axios.delete(addressType.url.destroy)
          .then(response => {
            this.flash('The address type has been deleted', 'success');
            var id = this.localAddressTypes.findIndex(x => x.id === addressType.id);
            this.localAddressTypes.splice(id, 1);
          })
          .catch(error => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },
  },
};
</script>
