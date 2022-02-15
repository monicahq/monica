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
    <nav class="bg-white sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600">You are here:</li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings" class="text-sky-500 hover:text-blue-900"> Settings </inertia-link>
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
              <inertia-link :href="data.url.personalize" class="text-sky-500 hover:text-blue-900">
                Personalize your account
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
            <li class="inline">Labels</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0"><span class="mr-1"> 🏷 </span> All the labels used in the account</h3>
          <pretty-button v-if="!createlabelModalShown" :text="'Add a label'" :icon="'plus'" @click="showLabelModal" />
        </div>

        <!-- modal to create a new group type -->
        <form
          v-if="createlabelModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200 p-5">
            <errors :errors="form.errors" />

            <text-input
              :ref="'newLabel'"
              v-model="form.name"
              :label="'Name'"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createlabelModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="createlabelModalShown = false" />
            <pretty-button :text="'Create label'" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>

        <!-- list of groups types -->
        <ul v-if="localLabels.length > 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
          <li v-for="label in localLabels" :key="label.id" class="item-list border-b border-gray-200 hover:bg-slate-50">
            <!-- detail of the group type -->
            <div v-if="renamelabelModalShownId != label.id" class="flex items-center justify-between px-5 py-2">
              <span class="text-base"
                >{{ label.name }} <span class="text-xs text-gray-500">({{ label.count }} contacts)</span></span
              >

              <!-- actions -->
              <ul class="text-sm">
                <li
                  class="mr-4 inline cursor-pointer text-sky-500 hover:text-blue-900"
                  @click="updateLabelModal(label)">
                  Rename
                </li>
                <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(label)">Delete</li>
              </ul>
            </div>

            <!-- rename a label modal -->
            <form
              v-if="renamelabelModalShownId == label.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50"
              @submit.prevent="update(label)">
              <div class="border-b border-gray-200 p-5">
                <errors :errors="form.errors" />

                <text-input
                  :ref="'rename' + label.id"
                  v-model="form.name"
                  :label="'Name'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="renamelabelModalShownId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="'Cancel'" :classes="'mr-3'" @click.prevent="renamelabelModalShownId = 0" />
                <pretty-button :text="'Rename'" :state="loadingState" :icon="'check'" :classes="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div v-if="localLabels.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
          <p class="p-5 text-center">Labels let you classify contacts using a system that matters to you.</p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Errors from '@/Shared/Form/Errors';

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

      axios
        .post(this.data.url.label_store, this.form)
        .then((response) => {
          this.flash('The label has been created', 'success');
          this.localLabels.unshift(response.data.data);
          this.loadingState = null;
          this.createlabelModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(label) {
      this.loadingState = 'loading';

      axios
        .put(label.url.update, this.form)
        .then((response) => {
          this.flash('The label has been updated', 'success');
          this.localLabels[this.localLabels.findIndex((x) => x.id === label.id)] = response.data.data;
          this.loadingState = null;
          this.renamelabelModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(label) {
      if (
        confirm(
          "Are you sure? This will remove the labels from all contacts, but won't delete the contacts themselves.",
        )
      ) {
        axios
          .delete(label.url.destroy)
          .then((response) => {
            this.flash('The label has been deleted', 'success');
            var id = this.localLabels.findIndex((x) => x.id === label.id);
            this.localLabels.splice(id, 1);
          })
          .catch((error) => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },
  },
};
</script>
