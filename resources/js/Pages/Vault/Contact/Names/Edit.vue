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
              <InertiaLink :href="data.url.show" class="text-blue-500 hover:underline">
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
            <li class="inline">{{ $t('Edit names') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-lg px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <form
          class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5 dark:border-gray-700 dark:bg-blue-900">
            <h1 class="text-center text-2xl font-medium">{{ $t('Edit a contact') }}</h1>
          </div>
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

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
              :id="'middle_name'"
              v-model="form.middle_name"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('Middle name')" />

            <!-- nickname -->
            <text-input
              :id="'nickname'"
              v-model="form.nickname"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('Nickname')" />

            <!-- nickname -->
            <text-input
              :id="'maiden_name'"
              v-model="form.maiden_name"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('Maiden name')" />

            <!-- genders -->
            <dropdown
              v-model="form.gender_id"
              :data="data.genders"
              :required="false"
              :class="'mb-5'"
              :placeholder="$t('Choose a value')"
              :dropdown-class="'block w-full'"
              :label="$t('Gender')" />

            <!-- pronouns -->
            <dropdown
              v-model="form.pronoun_id"
              :data="data.pronouns"
              :required="false"
              :class="'mb-5'"
              :placeholder="$t('Choose a value')"
              :dropdown-class="'block w-full'"
              :label="$t('Pronoun')" />

            <!-- prefix -->
            <text-input
              :id="'prefix'"
              v-model="form.prefix"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('Prefix')" />

            <!-- suffix -->
            <text-input
              :id="'suffix'"
              v-model="form.suffix"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('Suffix')" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-link :href="data.url.show" :text="$t('Cancel')" :class="'me-3'" />
            <pretty-button
              :href="'data.url.vault.create'"
              :text="$t('Update')"
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
      form: {
        first_name: '',
        last_name: '',
        middle_name: '',
        nickname: '',
        maiden_name: '',
        prefix: '',
        suffix: '',
        gender_id: '',
        pronoun_id: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.form.first_name = this.data.contact.first_name;
    this.form.last_name = this.data.contact.last_name;
    this.form.middle_name = this.data.contact.middle_name;
    this.form.nickname = this.data.contact.nickname;
    this.form.maiden_name = this.data.contact.maiden_name;
    this.form.gender_id = this.data.contact.gender_id;
    this.form.pronoun_id = this.data.contact.pronoun_id;
    this.form.suffix = this.data.contact.suffix;
    this.form.prefix = this.data.contact.prefix;
  },

  methods: {
    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.update, this.form)
        .then((response) => {
          localStorage.success = this.$t('The contact has been updated');
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
