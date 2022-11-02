<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('app.breadcrumb_location') }}
            </li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings" class="text-blue-500 hover:underline">
                {{ $t('app.breadcrumb_settings') }}
              </inertia-link>
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
              <inertia-link :href="data.url.personalize" class="text-blue-500 hover:underline">
                {{ $t('app.breadcrumb_settings_personalize') }}
              </inertia-link>
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
            <li class="inline">{{ $t('app.breadcrumb_settings_religions') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0">
            <span class="mr-1"> ⛪ </span>
            {{ $t('settings.religion_title') }}
          </h3>
          <pretty-button
            v-if="!createReligionModalShown"
            :text="$t('settings.religion_cta')"
            :icon="'plus'"
            @click="showReligionModal" />
        </div>

        <!-- modal to create a religion -->
        <form
          v-if="createReligionModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              :ref="'newReligion'"
              v-model="form.name"
              :label="$t('settings.religion_name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createReligionModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="createReligionModalShown = false" />
            <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>

        <!-- list of religions -->
        <div
          v-if="localReligions.length > 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <draggable
            :list="localReligions"
            item-key="id"
            :component-data="{ name: 'fade' }"
            handle=".handle"
            @change="updatePosition">
            <template #item="{ element }">
              <div
                v-if="editReligionId != element.id"
                class="item-list flex items-center justify-between border-b border-gray-200 py-2 pl-4 pr-5 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
                <!-- icon to move position -->
                <div class="mr-2 flex">
                  <svg
                    class="handle mr-2 cursor-move"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 7H9V9H7V7Z" fill="currentColor" />
                    <path d="M11 7H13V9H11V7Z" fill="currentColor" />
                    <path d="M17 7H15V9H17V7Z" fill="currentColor" />
                    <path d="M7 11H9V13H7V11Z" fill="currentColor" />
                    <path d="M13 11H11V13H13V11Z" fill="currentColor" />
                    <path d="M15 11H17V13H15V11Z" fill="currentColor" />
                    <path d="M9 15H7V17H9V15Z" fill="currentColor" />
                    <path d="M11 15H13V17H11V15Z" fill="currentColor" />
                    <path d="M17 15H15V17H17V15Z" fill="currentColor" />
                  </svg>

                  <span>{{ element.name }}</span>
                </div>

                <!-- actions -->
                <ul class="text-sm">
                  <li class="inline cursor-pointer text-blue-500 hover:underline" @click="renameReligionModal(element)">
                    {{ $t('app.rename') }}
                  </li>
                  <li class="ml-4 inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(element)">
                    {{ $t('app.delete') }}
                  </li>
                </ul>
              </div>

              <form
                v-else
                class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800"
                @submit.prevent="update(element)">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                  <errors :errors="form.errors" />

                  <text-input
                    :ref="'rename' + element.id"
                    v-model="form.name"
                    :label="$t('settings.religion_name')"
                    :type="'text'"
                    :autofocus="true"
                    :input-class="'block w-full'"
                    :required="true"
                    :autocomplete="false"
                    :maxlength="255"
                    @esc-key-pressed="editReligionId = 0" />
                </div>

                <div class="flex justify-between p-5">
                  <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click.prevent="editReligionId = 0" />
                  <pretty-button :text="$t('app.rename')" :state="loadingState" :icon="'check'" :classes="'save'" />
                </div>
              </form>
            </template>
          </draggable>
        </div>

        <!-- blank state -->
        <div
          v-if="localReligions.length == 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">{{ $t('settings.religion_blank') }}</p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import draggable from 'vuedraggable';

export default {
  components: {
    Layout,
    PrettyButton,
    PrettySpan,
    TextInput,
    Errors,
    draggable,
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
      createReligionModalShown: false,
      editReligionId: 0,
      localReligions: [],
      form: {
        name: '',
        position: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localReligions = this.data.religions;
  },

  methods: {
    showReligionModal() {
      this.form.name = '';
      this.form.position = '';
      this.createReligionModalShown = true;

      this.$nextTick(() => {
        this.$refs.newReligion.focus();
      });
    },

    renameReligionModal(religion) {
      this.form.name = religion.name;
      this.editReligionId = religion.id;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('settings.religion_new_success'), 'success');
          this.localReligions.unshift(response.data.data);
          this.loadingState = null;
          this.createReligionModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(religion) {
      this.loadingState = 'loading';

      axios
        .put(religion.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('settings.religion_update_success'), 'success');
          this.localReligions[this.localReligions.findIndex((x) => x.id === religion.id)] = response.data.data;
          this.loadingState = null;
          this.editReligionId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(religion) {
      if (confirm(this.$t('settings.religion_delete_warning'))) {
        axios
          .delete(religion.url.destroy)
          .then(() => {
            this.flash(this.$t('settings.religion_delete_success'), 'success');
            var id = this.localReligions.findIndex((x) => x.id === religion.id);
            this.localReligions.splice(id, 1);
          })
          .catch((error) => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },

    updatePosition(event) {
      // the event object comes from the draggable component
      this.form.position = event.moved.newIndex + 1;

      axios
        .post(event.moved.element.url.position, this.form)
        .then(() => {
          this.flash(this.$t('settings.personalize_template_show_module_order_success'), 'success');
        })
        .catch((error) => {
          this.loadingState = null;
          this.errors = error.response.data;
        });
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
