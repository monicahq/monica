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

<template>
  <div class="mb-12">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0"><span class="mr-1"> 📁 </span> All the important date types used in the vault</h3>
      <pretty-button v-if="!createTypeModalShown" :text="'Add a type'" :icon="'plus'" @click="showTypeModal" />
    </div>

    <!-- modal to create a type -->
    <form v-if="createTypeModalShown" class="bg-form mb-6 rounded-lg border border-gray-200" @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5">
        <errors :errors="form.errors" />

        <text-input
          :ref="'newtype'"
          v-model="form.label"
          :label="'Name'"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          :div-outer-class="'mb-4'"
          @esc-key-pressed="createTypeModalShown = false" />
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="createTypeModalShown = false" />
        <pretty-button :text="'Create date type'" :state="loadingState" :icon="'plus'" :classes="'save'" />
      </div>
    </form>

    <!-- list of important date types -->
    <ul v-if="localTypes.length > 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <li v-for="type in localTypes" :key="type.id" class="item-list border-b border-gray-200 hover:bg-slate-50">
        <!-- detail of the type -->
        <div v-if="editTypeModalShownId != type.id" class="flex items-center justify-between px-5 py-2">
          <span class="text-base">
            {{ type.label }}
            <span
              v-if="type.internal_type"
              class="mr-2 inline-block rounded bg-neutral-200 py-1 px-2 text-xs font-semibold text-neutral-500 last:mr-0"
              >{{ type.internal_type }}</span
            >
          </span>

          <!-- actions -->
          <ul class="text-sm">
            <li class="inline cursor-pointer text-blue-500 hover:underline" @click="edit(type)">Edit</li>
            <li
              v-if="type.can_be_deleted"
              class="ml-4 inline cursor-pointer text-red-500 hover:text-red-900"
              @click="destroy(type)">
              Delete
            </li>
          </ul>
        </div>

        <!-- edit a type modal -->
        <form
          v-if="editTypeModalShownId == type.id"
          class="item-list bg-form border-b border-gray-200 hover:bg-slate-50"
          @submit.prevent="update(type)">
          <div class="border-b border-gray-200 p-5">
            <errors :errors="form.errors" />

            <text-input
              :ref="'rename' + type.id"
              v-model="form.label"
              :label="'Name'"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              :div-outer-class="'mb-4'"
              @esc-key-pressed="editTypeModalShownId = 0" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="'Cancel'" :classes="'mr-3'" @click.prevent="editTypeModalShownId = 0" />
            <pretty-button :text="'Rename'" :state="loadingState" :icon="'check'" :classes="'save'" />
          </div>
        </form>
      </li>
    </ul>

    <!-- blank state -->
    <div v-if="localTypes.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">
        Date types are essential as they let you categorize dates that you add to a contact.
      </p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Errors from '@/Shared/Form/Errors';

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

      this.$nextTick(() => {
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
          this.flash('The type has been created', 'success');
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
          this.flash('The type has been updated', 'success');
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
      if (
        confirm("Are you sure? This will remove the types from all contacts, but won't delete the contacts themselves.")
      ) {
        axios
          .delete(type.url.destroy)
          .then((response) => {
            this.flash('The type has been deleted', 'success');
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
