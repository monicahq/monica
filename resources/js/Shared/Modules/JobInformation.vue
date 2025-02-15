<template>
  <div class="mb-4">
    <div class="pb-1 mb-2 items-center justify-between border-b border-gray-200 dark:border-gray-700 flex">
      <div class="text-xs">{{ $t('Job information') }}</div>
      <span v-if="!editJobInformation" class="relative cursor-pointer" @click="showEditModal">
        <Pencil class="h-3 w-3 text-gray-400" />
      </span>

      <!-- close button -->
      <span
        v-if="editJobInformation"
        class="cursor-pointer text-xs text-gray-600 dark:text-gray-400"
        @click="editJobInformation = false">
        {{ $t('Close') }}
      </span>
    </div>

    <!-- edit job information -->
    <div
      v-if="editJobInformation"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
      <form @submit.prevent="update">
        <div class="border-b border-gray-200 p-2 dark:border-gray-700">
          <errors :errors="form.errors" />

          <!-- companies -->
          <dropdown
            v-if="showDropdownCompanies"
            v-model.number="form.company_id"
            :data="localCompanies"
            :required="false"
            :class="'mb-2'"
            :placeholder="$t('Choose a value')"
            :dropdown-class="'block w-full'"
            :label="$t('Existing company')" />

          <p
            v-if="showCreateCompanyLink"
            class="cursor-pointer text-sm text-blue-500 hover:underline"
            @click="showCreateCompany()">
            {{ $t('Or create a new one') }}
          </p>

          <!-- create a new company -->
          <text-input
            v-if="showCreateCompanyField"
            ref="name"
            v-model="form.company_name"
            :label="$t('Company name')"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :required="false"
            :autocomplete="false"
            :maxlength="255" />
        </div>

        <div class="border-b border-gray-200 p-2 dark:border-gray-700">
          <!-- job position -->
          <text-input
            v-model="form.job_position"
            :label="$t('Job position')"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :required="false"
            :autocomplete="false"
            :maxlength="255" />
        </div>

        <div class="flex justify-between p-2">
          <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editJobInformation = false" />
          <pretty-button
            :href="'data.url.vault.create'"
            :text="$t('Save')"
            :state="loadingState"
            :icon="'check'"
            :class="'save'" />
        </div>

        <div class="border-t border-gray-200 p-2 dark:border-gray-700">
          <p class="cursor-pointer text-sm text-blue-500 hover:underline" @click="reset()">
            {{ $t('Or reset the fields') }}
          </p>
        </div>
      </form>
    </div>

    <!-- blank state -->
    <p v-if="!form.job_position && !company_name" class="text-sm text-gray-600 dark:text-gray-400">
      {{ $t('Not set') }}
    </p>

    <p v-else>
      <span v-if="form.job_position">
        {{ form.job_position }}
        <span v-if="company_name" class="text-sm text-gray-600 dark:text-gray-400">{{ $t('at ') }}</span>
      </span>
      <span v-if="company_name">{{ company_name }}</span>
    </p>
  </div>
</template>

<script>
import TextInput from '@/Shared/Form/TextInput.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import Errors from '@/Shared/Form/Errors.vue';
import { Pencil } from 'lucide-vue-next';

export default {
  components: {
    TextInput,
    Errors,
    Dropdown,
    PrettySpan,
    PrettyButton,
    Pencil,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      loadingState: '',
      showDropdownCompanies: false,
      showCreateCompanyLink: false,
      showCreateCompanyField: false,
      editJobInformation: false,
      localCompanies: [],
      company_name: '',
      form: {
        job_position: '',
        company_name: '',
        company_id: 0,
        errors: [],
      },
    };
  },

  created() {
    this.form.job_position = this.data.job_position;
    this.form.company_id = this.data.company ? this.data.company.id : null;
    this.company_name = this.data.company ? this.data.company.name : null;
  },

  methods: {
    showEditModal() {
      this.showDropdownCompanies = true;
      this.showCreateCompanyLink = true;
      this.showCreateCompanyField = false;
      this.editJobInformation = true;
      this.getCompanies();
    },

    showCreateCompany() {
      this.showCreateCompanyLink = false;
      this.showDropdownCompanies = false;
      this.showCreateCompanyField = true;
      this.form.company_name = '';

      this.$nextTick().then(() => {
        this.$refs.name.focus();
      });
    },

    getCompanies() {
      if (this.localCompanies.length > 0) {
        return;
      }

      axios
        .get(this.data.url.index)
        .then((response) => {
          this.localCompanies = response.data.data;

          if (this.localCompanies.length === 0) {
            this.showDropdownCompanies = false;
            this.showCreateCompanyLink = false;
            this.showCreateCompanyField = true;
          }
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    update() {
      this.loadingState = 'loading';

      axios
        .put(this.data.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The job information has been saved'), 'success');
          this.editJobInformation = false;
          this.loadingState = '';
          this.company_name = response.data.data.company.name;
          this.localCompanies.push(response.data.data);
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    reset() {
      axios
        .delete(this.data.url.destroy)
        .then(() => {
          this.editJobInformation = false;
          this.company_name = '';
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.icon-sidebar {
  top: -2px;
}
</style>
