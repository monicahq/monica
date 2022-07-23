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

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative mr-1">
          <span class="relative mr-1">
            <svg
              class="icon-sidebar relative inline h-4 w-4"
              style="width: 1em; height: 1em; vertical-align: middle; fill: currentColor; overflow: hidden"
              viewBox="0 0 1024 1024"
              version="1.1"
              xmlns="http://www.w3.org/2000/svg">
              <path
                d="M927.3 453c-21.1-55.3-51.7-109.1-88.4-155.5-41.1-51.9-88.3-93.2-140.1-122.6-58.6-33.2-121.3-50.1-186.4-50.1-65.9 0-129.2 16.7-188.1 49.7-52.2 29.2-99.5 70.3-140.7 122-36.7 46.1-67.3 99.7-88.3 154.9-20.3 53.1-31 105.4-31 151.1 0 41.7 9.2 76.8 26.5 101.5 19.6 28 49.4 42.8 86.1 42.8 27.4 0 51-6.6 69.7-18.6 24.1 51.8 75.7 85 132.9 85 17.8 0 34.9-3.2 50.7-9.1v14.1c0 44.1 35.9 80 80 80s80-35.9 80-80v-14.8c16.3 6.3 33.9 9.8 52.3 9.8 57.5 0 109.1-33.6 133-86 18.9 12.6 43 19.5 71.1 19.5 36.4 0 66-14.6 85.4-42.2 17.2-24.4 26.2-59.1 26.2-100.3 0.2-45.5-10.6-97.9-30.9-151.2zM560.2 818.4c0 27.6-22.4 50-50 50s-50-22.4-50-50V789c13.8-9.2 26-20.7 36-33.9v45.8c0 8.3 6.7 15 15 15s15-6.7 15-15v-45.7c9.5 12.6 21 23.6 34 32.5v30.7z m-49.1-167.6h-0.2l-73-40.8 20.8-29.8h104.4l20.8 29.8-72.8 40.8z m396.5 36.6c-13.8 19.6-34.2 29.5-60.9 29.5-26.1 0-47.8-7.2-62.9-20.9-15.7-14.2-23.6-34.7-23.6-60.8 0-57.3-0.4-130.2-0.7-183.5-0.2-34.3-0.4-61.4-0.4-75 0-8.3-6.7-15-15-15s-15 6.7-15 15c0 13.7 0.2 40.8 0.4 75.1 0.3 53.2 0.7 126.2 0.7 183.3 0 28.5 8.1 52.8 22.7 71.4-1.2 1.3-2.2 2.8-2.9 4.5-18.1 43.9-60.3 72.3-107.4 72.3-60.6 0-110.5-47.1-115.9-107l74.1-41.5c6.8-3.8 11.7-10.4 13.5-18 1.7-7.6 0.2-15.6-4.3-22L587 562c-5.2-7.4-13.7-11.8-22.7-11.8H457.6c-9.1 0-17.6 4.4-22.7 11.8l-23 32.9c-4.5 6.4-6 14.4-4.3 22 1.7 7.6 6.6 14.2 13.4 18l74.7 41.9c-5.5 59.7-55.5 106.6-116.2 106.6-47 0-89.2-28.2-107.5-71.8-0.6-1.4-1.4-2.7-2.3-3.8 15.3-18.7 23.7-43.4 23.7-72.6 0-57.2 0.4-130.1 0.7-183.3 0.2-34.3 0.4-61.5 0.4-75.1 0-8.3-6.7-15-15-15s-15 6.7-15 15c0 13.6-0.2 40.7-0.4 75-0.3 53.3-0.7 126.2-0.7 183.5 0 26.1-8 46.6-23.6 60.8-15.1 13.7-36.8 20.9-62.9 20.9-26.9 0-47.6-10.1-61.5-30-13.8-19.6-21-48.8-21-84.3 0-42.2 10-90.7 29-140.5 20-52.4 48.9-103.1 83.8-146.9 38.7-48.7 83.1-87.2 131.9-114.5 54.4-30.5 112.7-45.9 173.4-45.9 59.9 0 117.6 15.5 171.6 46.2 48.5 27.5 92.7 66.2 131.4 115.1 68.7 86.8 113 199.9 113 288.2-0.1 35-7.3 63.7-20.9 83z" />
              <path d="M383.2 480.9m-25 0a25 25 0 1 0 50 0 25 25 0 1 0-50 0Z" />
              <path d="M639.2 480.9m-25 0a25 25 0 1 0 50 0 25 25 0 1 0-50 0Z" />
            </svg>
          </span>
        </span>

        <span class="font-semibold">Pets</span>
      </div>
      <pretty-button :text="'Add a pet'" :icon="'plus'" :classes="'sm:w-fit w-full'" @click="showCreatePetModal" />
    </div>

    <!-- add a pet modal -->
    <form v-if="addPetModalShown" class="bg-form mb-6 rounded-lg border border-gray-200" @submit.prevent="submit()">
      <div class="border-b border-gray-200">
        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <!-- name -->
        <div class="border-b border-gray-200 p-5">
          <text-input
            :ref="'newName'"
            v-model="form.name"
            :label="'Name of the pet'"
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
            v-model="form.pet_category_id"
            :data="data.pet_categories"
            :required="true"
            :placeholder="$t('app.choose_value')"
            :dropdown-class="'block w-full'"
            :label="'Pet category'" />
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="addPetModalShown = false" />
        <pretty-button :text="'Add pet'" :state="loadingState" :icon="'plus'" :classes="'save'" />
      </div>
    </form>

    <!-- pets -->
    <div v-if="localPets.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white">
        <li v-for="pet in localPets" :key="pet.id" class="item-list border-b border-gray-200 hover:bg-slate-50">
          <!-- pet -->
          <div v-if="editedPetId != pet.id" class="flex items-center justify-between px-3 py-2">
            <div class="flex items-center">
              <span class="mr-2 text-sm text-gray-500">{{ pet.pet_category.name }}</span>
              <span class="mr-2">{{ pet.name }}</span>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li class="mr-4 inline cursor-pointer text-blue-500 hover:underline" @click="showEditPetModal(pet)">
                Edit
              </li>
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(pet)">
                {{ $t('app.delete') }}
              </li>
            </ul>
          </div>

          <!-- edit pet modal -->
          <form v-if="editedPetId == pet.id" class="bg-form" @submit.prevent="update(pet)">
            <div class="border-b border-gray-200">
              <div v-if="form.errors.length > 0" class="p-5">
                <errors :errors="form.errors" />
              </div>

              <!-- name -->
              <div class="border-b border-gray-200 p-5">
                <text-input
                  :ref="'label'"
                  v-model="form.name"
                  :label="'Name of the pet'"
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
                  v-model="form.pet_category_id"
                  :data="data.pet_categories"
                  :required="true"
                  :placeholder="$t('app.choose_value')"
                  :dropdown-class="'block w-full'"
                  :label="'Pet category'" />
              </div>
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="editedPetId = 0" />
              <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'check'" :classes="'save'" />
            </div>
          </form>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div v-if="localPets.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">There are no pets yet.</p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Dropdown from '@/Shared/Form/Dropdown';
import Errors from '@/Shared/Form/Errors';

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

      this.$nextTick(() => {
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
          this.flash('The pet has been added', 'success');
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
          this.flash('The pet has been edited', 'success');
          this.localPets[this.localPets.findIndex((x) => x.id === pet.id)] = response.data.data;
          this.editedPetId = 0;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    destroy(pet) {
      if (confirm('Are you sure? This will delete the pet permanently.')) {
        axios
          .delete(pet.url.destroy)
          .then((response) => {
            this.flash('The pet has been deleted', 'success');
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
