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
              <inertia-link :href="layoutData.vault.url.contacts" class="text-blue-500 hover:underline">
                Contacts
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
            <li class="mr-2 inline">
              <inertia-link :href="data.url.contact" class="text-blue-500 hover:underline">
                Profile of {{ data.contact.name }}
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
            <li class="inline">Add a relationship</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-lg px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <form class="mb-6 rounded-lg border border-gray-200 bg-white" @submit.prevent="submit()">
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5">
            <h1 class="text-center text-2xl font-medium">Add a relationship</h1>
          </div>
          <div class="border-b border-gray-200 p-5">
            <errors :errors="form.errors" />

            <!-- relationship type -->
            <label for="types" class="mb-2 block text-sm"> Select a relationship type </label>
            <select
              id="types"
              v-model="form.relationship_type_id"
              name="types"
              class="w-full rounded-md border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm"
              @change="load()">
              <optgroup v-for="group in data.relationship_group_types" :key="group.id" :label="group.name">
                <option v-for="type in group.types" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </optgroup>
            </select>
          </div>

          <!-- data once the relatonship type has been selected -->
          <div v-if="showRelationshipTypeDetails">
            <div class="border-b border-gray-200 p-5">
              <!-- relationship -->
              <div class="mb-6">
                <p
                  class="mb-2 inline-block flex-none rounded-md bg-gray-200 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gray-600">
                  {{ fromRelationship }}
                </p>
                <div class="flex items-center">
                  <avatar :data="data.contact.avatar" :classes="'mr-2 h-5 w-5'" />

                  <span>{{ data.contact.name }}</span>
                </div>
              </div>

              <!-- switch -->
              <div
                class="w-100 mb-4 block cursor-pointer text-center text-gray-400 hover:text-gray-900"
                @click="toggle()">
                <div class="flex">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="mr-2 h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                  </svg>
                  <span class="text-xs"> Switch role </span>
                </div>
              </div>

              <!-- reverse relationship -->
              <div>
                <p
                  class="mb-2 inline-block flex-none rounded-md bg-gray-200 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gray-600">
                  {{ toRelationship }}
                </p>
                <div class="">
                  <!-- I don't know the name -->
                  <div class="mb-2 flex items-center">
                    <input
                      id="unknown"
                      v-model="form.choice"
                      value="unknown"
                      name="name-order"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500" />
                    <label for="unknown" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                      I don't know the name
                    </label>
                  </div>

                  <!-- I know the contact's name -->
                  <div class="mb-2 flex items-center">
                    <input
                      id="name"
                      v-model="form.choice"
                      value="name"
                      name="name-order"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500"
                      @click="displayContactNameField" />
                    <label for="name" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                      I know the name
                    </label>
                  </div>

                  <div v-if="showContactName" class="pl-6">
                    <text-input
                      :ref="'contactName'"
                      v-model="form.first_name"
                      :autofocus="true"
                      :div-outer-class="'mb-5'"
                      :input-class="'block w-full'"
                      :required="true"
                      :maxlength="255" />

                    <!-- last name -->
                    <text-input
                      v-if="showLastNameField"
                      :id="'last_name'"
                      v-model="form.last_name"
                      :div-outer-class="'mb-5'"
                      :input-class="'block w-full'"
                      :required="false"
                      :maxlength="255"
                      :label="'Last name'" />

                    <div class="mb-4 flex flex-wrap text-xs">
                      <span
                        v-if="!showLastNameField"
                        class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                        @click="displayLastNameField">
                        + last name
                      </span>
                      <span
                        v-if="!showMiddleNameField"
                        class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                        @click="displayMiddleNameField">
                        + middle name
                      </span>
                      <span
                        v-if="!showNicknameField"
                        class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                        @click="displayNicknameField">
                        + nickname
                      </span>
                      <span
                        v-if="!showMaidenNameField"
                        class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                        @click="displayMaidenNameField">
                        + maiden name
                      </span>
                    </div>
                  </div>

                  <!-- Choose an existing contact -->
                  <div class="mb-2 flex items-center">
                    <input
                      id="contact"
                      v-model="form.choice"
                      value="contact"
                      name="name-order"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500"
                      @click="displayContactSelector" />
                    <label for="contact" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                      Choose an existing contact
                    </label>
                  </div>

                  <div v-if="form.choice == 'contact'" class="pl-6">
                    <contact-selector
                      v-model="form.other_contact_id"
                      :search-url="layoutData.vault.url.search_contacts_only"
                      :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
                      :display-most-consulted-contacts="false"
                      :add-multiple-contacts="false"
                      :required="true"
                      :div-outer-class="'flex-1 border-r border-gray-200'" />
                  </div>
                </div>
              </div>
            </div>

            <div v-if="showMoreContactOptions" class="border-b border-gray-200 p-5">
              <!-- middle name -->
              <text-input
                v-if="showMiddleNameField"
                :id="'middle_name'"
                v-model="form.middle_name"
                :div-outer-class="'mb-5'"
                :input-class="'block w-full'"
                :required="false"
                :maxlength="255"
                :label="'Middle name'" />

              <!-- nickname -->
              <text-input
                v-if="showNicknameField"
                :id="'nickname'"
                v-model="form.nickname"
                :div-outer-class="'mb-5'"
                :input-class="'block w-full'"
                :required="false"
                :maxlength="255"
                :label="'Nickname'" />

              <!-- nickname -->
              <text-input
                v-if="showMaidenNameField"
                :id="'maiden_name'"
                v-model="form.maiden_name"
                :div-outer-class="'mb-5'"
                :input-class="'block w-full'"
                :required="false"
                :maxlength="255"
                :label="'Maiden name'" />

              <!-- genders -->
              <dropdown
                v-if="showGenderField"
                v-model="form.gender_id"
                :data="data.genders"
                :required="false"
                :div-outer-class="'mb-5'"
                :placeholder="$t('app.choose_value')"
                :dropdown-class="'block w-full'"
                :label="'Gender'" />

              <!-- pronouns -->
              <dropdown
                v-if="showPronounField"
                v-model="form.pronoun_id"
                :data="data.pronouns"
                :required="false"
                :div-outer-class="'mb-5'"
                :placeholder="$t('app.choose_value')"
                :dropdown-class="'block w-full'"
                :label="'Pronoun'" />

              <!-- other fields -->
              <div class="flex flex-wrap text-xs">
                <span
                  v-if="data.genders.length > 0 && !showGenderField"
                  class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                  @click="displayGenderField">
                  + gender
                </span>
                <span
                  v-if="data.pronouns.length > 0 && !showPronounField"
                  class="mr-2 mb-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300"
                  @click="displayPronounField">
                  + pronoun
                </span>
              </div>
            </div>

            <!-- create a contact entry -->
            <div v-if="form.choice != 'contact'" class="border-b border-gray-200 p-5">
              <div class="relative flex items-start">
                <input
                  id="create-contact"
                  v-model="form.create_contact_entry"
                  name="create-contact"
                  type="checkbox"
                  class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600" />
                <label for="create-contact" class="ml-2 block cursor-pointer text-sm text-gray-900">
                  Create a contact entry for this person
                </label>
              </div>
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
import Layout from '@/Shared/Layout';
import PrettySpan from '@/Shared/Form/PrettySpan';
import PrettyLink from '@/Shared/Form/PrettyLink';
import PrettyButton from '@/Shared/Form/PrettyButton';
import TextInput from '@/Shared/Form/TextInput';
import Dropdown from '@/Shared/Form/Dropdown';
import Errors from '@/Shared/Form/Errors';
import ContactSelector from '@/Shared/Form/ContactSelector';
import Avatar from '@/Shared/Avatar';

export default {
  components: {
    Layout,
    PrettyLink,
    PrettySpan,
    PrettyButton,
    TextInput,
    Dropdown,
    Errors,
    ContactSelector,
    Avatar,
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
      showRelationshipTypeDetails: false,
      showMoreContactOptions: false,
      showContactName: false,
      showLastNameField: false,
      showMiddleNameField: false,
      showNicknameField: false,
      showMaidenNameField: false,
      showGenderField: false,
      showPronounField: false,
      fromRelationship: '',
      toRelationship: '',
      form: {
        choice: 'unknown',
        create_contact_entry: false,
        relationship_type_id: 0,
        base_contact_id: 0,
        other_contact_id: 0,
        last_name: '',
        middle_name: '',
        nickname: '',
        maiden_name: '',
        gender_id: '',
        pronoun_id: '',
        errors: [],
      },
    };
  },

  created() {
    this.form.base_contact_id = this.data.contact.id;
    this.fromRelationship = 'Father';
    this.toRelationship = 'Child';
  },

  methods: {
    displayContactNameField() {
      this.form.choice = 'name';
      this.showContactName = true;
      this.showMoreContactOptions = true;

      this.$nextTick(() => {
        this.$refs.contactName.focus();
      });
    },

    displayContactSelector() {
      this.form.choice = 'choice';
      this.showContactName = false;
      this.showMoreContactOptions = false;
    },

    displayLastNameField() {
      this.showLastNameField = true;
    },

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

    toggle() {
      var temp = this.fromRelationship;
      this.fromRelationship = this.toRelationship;
      this.toRelationship = temp;

      if (this.form.base_contact_id == this.data.contact.id) {
        this.form.base_contact_id = 0;
      } else {
        this.form.base_contact_id = this.data.contact.id;
      }
    },

    load() {
      var id = this.data.relationship_types.findIndex((x) => x.id === this.form.relationship_type_id);
      this.fromRelationship = this.data.relationship_types[id].name;
      this.toRelationship = this.data.relationship_types[id].name_reverse_relationship;
      this.showRelationshipTypeDetails = true;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          localStorage.success = 'The relationship has been added';
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

input[type='checkbox'] {
  top: 3px;
}

select {
  padding-left: 8px;
  padding-right: 20px;
  background-position: right 3px center;
}
</style>
