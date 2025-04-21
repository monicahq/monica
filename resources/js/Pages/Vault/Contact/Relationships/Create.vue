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
              <InertiaLink :href="layoutData.vault.url.contacts" class="text-blue-500 hover:underline">
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
            <li class="me-2 inline">
              <InertiaLink :href="data.url.contact" class="text-blue-500 hover:underline">
                {{ $t('Profile of :name', { name: data.contact.name }) }}
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
            <li class="inline">{{ $t('Add a relationship') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-lg px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <form
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <!-- header -->
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5 dark:border-gray-700 dark:bg-blue-900">
            <h1 class="text-center text-2xl font-medium">{{ $t('Add a relationship') }}</h1>
          </div>

          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <!-- relationship type -->
            <label for="types" class="mb-2 block text-sm"> {{ $t('Select a relationship type') }} </label>
            <Dropdown
              id="types"
              v-model="form.relationship_type_id"
              name="types"
              class="w-full rounded-md border-gray-300 bg-white px-3 py-2 shadow-xs focus:border-indigo-300 focus:outline-hidden focus:ring-3 focus:ring-indigo-200/50 dark:bg-gray-900 sm:text-sm"
              @update:model-value="load"
              :data="fromRelationshipOptions" />
          </div>

          <!-- data once the relatonship type has been selected -->
          <div v-if="showRelationshipTypeDetails">
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <!-- relationship -->
              <div class="mb-6">
                <p
                  class="mb-2 inline-block flex-none rounded-md bg-gray-200 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                  {{ fromRelationship }}
                </p>
                <div class="flex items-center">
                  <avatar :data="data.contact.avatar" :class="'me-2 h-5 w-5'" />

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
                    class="me-2 h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                  </svg>
                  <span class="text-xs"> {{ $t('Switch role') }} </span>
                </div>
              </div>

              <!-- reverse relationship -->
              <div>
                <p
                  class="mb-2 inline-block flex-none rounded-md bg-gray-200 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                  {{ toRelationship }}
                </p>
                <div>
                  <!-- I don't know the name -->
                  <div class="mb-2 flex items-center">
                    <input
                      id="unknown"
                      v-model="form.choice"
                      value="unknown"
                      name="name-order"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700"
                      @click="hideContactNameField" />
                    <label
                      for="unknown"
                      class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('I don’t know the name') }}
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
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700"
                      @click="displayContactNameField" />
                    <label
                      for="name"
                      class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('I know the name') }}
                    </label>
                  </div>

                  <div v-if="showContactName" class="ps-6">
                    <text-input
                      ref="contactName"
                      v-model="form.first_name"
                      :autofocus="true"
                      :class="'mb-5'"
                      :input-class="'block w-full'"
                      :required="true"
                      :maxlength="255" />

                    <!-- last name -->
                    <text-input
                      v-if="showLastNameField"
                      :id="'last_name'"
                      v-model="form.last_name"
                      :class="'mb-5'"
                      :input-class="'block w-full'"
                      :required="false"
                      :maxlength="255"
                      :label="$t('Last name')" />

                    <div class="mb-4 flex flex-wrap text-xs">
                      <span
                        v-if="!showLastNameField"
                        class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900"
                        @click="displayLastNameField">
                        {{ $t('+ last name') }}
                      </span>
                      <span
                        v-if="!showMiddleNameField"
                        class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900"
                        @click="displayMiddleNameField">
                        {{ $t('+ middle name') }}
                      </span>
                      <span
                        v-if="!showNicknameField"
                        class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900"
                        @click="displayNicknameField">
                        {{ $t('+ nickname') }}
                      </span>
                      <span
                        v-if="!showMaidenNameField"
                        class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900"
                        @click="displayMaidenNameField">
                        {{ $t('+ maiden name') }}
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
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700"
                      @input="displayContactSelector" />
                    <label
                      for="contact"
                      class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('Choose an existing contact') }}
                    </label>
                  </div>

                  <div v-if="form.choice === 'contact'" class="ps-6">
                    <contact-selector
                      v-model="form.other_contact_id"
                      :search-url="layoutData.vault.url.search_contacts_only"
                      :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
                      :display-most-consulted-contacts="false"
                      :add-multiple-contacts="false"
                      :required="true"
                      :class="'flex-1 border-gray-200 dark:border-gray-700'" />
                  </div>
                </div>
              </div>
            </div>

            <div v-if="showMoreContactOptions" class="border-b border-gray-200 p-5 dark:border-gray-700">
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

              <!-- nickname -->
              <text-input
                v-if="showMaidenNameField"
                :id="'maiden_name'"
                v-model="form.maiden_name"
                :class="'mb-5'"
                :input-class="'block w-full'"
                :required="false"
                :maxlength="255"
                :label="$t('Maiden name')" />

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

              <!-- other fields -->
              <div class="flex flex-wrap text-xs">
                <span
                  v-if="data.genders.length > 0 && !showGenderField"
                  class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900"
                  @click="displayGenderField">
                  {{ $t('+ gender') }}
                </span>
                <span
                  v-if="data.pronouns.length > 0 && !showPronounField"
                  class="mb-2 me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 hover:bg-slate-300 dark:bg-slate-500 dark:text-gray-900"
                  @click="displayPronounField">
                  {{ $t('+ pronoun') }}
                </span>
              </div>
            </div>

            <!-- create a contact entry -->
            <div v-if="form.choice !== 'contact'" class="border-b border-gray-200 p-5 dark:border-gray-700">
              <div class="relative flex items-start">
                <input
                  id="create-contact"
                  v-model="form.create_contact_entry"
                  name="create-contact"
                  type="checkbox"
                  class="focus:ring-3 relative h-4 w-4 rounded-xs border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600" />
                <label for="create-contact" class="ms-2 block cursor-pointer text-sm text-gray-900 dark:text-white">
                  {{ $t('Create a contact entry for this person') }}
                </label>
              </div>
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
import ContactSelector from '@/Shared/Form/ContactSelector.vue';
import Avatar from '@/Shared/Avatar.vue';

export default {
  components: {
    InertiaLink: Link,
    Layout,
    PrettyLink,
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
        other_contact_id: [],
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

  computed: {
    fromRelationshipOptions() {
      return _.map(this.data.relationship_group_types, (group) => {
        return {
          id: group.id,
          optgroup: group.name,
          options: _.map(group.types, (type) => {
            return {
              id: type.id,
              name: type.name,
            };
          }),
        };
      });
    },
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

      this.$nextTick().then(() => {
        this.$refs.contactName.focus();
      });
    },

    hideContactNameField() {
      this.form.choice = 'unknown';
      this.form.first_name = '';
      this.form.last_name = '';
      this.form.middle_name = '';
      this.form.nickname = '';
      this.form.maiden_name = '';
      this.form.gender_id = '';
      this.form.pronoun_id = '';
      this.showContactName = false;
      this.showMoreContactOptions = false;
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

      if (this.form.base_contact_id === this.data.contact.id) {
        this.form.base_contact_id = 0;
      } else {
        this.form.base_contact_id = this.data.contact.id;
      }
    },

    load(target) {
      var id = this.data.relationship_types.findIndex((x) => x.id === parseInt(target));
      this.fromRelationship = this.data.relationship_types[id].name;
      this.toRelationship = this.data.relationship_types[id].name_reverse_relationship;
      this.showRelationshipTypeDetails = true;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          localStorage.success = this.$t('The relationship has been added');
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
</style>
