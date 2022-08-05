<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-slate-200">
              {{ $t('app.breadcrumb_location') }}
            </li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.back" class="text-blue-500 hover:underline">
                {{ $t('app.breadcrumb_contact_index') }}
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
            <li class="inline">
              {{ $t('app.breadcrumb_contact_create') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-lg px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <form class="mb-6 rounded-lg border border-gray-200 bg-white" @submit.prevent="submit()">
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5">
            <h1 class="text-center text-2xl font-medium">
              {{ $t('vault.create_contact_title') }}
            </h1>
          </div>
          <div class="border-b border-gray-200 p-5">
            <errors :errors="form.errors" />

            <!-- first name -->
            <text-input
              v-model="form.first_name"
              :autofocus="true"
              :div-outer-class="'mb-5'"
              :input-class="'block w-full'"
              :required="true"
              :maxlength="255"
              :label="$t('vault.create_contact_first_name')" />

            <!-- last name -->
            <text-input
              :id="'last_name'"
              v-model="form.last_name"
              :div-outer-class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('vault.create_contact_last_name')" />

            <!-- middle name -->
            <text-input
              v-if="showMiddleNameField"
              :id="'middle_name'"
              v-model="form.middle_name"
              :div-outer-class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('vault.create_contact_middle_name')" />

            <!-- nickname -->
            <text-input
              v-if="showNicknameField"
              :id="'nickname'"
              v-model="form.nickname"
              :div-outer-class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('vault.create_contact_nickname')" />

            <!-- nickname -->
            <text-input
              v-if="showMaidenNameField"
              :id="'maiden_name'"
              v-model="form.maiden_name"
              :div-outer-class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('vault.create_contact_maiden_name')" />

            <!-- genders -->
            <dropdown
              v-if="showGenderField"
              v-model="form.gender_id"
              :data="data.genders"
              :required="false"
              :div-outer-class="'mb-5'"
              :placeholder="$t('app.choose_value')"
              :dropdown-class="'block w-full'"
              :label="$t('vault.create_contact_gender')" />

            <!-- pronouns -->
            <dropdown
              v-if="showPronounField"
              v-model="form.pronoun_id"
              :data="data.pronouns"
              :required="false"
              :div-outer-class="'mb-5'"
              :placeholder="$t('app.choose_value')"
              :dropdown-class="'block w-full'"
              :label="$t('vault.create_contact_pronoun')" />

            <!-- templates -->
            <dropdown
              v-if="showTemplateField"
              v-model="form.template_id"
              :data="data.templates"
              :required="false"
              :div-outer-class="'mb-5'"
              :placeholder="$t('app.choose_value')"
              :dropdown-class="'block w-full'"
              :label="$t('vault.create_contact_template')" />

            <!-- other fields -->
            <div class="flex flex-wrap text-xs">
              <span
                v-if="!showMiddleNameField"
                class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                @click="displayMiddleNameField">
                {{ $t('vault.create_contact_add_middle_name') }}
              </span>
              <span
                v-if="!showNicknameField"
                class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                @click="displayNicknameField">
                {{ $t('vault.create_contact_add_nickname') }}
              </span>
              <span
                v-if="!showMaidenNameField"
                class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                @click="displayMaidenNameField">
                {{ $t('vault.create_contact_add_maiden_name') }}
              </span>
              <span
                v-if="data.genders.length > 0 && !showGenderField"
                class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                @click="displayGenderField">
                {{ $t('vault.create_contact_add_gender') }}
              </span>
              <span
                v-if="data.pronouns.length > 0 && !showPronounField"
                class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                @click="displayPronounField">
                {{ $t('vault.create_contact_add_pronoun') }}
              </span>
              <span
                v-if="data.templates.length > 0 && !showTemplateField"
                class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                @click="displayTemplateField">
                {{ $t('vault.create_contact_add_change_template') }}
              </span>
            </div>
          </div>

          <!-- actions -->
          <div class="flex justify-between p-5">
            <pretty-link :href="data.url.back" :text="$t('app.cancel')" :classes="'mr-3'" />
            <pretty-button
              :href="'data.url.vault.create'"
              :text="$t('app.add')"
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
import Layout from '@/Shared/Layout.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import Errors from '@/Shared/Form/Errors.vue';

export default {
  components: {
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
      form: {
        first_name: '',
        last_name: '',
        middle_name: '',
        nickname: '',
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

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          localStorage.success = this.$t('vault.create_contact_success');
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
