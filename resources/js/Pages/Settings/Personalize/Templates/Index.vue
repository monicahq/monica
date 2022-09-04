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
            <li class="inline">
              {{ $t('app.breadcrumb_settings_personalize_templates') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0">
            <span class="mr-1"> 📐 </span>
            {{ $t('settings.personalize_templates_title') }}
          </h3>
          <pretty-button
            v-if="!createTemplateModalShown"
            :text="$t('settings.personalize_templates_cta')"
            :icon="'plus'"
            @click="showTemplateModal" />
        </div>

        <!-- help text -->
        <div class="mb-6 flex rounded border bg-slate-50 px-3 py-2 text-sm dark:bg-slate-900">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 grow pr-2"
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
              {{ $t('settings.personalize_templates_help') }}
            </p>
            <p>
              {{ $t('settings.personalize_templates_help_2') }}
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
              :ref="'newTemplate'"
              v-model="form.name"
              :label="$t('settings.personalize_templates_new_name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createTemplateModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="createTemplateModalShown = false" />
            <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>

        <!-- list of templates -->
        <ul
          v-if="localTemplates.length > 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <li
            v-for="template in localTemplates"
            :key="template.id"
            class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
            <!-- detail of the template -->
            <div v-if="renameTemplateModalShownId != template.id" class="flex items-center justify-between px-5 py-2">
              <inertia-link :href="template.url.show" class="text-blue-500 hover:underline">
                {{ template.name }}
              </inertia-link>

              <!-- actions -->
              <ul class="text-sm">
                <li
                  class="mr-4 inline cursor-pointer text-blue-500 hover:underline"
                  @click="showUpdateTemplateModal(template)">
                  {{ $t('app.rename') }}
                </li>
                <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(template)">
                  {{ $t('app.delete') }}
                </li>
              </ul>
            </div>

            <!-- rename a template modal -->
            <form
              v-if="renameTemplateModalShownId == template.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800"
              @submit.prevent="update(template)">
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <errors :errors="form.errors" />

                <text-input
                  :ref="'rename' + template.id"
                  v-model="form.name"
                  :label="$t('settings.personalize_templates_edit_name')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="renameTemplateModalShownId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span
                  :text="$t('app.cancel')"
                  :classes="'mr-3'"
                  @click.prevent="renameTemplateModalShownId = 0" />
                <pretty-button :text="$t('app.rename')" :state="loadingState" :icon="'check'" :classes="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div
          v-if="localTemplates.length == 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{ $t('settings.personalize_templates_blank') }}
          </p>
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

      this.$nextTick(() => {
        this.$refs.newTemplate.focus();
      });
    },

    showUpdateTemplateModal(template) {
      this.form.name = template.name;
      this.renameTemplateModalShownId = template.id;

      this.$nextTick(() => {
        this.$refs[`rename${template.id}`].focus();
      });
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.template_store, this.form)
        .then((response) => {
          this.flash(this.$t('settings.personalize_templates_new_success'), 'success');
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
          this.flash(this.$t('settings.personalize_templates_update_success'), 'success');
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
      if (confirm(this.$t('settings.personalize_templates_destroy_confirmation'))) {
        axios
          .delete(template.url.destroy)
          .then(() => {
            this.flash(this.$t('settings.personalize_templates_destroy_success'), 'success');
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
