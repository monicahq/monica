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
              <InertiaLink :href="data.url.personalize" class="text-blue-500 hover:underline">
                {{ $t('Personalize your account') }}
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
            <li class="inline">{{ $t('Pronouns') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0">
            <span class="me-1"> 🚻 </span>
            {{ $t('All the pronouns') }}
          </h3>
          <pretty-button
            v-if="!createPronounModalShown"
            :text="$t('Add a pronoun')"
            :icon="'plus'"
            @click="showPronounModal" />
        </div>

        <!-- modal to create a pronoun -->
        <form
          v-if="createPronounModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              ref="newPronoun"
              v-model="form.name"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createPronounModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createPronounModalShown = false" />
            <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
          </div>
        </form>

        <!-- list of pronouns -->
        <ul
          v-if="localPronouns.length > 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <li
            v-for="pronoun in localPronouns"
            :key="pronoun.id"
            class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
            <!-- detail of the pronoun -->
            <div v-if="renamePronounModalShownId !== pronoun.id" class="flex items-center justify-between px-5 py-2">
              <span class="text-base">{{ pronoun.name }}</span>

              <!-- actions -->
              <ul class="text-sm">
                <li class="me-4 inline cursor-pointer" @click="updatePronounModal(pronoun)">
                  <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                </li>
                <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(pronoun)">
                  {{ $t('Delete') }}
                </li>
              </ul>
            </div>

            <!-- rename a pronoun modal -->
            <form
              v-if="renamePronounModalShownId === pronoun.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
              @submit.prevent="update(pronoun)">
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <errors :errors="form.errors" />

                <text-input
                  ref="rename"
                  v-model="form.name"
                  :label="$t('Name')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="renamePronounModalShownId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="renamePronounModalShownId = 0" />
                <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div
          v-if="localPronouns.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{
              $t(
                'Pronouns are basically how we identify ourselves apart from our name. It’s how someone refers to you in conversation.',
              )
            }}
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';

export default {
  components: {
    InertiaLink: Link,
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
      this.renamePronounModalShownId = 0;

      this.$nextTick().then(() => {
        this.$refs.newPronoun.focus();
      });
    },

    updatePronounModal(pronoun) {
      this.form.name = pronoun.name;
      this.renamePronounModalShownId = pronoun.id;
      this.createPronounModalShown = false;

      this.$nextTick().then(() => {
        this.$refs.rename[0].focus();
      });
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.pronoun_store, this.form)
        .then((response) => {
          this.flash(this.$t('The pronoun has been created'), 'success');
          this.localPronouns.unshift(response.data.data);
          this.loadingState = null;
          this.createPronounModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(pronoun) {
      this.loadingState = 'loading';

      axios
        .put(pronoun.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The pronoun has been updated'), 'success');
          this.localPronouns[this.localPronouns.findIndex((x) => x.id === pronoun.id)] = response.data.data;
          this.loadingState = null;
          this.renamePronounModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(pronoun) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(pronoun.url.destroy)
          .then(() => {
            this.flash(this.$t('The pronoun has been deleted'), 'success');
            var id = this.localPronouns.findIndex((x) => x.id === pronoun.id);
            this.localPronouns.splice(id, 1);
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
