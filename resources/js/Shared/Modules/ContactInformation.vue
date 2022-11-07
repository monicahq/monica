<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative mr-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-sidebar relative inline h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </span>

        <span class="font-semibold">
          {{ $t('contact.contact_information_title') }}
        </span>
      </div>
      <pretty-button
        :text="$t('contact.contact_information_cta')"
        :icon="'plus'"
        :classes="'sm:w-fit w-full'"
        @click="showCreateContactInformationModal" />
    </div>

    <!-- add a contact information modal -->
    <form
      v-if="addContactInformationModalShown"
      class="bg-form mb-6 rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <!-- name -->
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <text-input
            :ref="'newData'"
            v-model="form.data"
            :label="$t('contact.contact_information_name')"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :required="false"
            :autocomplete="false"
            :maxlength="255"
            @esc-key-pressed="addContactInformationModalShown = false" />
        </div>

        <div class="p-5">
          <!-- contact information types -->
          <dropdown
            v-model="form.contact_information_type_id"
            :data="data.contact_information_types"
            :required="true"
            :placeholder="$t('app.choose_value')"
            :dropdown-class="'block w-full'"
            :label="$t('contact.contact_information_type')" />
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="addContactInformationModalShown = false" />
        <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
      </div>
    </form>

    <!-- contact infos -->
    <div v-if="localContactInformation.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="info in localContactInformation"
          :key="info.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
          <!-- contact information -->
          <div v-if="editedContactInformationId != info.id" class="flex items-center justify-between px-3 py-2">
            <a :href="info.data" class="text-blue-500 hover:underline">{{ info.label }} </a>

            <!-- actions -->
            <ul class="text-sm">
              <li
                class="mr-4 inline cursor-pointer text-blue-500 hover:underline"
                @click="showEditContactInformationModal(info)">
                {{ $t('app.edit') }}
              </li>
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(info)">
                {{ $t('app.delete') }}
              </li>
            </ul>
          </div>

          <!-- edit info modal -->
          <form v-if="editedContactInformationId == info.id" class="bg-form" @submit.prevent="update(info)">
            <div class="border-b border-gray-200 dark:border-gray-700">
              <div v-if="form.errors.length > 0" class="p-5">
                <errors :errors="form.errors" />
              </div>

              <!-- name -->
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <text-input
                  :ref="'newData'"
                  v-model="form.data"
                  :label="$t('contact.contact_information_name')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="addContactInformationModalShown = false" />
              </div>

              <div class="p-5">
                <!-- contact information types -->
                <dropdown
                  v-model="form.contact_information_type_id"
                  :data="data.contact_information_types"
                  :required="true"
                  :placeholder="$t('app.choose_value')"
                  :dropdown-class="'block w-full'"
                  :label="$t('contact.contact_information_type')" />
              </div>
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="editedContactInformationId = 0" />
              <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'check'" :classes="'save'" />
            </div>
          </form>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-if="localContactInformation.length == 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_contact.svg" class="mx-auto mt-4 h-20 w-20" />
      <p class="px-5 pb-5 pt-2 text-center">
        {{ $t('contact.contact_information_blank') }}
      </p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import Errors from '@/Shared/Form/Errors.vue';

export default {
  components: {
    PrettyButton,
    PrettySpan,
    TextInput,
    Dropdown,
    Errors,
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
      addContactInformationModalShown: false,
      localContactInformation: [],
      editedContactInformationId: 0,
      form: {
        data: '',
        contact_information_type_id: 0,
        errors: [],
      },
    };
  },

  created() {
    this.localContactInformation = this.data.contact_information;
  },

  methods: {
    showCreateContactInformationModal() {
      this.addContactInformationModalShown = true;
      this.form.errors = [];
      this.form.data = '';
      this.form.contact_information_type_id = 0;

      this.$nextTick(() => {
        this.$refs.newData.focus();
      });
    },

    showEditContactInformationModal(info) {
      this.form.errors = [];
      this.editedContactInformationId = info.id;
      this.form.contact_information_type_id = info.contact_information_type.id;
      this.form.data = info.data;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('contact.contact_information_new_success'), 'success');
          this.localContactInformation.unshift(response.data.data);
          this.loadingState = '';
          this.addContactInformationModalShown = false;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    update(info) {
      this.loadingState = 'loading';

      axios
        .put(info.url.update, this.form)
        .then((response) => {
          this.loadingState = '';
          this.flash(this.$t('contact.contact_information_edit_success'), 'success');
          this.localContactInformation[this.localContactInformation.findIndex((x) => x.id === info.id)] =
            response.data.data;
          this.editedContactInformationId = 0;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    destroy(info) {
      if (confirm(this.$t('contact.contact_information_delete_confirm'))) {
        axios
          .delete(info.url.destroy)
          .then(() => {
            this.flash(this.$t('contact.contact_information_delete_success'), 'success');
            var id = this.localContactInformation.findIndex((x) => x.id === info.id);
            this.localContactInformation.splice(id, 1);
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
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

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

select {
  padding-left: 8px;
  padding-right: 20px;
  background-position: right 3px center;
}
</style>
