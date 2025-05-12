<template>
  <div class="mb-12">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0">
        <span class="me-1"> 📁 </span>
        {{ $t('All the important date types used in the vault') }}
      </h3>
      <pretty-button v-if="!createTypeModalShown" :text="$t('Add a type')" :icon="'plus'" @click="showTypeModal" />
    </div>

    <!-- modal to create a type -->
    <form
      v-if="createTypeModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <text-input
          ref="newtype"
          v-model="form.label"
          :label="$t('Name')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          :class="'mb-4'"
          @esc-key-pressed="createTypeModalShown = false" />
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createTypeModalShown = false" />
        <pretty-button :text="$t('Add')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- list of important date types -->
    <ul
      v-if="localTypes.length > 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <li
        v-for="type in localTypes"
        :key="type.id"
        class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800 dark:hover:bg-slate-900">
        <!-- detail of the type -->
        <div v-if="editTypeModalShownId !== type.id" class="flex items-center justify-between px-5 py-2">
          <span class="text-base">
            {{ type.label }}
            <span
              v-if="type.internal_type"
              class="me-2 inline-block rounded-xs bg-neutral-200 px-2 py-1 text-xs font-semibold text-neutral-500 last:me-0"
              >{{ type.internal_type }}</span
            >
          </span>

          <!-- actions -->
          <ul class="text-sm">
            <li class="inline cursor-pointer" @click="edit(type)">
              <span class="text-blue-500 hover:underline">{{ $t('Edit') }}</span>
            </li>
            <li
              v-if="type.can_be_deleted"
              class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900 dark:hover:text-red-100"
              @click="destroy(type)">
              {{ $t('Delete') }}
            </li>
          </ul>
        </div>

        <!-- edit a type modal -->
        <form
          v-if="editTypeModalShownId === type.id"
          class="item-list border-b border-gray-200 bg-gray-50 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:bg-slate-900 dark:hover:bg-slate-800 dark:hover:bg-slate-900"
          @submit.prevent="update(type)">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              ref="rename"
              v-model="form.label"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              :class="'mb-4'"
              @esc-key-pressed="editTypeModalShownId = 0" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="editTypeModalShownId = 0" />
            <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
          </div>
        </form>
      </li>
    </ul>

    <!-- blank state -->
    <div
      v-if="localTypes.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="p-5 text-center">
        {{ $t('Date types are essential as they let you categorize dates that you add to a contact.') }}
      </p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';

export default {
  components: {
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
      createTypeModalShown: false,
      editTypeModalShownId: 0,
      localTypes: [],
      form: {
        label: '',
        internal_type: '',
        can_be_deleted: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localTypes = this.data.contact_important_date_types;
  },

  methods: {
    showTypeModal() {
      this.form.label = '';
      this.createTypeModalShown = true;

      this.$nextTick().then(() => {
        this.$refs.newtype.focus();
      });
    },

    edit(type) {
      this.form.label = type.label;
      this.form.can_be_deleted = type.can_be_deleted;
      this.form.internal_type = type.internal_type;
      this.editTypeModalShownId = type.id;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.contact_date_important_date_type_store, this.form)
        .then((response) => {
          this.flash(this.$t('The type has been created'), 'success');
          this.localTypes.unshift(response.data.data);
          this.loadingState = null;
          this.createTypeModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(type) {
      this.loadingState = 'loading';

      axios
        .put(type.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The type has been updated'), 'success');
          this.localTypes[this.localTypes.findIndex((x) => x.id === type.id)] = response.data.data;
          this.loadingState = null;
          this.editTypeModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(type) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(type.url.destroy)
          .then(() => {
            this.flash(this.$t('The type has been deleted'), 'success');
            var id = this.localTypes.findIndex((x) => x.id === type.id);
            this.localTypes.splice(id, 1);
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
