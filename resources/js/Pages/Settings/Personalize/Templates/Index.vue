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
    <nav class="sm:border-b bg-white">
      <div class="max-w-8xl mx-auto px-4 sm:px-6 py-2 hidden md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="inline mr-2 text-gray-600">You are here:</li>
            <li class="inline mr-2">
              <inertia-link :href="data.url.settings" class="text-sky-500 hover:text-blue-900">Settings</inertia-link>
            </li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline mr-2"><inertia-link :href="data.url.personalize" class="text-sky-500 hover:text-blue-900">Personalize your account</inertia-link></li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Templates</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-20 relative">
      <div class="max-w-3xl mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="sm:flex items-center justify-between mb-6 sm:mt-0 mt-8">
          <h3 class="mb-4 sm:mb-0">
            <span class="mr-1">
              📐
            </span> All the templates
          </h3>
          <pretty-button v-if="!createTemplateModalShown" :text="'Add a new template'" :icon="'plus'" @click="showTemplateModal" />
        </div>

        <!-- help text -->
        <div class="px-3 py-2 border mb-6 flex rounded text-sm bg-slate-50">
          <svg xmlns="http://www.w3.org/2000/svg" class="grow h-6 pr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>

          <div>
            <p class="mb-2">Templates let you customize what data should be displayed on your contacts. You can define as many templates as you want, and choose which template should be used on which contact.</p>
            <p>You need at least one template for contacts to be displayed. Without a template, Monica won't know which information it should display.</p>
          </div>
        </div>

        <!-- modal to create a new template -->
        <form v-if="createTemplateModalShown" class="bg-white border border-gray-200 rounded-lg mb-6" @submit.prevent="submit()">
          <div class="p-5 border-b border-gray-200">
            <errors :errors="form.errors" />

            <text-input :ref="'newTemplate'"
                        v-model="form.name"
                        :label="'Name of the new template'" :type="'text'"
                        :autofocus="true"
                        :input-class="'block w-full'"
                        :required="true"
                        :autocomplete="false"
                        :maxlength="255"
                        @esc-key-pressed="createTemplateModalShown = false"
            />
          </div>

          <div class="p-5 flex justify-between">
            <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="createTemplateModalShown = false" />
            <pretty-button :text="'Create template'" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>

        <!-- list of templates -->
        <ul v-if="localTemplates.length > 0" class="bg-white border border-gray-200 rounded-lg mb-6">
          <li v-for="template in localTemplates" :key="template.id" class="border-b border-gray-200 hover:bg-slate-50 item-list">
            <!-- detail of the template -->
            <div v-if="renameTemplateModalShownId != template.id" class="flex justify-between items-center px-5 py-2">
              <inertia-link :href="template.url.show" class="text-sky-500 hover:text-blue-900">{{ template.name }}</inertia-link>

              <!-- actions -->
              <ul class="text-sm">
                <li class="cursor-pointer inline mr-4 text-sky-500 hover:text-blue-900" @click="showUpdateTemplateModal(template)">Rename</li>
                <li class="cursor-pointer inline text-red-500 hover:text-red-900" @click="destroy(template)">Delete</li>
              </ul>
            </div>

            <!-- rename a template modal -->
            <form v-if="renameTemplateModalShownId == template.id" class="border-b border-gray-200 hover:bg-slate-50 item-list" @submit.prevent="update(template)">
              <div class="p-5 border-b border-gray-200">
                <errors :errors="form.errors" />

                <text-input :ref="'rename' + template.id"
                            v-model="form.name"
                            :label="'Name'" :type="'text'"
                            :autofocus="true"
                            :input-class="'block w-full'"
                            :required="true"
                            :autocomplete="false"
                            :maxlength="255"
                            @esc-key-pressed="renameTemplateModalShownId = 0"
                />
              </div>

              <div class="p-5 flex justify-between">
                <pretty-span :text="'Cancel'" :classes="'mr-3'" @click.prevent="renameTemplateModalShownId = 0" />
                <pretty-button :text="'Rename'" :state="loadingState" :icon="'check'" :classes="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div v-if="localTemplates.length == 0" class="bg-white border border-gray-200 rounded-lg mb-6">
          <p class="p-5 text-center">Create at least one template to use Monica.</p>
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

      axios.post(this.data.url.template_store, this.form)
        .then(response => {
          this.flash('The template has been created', 'success');
          this.localTemplates.unshift(response.data.data);
          this.loadingState = null;
          this.createTemplateModalShown = false;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(template) {
      this.loadingState = 'loading';

      axios.put(template.url.update, this.form)
        .then(response => {
          this.flash('The template has been updated', 'success');
          this.localTemplates[this.localTemplates.findIndex(x => x.id === template.id)] = response.data.data;
          this.loadingState = null;
          this.renameTemplateModalShownId = 0;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(template) {
      if(confirm('Are you sure? This will remove the templates from all contacts, but won\'t delete the contacts themselves.')) {

        axios.delete(template.url.destroy)
          .then(response => {
            this.flash('The template has been deleted', 'success');
            var id = this.localTemplates.findIndex(x => x.id === template.id);
            this.localTemplates.splice(id, 1);
          })
          .catch(error => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },
  },
};
</script>
