<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="sm:mt-20 sm:border-b bg-white">
      <div class="max-w-8xl mx-auto px-4 sm:px-6 py-2 hidden md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="inline mr-2 text-gray-600">You are here:</li>
            <li class="inline mr-2">
              <inertia-link :href="data.url.back" class="text-sky-500 hover:text-blue-900">Contacts</inertia-link>
            </li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Create a contact</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-16 relative">
      <div class="max-w-lg mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <form class="bg-white border border-gray-200 rounded-lg mb-6" @submit.prevent="submit()">
          <div class="p-5 border-b border-gray-200 bg-blue-50 section-head">
            <h1 class="text-center text-2xl font-medium">
              Add a contact
            </h1>
          </div>
          <div class="p-5 border-b border-gray-200">
            <errors :errors="form.errors" />

            <!-- first name -->
            <text-input v-model="form.first_name" :autofocus="true" :div-outer-class="'mb-5'" :input-class="'block w-full'" :required="true"
                        :maxlength="255" :label="'First name'"
            />

            <!-- last name -->
            <text-input :id="'last_name'" v-model="form.last_name" :div-outer-class="'mb-5'" :input-class="'block w-full'" :required="false"
                        :maxlength="255" :label="'Last name'"
            />

            <!-- middle name -->
            <text-input v-if="showMiddleNameField" :id="'middle_name'" v-model="form.middle_name" :div-outer-class="'mb-5'" :input-class="'block w-full'"
                        :required="false"
                        :maxlength="255" :label="'Middle name'"
            />

            <!-- nickname -->
            <text-input v-if="showNicknameField" :id="'nickname'" v-model="form.nickname" :div-outer-class="'mb-5'" :input-class="'block w-full'"
                        :required="false"
                        :maxlength="255" :label="'Nickname'"
            />

            <!-- nickname -->
            <text-input v-if="showMaidenNameField" :id="'maiden_name'" v-model="form.maiden_name" :div-outer-class="'mb-5'" :input-class="'block w-full'"
                        :required="false"
                        :maxlength="255" :label="'Maiden name'"
            />

            <!-- genders -->
            <dropdown v-if="showGenderField" v-model="form.gender_id" :data="data.genders" :required="false" :div-outer-class="'mb-5'"
                      :placeholder="'Choose a value'" :dropdown-class="'block w-full'" :label="'Gender'"
            />

            <!-- pronouns -->
            <dropdown v-if="showPronounField" v-model="form.pronoun_id" :data="data.pronouns" :required="false" :div-outer-class="'mb-5'"
                      :placeholder="'Choose a value'" :dropdown-class="'block w-full'" :label="'Pronoun'"
            />

            <!-- templates -->
            <dropdown v-if="showTemplateField" v-model="form.template_id" :data="data.templates" :required="false" :div-outer-class="'mb-5'"
                      :placeholder="'Choose a value'" :dropdown-class="'block w-full'" :label="'Use the following template for this contact'"
            />

            <!-- other fields -->
            <div class="text-xs flex flex-wrap">
              <span v-if="!showMiddleNameField" class="border rounded-lg bg-slate-200 hover:bg-slate-300 px-1 py-1 mr-2 mb-2 flex flex-wrap cursor-pointer" @click="displayMiddleNameField">
                + middle name
              </span>
              <span v-if="!showNicknameField" class="border rounded-lg bg-slate-200 hover:bg-slate-300 px-1 py-1 mr-2 mb-2 flex flex-wrap cursor-pointer" @click="displayNicknameField">
                + nickname
              </span>
              <span v-if="!showMaidenNameField" class="border rounded-lg bg-slate-200 hover:bg-slate-300 px-1 py-1 mr-2 mb-2 flex flex-wrap cursor-pointer" @click="displayMaidenNameField">
                + maiden name
              </span>
              <span v-if="data.genders.length > 0 && !showGenderField" class="border rounded-lg bg-slate-200 hover:bg-slate-300 px-1 py-1 mr-2 mb-2 flex flex-wrap cursor-pointer" @click="displayGenderField">
                + gender
              </span>
              <span v-if="data.pronouns.length > 0 && !showPronounField" class="border rounded-lg bg-slate-200 hover:bg-slate-300 px-1 py-1 mr-2 mb-2 flex flex-wrap cursor-pointer" @click="displayPronounField">
                + pronoun
              </span>
              <span v-if="data.templates.length > 0 && !showTemplateField" class="border rounded-lg bg-slate-200 hover:bg-slate-300 px-1 py-1 mr-2 mb-2 flex flex-wrap cursor-pointer" @click="displayTemplateField">
                + change template
              </span>
            </div>
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
import Dropdown from '@/Shared/Dropdown';
import Errors from '@/Shared/Errors';

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
        description: '',
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

      axios.post(this.data.url.store, this.form)
        .then(response => {
          localStorage.success = 'The contact has been added';
          this.$inertia.visit(response.data.data);
        })
        .catch(error => {
          this.form.errors = error.response.data;
          this.loadingState = null;
        });
    },
  },
};
</script>
