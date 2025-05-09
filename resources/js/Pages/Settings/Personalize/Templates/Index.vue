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
            <li class="inline">
              {{ $t('Templates') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0">
            <span class="me-1"> 📐 </span>
            {{ $t('All the templates') }}
          </h3>
          <pretty-button
            v-if="!createTemplateModalShown"
            :text="$t('Add a template')"
            :icon="'plus'"
            @click="showTemplateModal" />
        </div>

        <!-- help text -->
        <div class="mb-6 flex rounded-xs border bg-slate-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-slate-900">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 grow pe-2"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>

          <div>
            <p class="mb-2">
              {{
                $t(
                  'Templates let you customize what data should be displayed on your contacts. You can define as many templates as you want, and choose which template should be used on which contact.',
                )
              }}
            </p>
            <p>
              {{
                $t(
                  'You need at least one template for contacts to be displayed. Without a template, Monica won’t know which information it should display.',
                )
              }}
            </p>
          </div>
        </div>

        <!-- modal to create a new template -->
        <form
          v-if="createTemplateModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              ref="newTemplate"
              v-model="form.name"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createTemplateModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createTemplateModalShown = false" />
            <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
          </div>
        </form>

        <!-- list of templates -->
        <ul
          v-if="localTemplates.length > 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <li
            v-for="template in localTemplates"
            :key="template.id"
            class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
            <!-- detail of the template -->
            <div v-if="renameTemplateModalShownId !== template.id" class="flex items-center justify-between px-5 py-2">
              <InertiaLink :href="template.url.show" class="text-blue-500 hover:underline">
                {{ template.name }}
              </InertiaLink>

              <!-- actions -->
              <ul class="text-sm">
                <li class="me-4 inline cursor-pointer" @click="showUpdateTemplateModal(template)">
                  <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                </li>
                <li
                  v-if="template.can_be_deleted"
                  class="inline cursor-pointer text-red-500 hover:text-red-900"
                  @click="destroy(template)">
                  {{ $t('Delete') }}
                </li>
              </ul>
            </div>

            <!-- rename a template modal -->
            <form
              v-if="renameTemplateModalShownId === template.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
              @submit.prevent="update(template)">
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
                  @esc-key-pressed="renameTemplateModalShownId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="renameTemplateModalShownId = 0" />
                <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div
          v-if="localTemplates.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{ $t('Create at least one template to use Monica.') }}
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
      createTemplateModalShown: false,
      renameTemplateModalShownId: 0,
      localTemplates: [],
      form: {
        name: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localTemplates = this.data.templates;
  },

  methods: {
    showTemplateModal() {
      this.form.name = '';
      this.createTemplateModalShown = true;
      this.renameTemplateModalShownId = 0;

      this.$nextTick().then(() => {
        this.$refs.newTemplate.focus();
      });
    },

    showUpdateTemplateModal(template) {
      this.form.name = template.name;
      this.renameTemplateModalShownId = template.id;
      this.createTemplateModalShown = false;

      this.$nextTick().then(() => {
        this.$refs.rename[0].focus();
      });
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.template_store, this.form)
        .then((response) => {
          this.flash(this.$t('The template has been created'), 'success');
          this.localTemplates.unshift(response.data.data);
          this.loadingState = null;
          this.createTemplateModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(template) {
      this.loadingState = 'loading';

      axios
        .put(template.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The template has been updated'), 'success');
          this.localTemplates[this.localTemplates.findIndex((x) => x.id === template.id)] = response.data.data;
          this.loadingState = null;
          this.renameTemplateModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(template) {
      if (
        confirm(
          this.$t(
            'Are you sure? This will remove the template from all contacts, but won’t delete the contacts themselves.',
          ),
        )
      ) {
        axios
          .delete(template.url.destroy)
          .then(() => {
            this.flash(this.$t('The template has been deleted'), 'success');
            var id = this.localTemplates.findIndex((x) => x.id === template.id);
            this.localTemplates.splice(id, 1);
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
