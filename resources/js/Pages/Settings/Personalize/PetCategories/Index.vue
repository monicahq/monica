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
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600">{{ $t('app.breadcrumb_location') }}</li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings" class="text-blue-500 hover:underline">{{
                $t('app.breadcrumb_settings')
              }}</inertia-link>
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
              <inertia-link :href="data.url.personalize" class="text-blue-500 hover:underline"
                >Personalize your account</inertia-link
              >
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
            <li class="inline">Pet categories</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0"><span class="mr-1"> 🐱 </span> All the pet categories</h3>
          <pretty-button
            v-if="!createPetCategoryModalShown"
            :text="'Add a pet category'"
            :icon="'plus'"
            @click="showPetCategoryModal" />
        </div>

        <!-- modal to create a new pet category -->
        <form
          v-if="createPetCategoryModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200 p-5">
            <errors :errors="form.errors" />

            <text-input
              :ref="'newPetCategory'"
              v-model="form.name"
              :label="'Name'"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createPetCategoryModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="createPetCategoryModalShown = false" />
            <pretty-button :text="'Create pet category'" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>

        <!-- list of groups types -->
        <ul v-if="localPetCategories.length > 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
          <li
            v-for="petCategory in localPetCategories"
            :key="petCategory.id"
            class="item-list border-b border-gray-200 hover:bg-slate-50">
            <!-- detail of the pet category -->
            <div
              v-if="renamePetCategoryModalShownId != petCategory.id"
              class="flex items-center justify-between px-5 py-2">
              <span class="text-base">{{ petCategory.name }}</span>

              <!-- actions -->
              <ul class="text-sm">
                <li
                  class="mr-4 inline cursor-pointer text-blue-500 hover:underline"
                  @click="updatePetCategoryModal(petCategory)">
                  Rename
                </li>
                <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(petCategory)">
                  Delete
                </li>
              </ul>
            </div>

            <!-- rename a petCategory modal -->
            <form
              v-if="renamePetCategoryModalShownId == petCategory.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50"
              @submit.prevent="update(petCategory)">
              <div class="border-b border-gray-200 p-5">
                <errors :errors="form.errors" />

                <text-input
                  :ref="'rename' + petCategory.id"
                  v-model="form.name"
                  :label="'Name'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="renamePetCategoryModalShownId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span
                  :text="$t('app.cancel')"
                  :classes="'mr-3'"
                  @click.prevent="renamePetCategoryModalShownId = 0" />
                <pretty-button :text="'Rename'" :state="loadingState" :icon="'check'" :classes="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div v-if="localPetCategories.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
          <p class="p-5 text-center">
            Pet categories let you add types of pets that contacts can add to their profile.
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    Layout,
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
      createPetCategoryModalShown: false,
      renamePetCategoryModalShownId: 0,
      localPetCategories: [],
      form: {
        name: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localPetCategories = this.data.pet_categories;
  },

  methods: {
    showPetCategoryModal() {
      this.form.name = '';
      this.createPetCategoryModalShown = true;

      this.$nextTick(() => {
        this.$refs.newPetCategory.focus();
      });
    },

    updatePetCategoryModal(petCategory) {
      this.form.name = petCategory.name;
      this.renamePetCategoryModalShownId = petCategory.id;

      this.$nextTick(() => {
        this.$refs[`rename${petCategory.id}`].focus();
      });
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.pet_category_store, this.form)
        .then((response) => {
          this.flash('The pet category has been created', 'success');
          this.localPetCategories.unshift(response.data.data);
          this.loadingState = null;
          this.createPetCategoryModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(petCategory) {
      this.loadingState = 'loading';

      axios
        .put(petCategory.url.update, this.form)
        .then((response) => {
          this.flash('The pet category has been updated', 'success');
          this.localPetCategories[this.localPetCategories.findIndex((x) => x.id === petCategory.id)] =
            response.data.data;
          this.loadingState = null;
          this.renamePetCategoryModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(petCategory) {
      if (
        confirm(
          "Are you sure? This will remove the pets from the contacts who have them, but won't delete the contacts themselves.",
        )
      ) {
        axios
          .delete(petCategory.url.destroy)
          .then((response) => {
            this.flash('The pet category has been deleted', 'success');
            var id = this.localPetCategories.findIndex((x) => x.id === petCategory.id);
            this.localPetCategories.splice(id, 1);
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
