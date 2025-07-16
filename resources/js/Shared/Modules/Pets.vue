<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <Dog class="h-4 w-4 text-gray-600" />

        <span class="font-semibold"> {{ $t('Pets') }} </span>
      </div>
      <pretty-button :text="$t('Add a pet')" :icon="'plus'" :class="'w-full sm:w-fit'" @click="showCreatePetModal" />
    </div>

    <!-- add a pet modal -->
    <form
      v-if="addPetModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <!-- name -->
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <text-input
            ref="newName"
            v-model="form.name"
            :label="$t('Name of the pet')"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :required="false"
            :autocomplete="false"
            :maxlength="255"
            @esc-key-pressed="addPetModalShown = false" />
        </div>

        <div class="p-5">
          <!-- pet categories -->
          <dropdown
            v-model.number="form.pet_category_id"
            :data="data.pet_categories"
            :required="true"
            :placeholder="$t('Choose a value')"
            :dropdown-class="'block w-full'"
            :label="$t('Pet category')" />
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="addPetModalShown = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- pets -->
    <div v-if="localPets.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="pet in localPets"
          :key="pet.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <!-- pet -->
          <div v-if="editedPetId !== pet.id" class="flex items-center justify-between px-3 py-2">
            <div class="flex items-center">
              <span class="me-2 text-sm text-gray-500">{{ pet.pet_category.name }}</span>
              <span class="me-2">{{ pet.name }}</span>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li class="me-4 inline" @click="showEditPetModal(pet)">
                <span class="cursor-pointer text-blue-500 hover:underline">{{ $t('Edit') }}</span>
              </li>
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(pet)">
                {{ $t('Delete') }}
              </li>
            </ul>
          </div>

          <!-- edit pet modal -->
          <form v-if="editedPetId === pet.id" class="bg-gray-50 dark:bg-gray-900" @submit.prevent="update(pet)">
            <div class="border-b border-gray-200 dark:border-gray-700">
              <div v-if="form.errors.length > 0" class="p-5">
                <errors :errors="form.errors" />
              </div>

              <!-- name -->
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <text-input
                  ref="label"
                  v-model="form.name"
                  :label="$t('Name of the pet')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="addPetModalShown = false" />
              </div>

              <div class="p-5">
                <!-- pet categories -->
                <dropdown
                  v-model.number="form.pet_category_id"
                  :data="data.pet_categories"
                  :required="true"
                  :placeholder="$t('Choose a value')"
                  :dropdown-class="'block w-full'"
                  :label="$t('Pet category')" />
              </div>
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editedPetId = 0" />
              <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
            </div>
          </form>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-if="localPets.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_pet.svg" :alt="$t('Pets')" class="mx-auto mt-4 h-16 w-16" />
      <p class="px-5 pb-5 pt-2 text-center">{{ $t('There are no pets yet.') }}</p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import Errors from '@/Shared/Form/Errors.vue';
import { Dog } from 'lucide-vue-next';

export default {
  components: {
    PrettyButton,
    PrettySpan,
    TextInput,
    Dropdown,
    Errors,
    Dog,
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
      addPetModalShown: false,
      localPets: [],
      editedPetId: 0,
      form: {
        name: '',
        pet_category_id: 0,
        errors: [],
      },
    };
  },

  created() {
    this.localPets = this.data.pets;
  },

  methods: {
    showCreatePetModal() {
      this.addPetModalShown = true;
      this.form.errors = [];
      this.form.name = '';
      this.form.pet_category_id = 0;

      this.$nextTick().then(() => {
        this.$refs.newName.focus();
      });
    },

    showEditPetModal(pet) {
      this.form.errors = [];
      this.editedPetId = pet.id;
      this.form.pet_category_id = pet.pet_category.id;
      this.form.name = pet.name;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The pet has been added'), 'success');
          this.localPets.unshift(response.data.data);
          this.loadingState = '';
          this.addPetModalShown = false;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    update(pet) {
      this.loadingState = 'loading';

      axios
        .put(pet.url.update, this.form)
        .then((response) => {
          this.loadingState = '';
          this.flash(this.$t('The pet has been updated'), 'success');
          this.localPets[this.localPets.findIndex((x) => x.id === pet.id)] = response.data.data;
          this.editedPetId = 0;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    destroy(pet) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(pet.url.destroy)
          .then(() => {
            this.flash(this.$t('The pet has been deleted'), 'success');
            var id = this.localPets.findIndex((x) => x.id === pet.id);
            this.localPets.splice(id, 1);
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
</style>
