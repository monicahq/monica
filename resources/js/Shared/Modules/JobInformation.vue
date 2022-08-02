<template>
  <div class="mb-4">
    <div class="mb-3 items-center justify-between border-b border-gray-200 sm:flex">
      <div class="mb-2 text-xs sm:mb-0">Job information</div>
      <span v-if="!editJobInformation" class="relative cursor-pointer" @click="showEditModal">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="icon-sidebar relative inline h-3 w-3 text-gray-300 hover:text-gray-600"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
      </span>

      <!-- close button -->
      <span v-if="editJobInformation" class="cursor-pointer text-xs text-gray-600" @click="editJobInformation = false">
        Close
      </span>
    </div>

    <!-- edit job information -->
    <div v-if="editJobInformation" class="bg-form mb-6 rounded-lg border border-gray-200">
      <form @submit.prevent="update">
        <div class="border-b border-gray-200 p-2">
          <errors :errors="form.errors" />

          <!-- companies -->
          <dropdown
            v-if="showDropdownCompanies"
            v-model="form.company_id"
            :data="localCompanies"
            :required="false"
            :div-outer-class="'mb-2'"
            :placeholder="$t('app.choose_value')"
            :dropdown-class="'block w-full'"
            :label="'Existing company'" />

          <p
            v-if="showCreateCompanyLink"
            class="cursor-pointer text-sm text-blue-500 hover:underline"
            @click="showCreateCompany()">
            Or create a new one
          </p>

          <!-- create a new company -->
          <text-input
            v-if="showCreateCompanyField"
            :ref="'name'"
            v-model="form.company_name"
            :label="'Company name'"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :required="true"
            :autocomplete="false"
            :maxlength="255" />
        </div>

        <div class="border-b border-gray-200 p-2">
          <!-- job position -->
          <text-input
            v-model="form.job_position"
            :label="'Job position'"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :required="true"
            :autocomplete="false"
            :maxlength="255" />
        </div>

        <div class="flex justify-between p-2">
          <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="editJobInformation = false" />
          <pretty-button
            :href="'data.url.vault.create'"
            :text="$t('app.save')"
            :state="loadingState"
            :icon="'check'"
            :classes="'save'" />
        </div>
      </form>
    </div>

    <!-- blank state -->
    <p v-if="!form.job_position && !company_name" class="text-sm text-gray-600">Not set</p>

    <p v-else>
      <span v-if="form.job_position">
        {{ form.job_position }}
        <span v-if="company_name" class="text-sm text-gray-600">at </span>
      </span>
      <span v-if="company_name">{{ company_name }}</span>
    </p>
  </div>
</template>

<script>
import TextInput from '@/Shared/Form/TextInput';
import Dropdown from '@/Shared/Form/Dropdown';
import PrettySpan from '@/Shared/Form/PrettySpan';
import PrettyButton from '@/Shared/Form/PrettyButton';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    TextInput,
    Errors,
    Dropdown,
    PrettySpan,
    PrettyButton,
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

      this.$nextTick(() => {
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

          if (this.localCompanies.length == 0) {
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
          this.flash('The job information has been saved', 'success');
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
  },
};
</script>

<style lang="scss" scoped>
.icon-sidebar {
  top: -2px;
}

.label-list {
  border-bottom-left-radius: 8px;
  border-bottom-right-radius: 8px;

  li:last-child {
    border-bottom: 0;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }

  li:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
