<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <InertiaLink :href="data.url.settings" class="text-blue-500 hover:underline">
                {{ $t('Settings') }}
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
              <InertiaLink :href="data.url.personalize" class="text-blue-500 hover:underline">
                {{ $t('Personalize your account') }}
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
            <li class="inline">{{ $t('Pet categories') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0">
            <span class="me-1"> 🐱 </span>
            {{ $t('All the pet categories') }}
          </h3>
          <pretty-button
            v-if="!createPetCategoryModalShown"
            :text="$t('Add')"
            :icon="'plus'"
            @click="showPetCategoryModal" />
        </div>

        <!-- modal to create a new pet category -->
        <form
          v-if="createPetCategoryModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              ref="newPetCategory"
              v-model="form.name"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createPetCategoryModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createPetCategoryModalShown = false" />
            <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
          </div>
        </form>

        <!-- list of groups types -->
        <ul
          v-if="localPetCategories.length > 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <li
            v-for="petCategory in localPetCategories"
            :key="petCategory.id"
            class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
            <!-- detail of the pet category -->
            <div
              v-if="renamePetCategoryModalShownId !== petCategory.id"
              class="flex items-center justify-between px-5 py-2">
              <span class="text-base">{{ petCategory.name }}</span>

              <!-- actions -->
              <ul class="text-sm">
                <li class="me-4 inline cursor-pointer" @click="updatePetCategoryModal(petCategory)">
                  <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                </li>
                <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(petCategory)">
                  {{ $t('Delete') }}
                </li>
              </ul>
            </div>

            <!-- rename a petCategory modal -->
            <form
              v-else
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
              @submit.prevent="update(petCategory)">
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <errors :errors="form.errors" />

                <text-input
                  ref="rename"
                  v-model="form.name"
                  :label="$t('Name')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="renamePetCategoryModalShownId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="renamePetCategoryModalShownId = 0" />
                <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div
          v-if="localPetCategories.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{ $t('Pet categories let you add types of pets that contacts can add to their profile.') }}
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';

export default {
  components: {
    InertiaLink: Link,
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
      this.renamePetCategoryModalShownId = 0;

      this.$nextTick().then(() => {
        this.$refs.newPetCategory.focus();
      });
    },

    updatePetCategoryModal(petCategory) {
      this.form.name = petCategory.name;
      this.renamePetCategoryModalShownId = petCategory.id;
      this.createPetCategoryModalShown = false;

      this.$nextTick().then(() => {
        this.$refs.rename[0].focus();
      });
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.pet_category_store, this.form)
        .then((response) => {
          this.flash(this.$t('The pet category has been created'), 'success');
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
          this.flash(this.$t('The pet category has been updated'), 'success');
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
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(petCategory.url.destroy)
          .then(() => {
            this.flash(this.$t('The pet category has been deleted'), 'success');
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
