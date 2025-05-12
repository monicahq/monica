<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <InertiaLink :href="data.url.back" class="text-blue-500 hover:underline">
                {{ $t('Contacts') }}
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
              {{ $t('Create a contact') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-lg px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <form
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5 dark:border-gray-700 dark:bg-blue-900">
            <h1 class="text-center text-2xl font-medium">
              {{ $t('Add a contact') }}
            </h1>
          </div>
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <!-- prefix -->
            <text-input
              v-if="showPrefixField"
              :id="'prefix'"
              v-model="form.prefix"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('Prefix')" />

            <!-- first name -->
            <text-input
              v-model="form.first_name"
              :autofocus="true"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="true"
              :maxlength="255"
              :label="$t('First name')" />

            <!-- last name -->
            <text-input
              :id="'last_name'"
              v-model="form.last_name"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('Last name')" />

            <!-- middle name -->
            <text-input
              v-if="showMiddleNameField"
              :id="'middle_name'"
              v-model="form.middle_name"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('Middle name')" />

            <!-- nickname -->
            <text-input
              v-if="showNicknameField"
              :id="'nickname'"
              v-model="form.nickname"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('Nickname')" />

            <!-- maiden name -->
            <text-input
              v-if="showMaidenNameField"
              :id="'maiden_name'"
              v-model="form.maiden_name"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('Maiden name')" />

            <!-- suffix -->
            <text-input
              v-if="showSuffixField"
              :id="'suffix'"
              v-model="form.suffix"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('Suffix')" />

            <!-- genders -->
            <dropdown
              v-if="showGenderField"
              v-model="form.gender_id"
              :data="data.genders"
              :required="false"
              :class="'mb-5'"
              :placeholder="$t('Choose a value')"
              :dropdown-class="'block w-full'"
              :label="$t('Gender')" />

            <!-- pronouns -->
            <dropdown
              v-if="showPronounField"
              v-model="form.pronoun_id"
              :data="data.pronouns"
              :required="false"
              :class="'mb-5'"
              :placeholder="$t('Choose a value')"
              :dropdown-class="'block w-full'"
              :label="$t('Pronoun')" />

            <!-- templates -->
            <dropdown
              v-if="showTemplateField"
              v-model="form.template_id"
              :data="data.templates"
              :required="false"
              :class="'mb-5'"
              :placeholder="$t('Choose a value')"
              :dropdown-class="'block w-full'"
              :label="$t('Use the following template for this contact')" />

            <!-- other fields -->
            <div class="flex flex-wrap text-xs">
              <span
                v-if="!showMiddleNameField"
                class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900 dark:text-white"
                @click="displayMiddleNameField">
                {{ $t('+ middle name') }}
              </span>
              <span
                v-if="!showPrefixField"
                class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900 dark:text-white"
                @click="displayPrefixField">
                {{ $t('+ prefix') }}
              </span>
              <span
                v-if="!showSuffixField"
                class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900 dark:text-white"
                @click="displaySuffixField">
                {{ $t('+ suffix') }}
              </span>
              <span
                v-if="!showNicknameField"
                class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900 dark:text-white"
                @click="displayNicknameField">
                {{ $t('+ nickname') }}
              </span>
              <span
                v-if="!showMaidenNameField"
                class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900 dark:text-white"
                @click="displayMaidenNameField">
                {{ $t('+ maiden name') }}
              </span>
              <span
                v-if="data.genders.length > 0 && !showGenderField"
                class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900 dark:text-white"
                @click="displayGenderField">
                {{ $t('+ gender') }}
              </span>
              <span
                v-if="data.pronouns.length > 0 && !showPronounField"
                class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900 dark:text-white"
                @click="displayPronounField">
                {{ $t('+ pronoun') }}
              </span>
              <span
                v-if="data.templates.length > 0 && !showTemplateField"
                class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900 dark:text-white"
                @click="displayTemplateField">
                {{ $t('+ change template') }}
              </span>
            </div>
          </div>

          <!-- actions -->
          <div class="flex justify-between p-5">
            <pretty-link :href="data.url.back" :text="$t('Cancel')" :class="'me-3'" />
            <pretty-button
              :href="'data.url.vault.create'"
              :text="$t('Add')"
              :state="loadingState"
              :icon="'check'"
              :class="'save'" />
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
import Dropdown from '@/Shared/Form/Dropdown.vue';
import Errors from '@/Shared/Form/Errors.vue';

export default {
  components: {
    InertiaLink: Link,
    Layout,
    PrettyLink,
    PrettyButton,
    TextInput,
    Dropdown,
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
      showMiddleNameField: false,
      showNicknameField: false,
      showMaidenNameField: false,
      showGenderField: false,
      showPronounField: false,
      showTemplateField: false,
      showSuffixField: false,
      showPrefixField: false,
      form: {
        first_name: '',
        last_name: '',
        middle_name: '',
        nickname: '',
        prefix: '',
        suffix: '',
        maiden_name: '',
        gender_id: '',
        pronoun_id: '',
        template_id: '',
        errors: [],
      },
    };
  },

  methods: {
    displayMiddleNameField() {
      this.showMiddleNameField = true;
    },

    displayNicknameField() {
      this.showNicknameField = true;
    },

    displayMaidenNameField() {
      this.showMaidenNameField = true;
    },

    displayGenderField() {
      this.showGenderField = true;
    },

    displayPronounField() {
      this.showPronounField = true;
    },

    displayTemplateField() {
      this.showTemplateField = true;
    },

    displaySuffixField() {
      this.showSuffixField = true;
    },

    displayPrefixField() {
      this.showPrefixField = true;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          localStorage.success = this.$t('The contact has been added');
          this.$inertia.visit(response.data.data);
        })
        .catch((error) => {
          this.form.errors = error.response.data;
          this.loadingState = null;
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
