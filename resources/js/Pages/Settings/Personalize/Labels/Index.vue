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
            <li class="inline mr-2"><Link :href="data.url.personalize" class="text-sky-500 hover:text-blue-900">Personalize your account</Link></li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Labels</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-20 relative">
      <div class="max-w-3xl mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="sm:flex items-center justify-between mb-6 sm:mt-0 mt-8">
          <h3 class="mb-4 sm:mb-0"><span class="mr-1">🏷</span> All the labels used in the account</h3>
          <pretty-button @click="showLabelModal" v-if="!createlabelModalShown" :text="'Add a label'" :icon="'plus'" />
        </div>

        <!-- modal to create a new group type -->
        <form v-if="createlabelModalShown" @submit.prevent="submit()" class="bg-white border border-gray-200 rounded-lg mb-6">
          <div class="p-5 border-b border-gray-200">
            <errors :errors="form.errors" />

            <text-input v-model="form.name"
              :label="'Name'"
              :type="'text'" :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :ref="'newLabel'"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createlabelModalShown = false" />
          </div>

          <div class="p-5 flex justify-between">
            <pretty-span @click="createlabelModalShown = false" :text="'Cancel'" :classes="'mr-3'" />
            <pretty-button :text="'Create label'" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>

        <!-- list of groups types -->
        <ul v-if="localLabels.length > 0" class="bg-white border border-gray-200 rounded-lg mb-6">
          <li v-for="label in localLabels" :key="label.id" class="border-b border-gray-200 hover:bg-slate-50 item-list">
            <!-- detail of the group type -->
            <div v-if="renamelabelModalShownId != label.id" class="flex justify-between items-center px-5 py-2">
              <span class="text-base">{{ label.name }} <span class="text-xs text-gray-500">({{ label.count }} contacts)</span></span>

              <!-- actions -->
              <ul class="text-sm">
                <li @click="updateLabelModal(label)" class="cursor-pointer inline mr-4 text-sky-500 hover:text-blue-900">Rename</li>
                <li @click="destroy(label)" class="cursor-pointer inline text-red-500 hover:text-red-900">Delete</li>
              </ul>
            </div>

            <!-- rename a label modal -->
            <form v-if="renamelabelModalShownId == label.id" @submit.prevent="update(label)" class="border-b border-gray-200 hover:bg-slate-50 item-list">
              <div class="p-5 border-b border-gray-200">
                <errors :errors="form.errors" />

                <text-input v-model="form.name"
                  :label="'Name'"
                  :type="'text'" :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :ref="'rename' + label.id"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="renamelabelModalShownId = 0" />
              </div>

              <div class="p-5 flex justify-between">
                <pretty-span @click.prevent="renamelabelModalShownId = 0" :text="'Cancel'" :classes="'mr-3'" />
                <pretty-button :text="'Rename'" :state="loadingState" :icon="'check'" :classes="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div v-if="localLabels.length == 0" class="bg-white border border-gray-200 rounded-lg mb-6">
          <p class="p-5 text-center">Labels let you classify contacts using a system that matters to you.</p>
        </div>
      </div>
    </main>
  </Layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import { Link } from '@inertiajs/inertia-vue3';
import PrettyButton from '@/Shared/PrettyButton';
import PrettyLink from '@/Shared/PrettyLink';
import PrettySpan from '@/Shared/PrettySpan';
import TextInput from '@/Shared/TextInput';
import Errors from '@/Shared/Errors';

export default {
  components: {
    Layout,
    Link,
    PrettyButton,
    PrettyLink,
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
      createlabelModalShown: false,
      renamelabelModalShownId: 0,
      localLabels: [],
      form: {
        name: '',
        description: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localLabels = this.data.labels;
  },

  methods: {
    showLabelModal() {
      this.form.name = '';
      this.createlabelModalShown = true;

      this.$nextTick(() => {
        this.$refs.newLabel.focus();
      });
    },

    updateLabelModal(label) {
      this.form.name = label.name;
      this.renamelabelModalShownId = label.id;

      this.$nextTick(() => {
        this.$refs[`rename${label.id}`].focus();
      });
    },

    submit() {
      this.loadingState = 'loading';

      axios.post(this.data.url.label_store, this.form)
        .then(response => {
          this.flash('The label has been created', 'success');
          this.localLabels.unshift(response.data.data);
          this.loadingState = null;
          this.createlabelModalShown = false;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(label) {
      this.loadingState = 'loading';

      axios.put(label.url.update, this.form)
        .then(response => {
          this.flash('The label has been updated', 'success');
          this.localLabels[this.localLabels.findIndex(x => x.id === label.id)] = response.data.data;
          this.loadingState = null;
          this.renamelabelModalShownId = 0;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(label) {
      if(confirm("Are you sure? This will remove the labels from all contacts, but won't delete the contacts themselves.")) {

        axios.delete(label.url.destroy)
          .then(response => {
            this.flash('The label has been deleted', 'success');
            var id = this.localLabels.findIndex(x => x.id === label.id);
            this.localLabels.splice(id, 1);
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
