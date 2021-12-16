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
            <li class="inline">Pronouns</li>
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
              🚻
            </span> All the pronouns
          </h3>
          <pretty-button v-if="!createPronounModalShown" :text="'Add a label'" :icon="'plus'" @click="showPronounModal" />
        </div>

        <!-- modal to create a new group type -->
        <form v-if="createPronounModalShown" class="bg-white border border-gray-200 rounded-lg mb-6" @submit.prevent="submit()">
          <div class="p-5 border-b border-gray-200">
            <errors :errors="form.errors" />

            <text-input :ref="'newPronoun'"
                        v-model="form.name"
                        :label="'Name'" :type="'text'"
                        :autofocus="true"
                        :input-class="'block w-full'"
                        :required="true"
                        :autocomplete="false"
                        :maxlength="255"
                        @esc-key-pressed="createPronounModalShown = false"
            />
          </div>

          <div class="p-5 flex justify-between">
            <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="createPronounModalShown = false" />
            <pretty-button :text="'Create label'" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>

        <!-- list of groups types -->
        <ul v-if="localPronouns.length > 0" class="bg-white border border-gray-200 rounded-lg mb-6">
          <li v-for="pronoun in localPronouns" :key="pronoun.id" class="border-b border-gray-200 hover:bg-slate-50 item-list">
            <!-- detail of the group type -->
            <div v-if="renamePronounModalShownId != pronoun.id" class="flex justify-between items-center px-5 py-2">
              <span class="text-base">{{ pronoun.name }}</span>

              <!-- actions -->
              <ul class="text-sm">
                <li class="cursor-pointer inline mr-4 text-sky-500 hover:text-blue-900" @click="updatePronounModal(pronoun)">Rename</li>
                <li class="cursor-pointer inline text-red-500 hover:text-red-900" @click="destroy(pronoun)">Delete</li>
              </ul>
            </div>

            <!-- rename a pronoun modal -->
            <form v-if="renamePronounModalShownId == pronoun.id" class="border-b border-gray-200 hover:bg-slate-50 item-list" @submit.prevent="update(pronoun)">
              <div class="p-5 border-b border-gray-200">
                <errors :errors="form.errors" />

                <text-input :ref="'rename' + pronoun.id"
                            v-model="form.name"
                            :label="'Name'" :type="'text'"
                            :autofocus="true"
                            :input-class="'block w-full'"
                            :required="true"
                            :autocomplete="false"
                            :maxlength="255"
                            @esc-key-pressed="renamePronounModalShownId = 0"
                />
              </div>

              <div class="p-5 flex justify-between">
                <pretty-span :text="'Cancel'" :classes="'mr-3'" @click.prevent="renamePronounModalShownId = 0" />
                <pretty-button :text="'Rename'" :state="loadingState" :icon="'check'" :classes="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div v-if="localPronouns.length == 0" class="bg-white border border-gray-200 rounded-lg mb-6">
          <p class="p-5 text-center">Labels let you classify contacts using a system that matters to you.</p>
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
      createPronounModalShown: false,
      renamePronounModalShownId: 0,
      localPronouns: [],
      form: {
        name: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localPronouns = this.data.pronouns;
  },

  methods: {
    showPronounModal() {
      this.form.name = '';
      this.createPronounModalShown = true;

      this.$nextTick(() => {
        this.$refs.newPronoun.focus();
      });
    },

    updatePronounModal(pronoun) {
      this.form.name = pronoun.name;
      this.renamePronounModalShownId = pronoun.id;

      this.$nextTick(() => {
        this.$refs[`rename${pronoun.id}`].focus();
      });
    },

    submit() {
      this.loadingState = 'loading';

      axios.post(this.data.url.pronoun_store, this.form)
        .then(response => {
          this.flash('The pronoun has been created', 'success');
          this.localPronouns.unshift(response.data.data);
          this.loadingState = null;
          this.createPronounModalShown = false;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(pronoun) {
      this.loadingState = 'loading';

      axios.put(pronoun.url.update, this.form)
        .then(response => {
          this.flash('The pronoun has been updated', 'success');
          this.localPronouns[this.localPronouns.findIndex(x => x.id === pronoun.id)] = response.data.data;
          this.loadingState = null;
          this.renamePronounModalShownId = 0;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(pronoun) {
      if(confirm('Are you sure? This will remove the pronouns from all contacts, but won\'t delete the contacts themselves.')) {

        axios.delete(pronoun.url.destroy)
          .then(response => {
            this.flash('The pronoun has been deleted', 'success');
            var id = this.localPronouns.findIndex(x => x.id === pronoun.id);
            this.localPronouns.splice(id, 1);
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
