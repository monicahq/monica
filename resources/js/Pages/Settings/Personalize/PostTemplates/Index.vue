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
            <li class="inline">{{ $t('app.breadcrumb_settings_personalize_post_templates') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0">
            <span class="mr-1"> 📮 </span>
            {{ $t('settings.personalize_post_templates_title') }}
          </h3>
          <pretty-button
            v-if="!createPostTemplateModalShown"
            :text="$t('settings.personalize_post_templates_cta')"
            :icon="'plus'"
            @click="showCreatePostTemplateModal" />
        </div>

        <!-- help text -->
        <div class="mb-6 flex rounded border bg-slate-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-slate-900">
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
            <p>{{ $t('settings.personalize_post_templates_help') }}</p>
          </div>
        </div>

        <!-- modal to create a post template -->
        <form
          v-if="createPostTemplateModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              :ref="'newPostTemplate'"
              v-model="form.label"
              :label="'Name'"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createPostTemplateModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="createPostTemplateModalShown = false" />
            <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>

        <!-- list of post templates -->
        <div v-if="localPostTemplates.length > 0" class="mb-6">
          <draggable
            :list="localPostTemplates"
            item-key="id"
            :component-data="{ name: 'fade' }"
            handle=".handle"
            @change="updatePosition">
            <template #item="{ element }">
              <div v-if="editPostTemplateId != element.id" class="">
                <div
                  class="item-list mb-2 rounded-lg border border-gray-200 bg-white py-2 pl-4 pr-5 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 hover:dark:bg-slate-800">
                  <div class="mb-3 flex items-center justify-between">
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

                      <span>{{ element.label }}</span>
                    </div>

                    <!-- actions -->
                    <ul class="text-sm">
                      <li
                        class="inline cursor-pointer text-blue-500 hover:underline"
                        @click="renamePostTemplateModal(element)">
                        {{ $t('app.rename') }}
                      </li>
                      <li
                        v-if="element.can_be_deleted"
                        class="ml-4 inline cursor-pointer text-red-500 hover:text-red-900"
                        @click="destroy(element)">
                        {{ $t('app.delete') }}
                      </li>
                    </ul>
                  </div>

                  <!-- available sections -->
                  <div class="ml-8">
                    <p class="mb-1 text-sm text-gray-500">Sections:</p>

                    <draggable
                      :list="element.post_template_sections"
                      item-key="id"
                      :component-data="{ name: 'fade' }"
                      handle=".handle"
                      @change="updatePosition">
                      <template #item="{ element, id }">
                        <div v-if="editSectionId != element.id" class="">
                          <div
                            class="item-list mb-2 rounded-lg border border-gray-200 bg-white py-2 pl-4 pr-5 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 hover:dark:bg-slate-800">
                            <div class="flex items-center justify-between">
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

                                <span>{{ element.label }}</span>
                              </div>

                              <!-- actions -->
                              <ul class="text-sm">
                                <li
                                  class="inline cursor-pointer text-blue-500 hover:underline"
                                  @click="renameSectionModal(id, element)">
                                  {{ $t('app.rename') }}
                                </li>
                                <li
                                  v-if="element.can_be_deleted"
                                  class="ml-4 inline cursor-pointer text-red-500 hover:text-red-900"
                                  @click="destroySection(element)">
                                  {{ $t('app.delete') }}
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>

                        <!-- edit a section form -->
                        <form
                          v-else
                          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                          @submit.prevent="updateSection(element)">
                          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                            <errors :errors="form.errors" />

                            <text-input
                              :ref="'newSection'"
                              v-model="form.label"
                              :label="'Name'"
                              :type="'text'"
                              :autofocus="true"
                              :input-class="'block w-full'"
                              :required="true"
                              :autocomplete="false"
                              :maxlength="255"
                              @esc-key-pressed="postTemplateId = 0" />
                          </div>

                          <div class="flex justify-between p-5">
                            <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="postTemplateId = 0" />
                            <pretty-button
                              :text="$t('app.rename')"
                              :state="loadingState"
                              :icon="'check'"
                              :classes="'save'" />
                          </div>
                        </form>
                      </template>
                    </draggable>

                    <!-- add a section -->
                    <span
                      v-if="
                        element.post_template_sections.length != 0 &&
                        !createSectionModalShown &&
                        postTemplateId != element.id
                      "
                      class="inline cursor-pointer text-sm text-blue-500 hover:underline"
                      @click="showCreateSectionModal(element)"
                      >add a section</span
                    >

                    <!-- form: create new section -->
                    <form
                      v-if="createSectionModalShown && postTemplateId == element.id"
                      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                      @submit.prevent="submitSection(element)">
                      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <errors :errors="form.errors" />

                        <text-input
                          :ref="'newSection'"
                          v-model="form.label"
                          :label="'Name'"
                          :type="'text'"
                          :autofocus="true"
                          :input-class="'block w-full'"
                          :required="true"
                          :autocomplete="false"
                          :maxlength="255"
                          @esc-key-pressed="createSectionModalShown = false" />
                      </div>

                      <div class="flex justify-between p-5">
                        <pretty-span
                          :text="$t('app.cancel')"
                          :classes="'mr-3'"
                          @click="createSectionModalShown = false" />
                        <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
                      </div>
                    </form>

                    <!-- blank state -->
                    <div
                      v-if="
                        element.post_template_sections.length == 0 &&
                        !createSectionModalShown &&
                        postTemplateId != element.id
                      "
                      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                      <p class="p-5 text-center">
                        No roles yet.
                        <span
                          class="block cursor-pointer text-sm text-blue-500 hover:underline"
                          @click="showCreateSectionModal(element)"
                          >add a section</span
                        >
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <form
                v-else
                class="item-list mb-2 rounded-lg border border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800"
                @submit.prevent="update(element)">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                  <errors :errors="form.errors" />

                  <text-input
                    :ref="'rename' + element.id"
                    v-model="form.label"
                    :label="'Name'"
                    :type="'text'"
                    :autofocus="true"
                    :input-class="'block w-full'"
                    :required="true"
                    :autocomplete="false"
                    :maxlength="255"
                    @esc-key-pressed="editPostTemplateId = 0" />
                </div>

                <div class="flex justify-between p-5">
                  <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click.prevent="editPostTemplateId = 0" />
                  <pretty-button :text="$t('app.rename')" :state="loadingState" :icon="'check'" :classes="'save'" />
                </div>
              </form>
            </template>
          </draggable>
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
      createPostTemplateModalShown: false,
      createSectionModalShown: false,
      postTemplateId: 0,
      editPostTemplateId: 0,
      editSectionId: 0,
      localPostTemplates: [],
      form: {
        label: '',
        position: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localPostTemplates = this.data.post_templates;
  },

  methods: {
    showCreatePostTemplateModal() {
      this.form.label = '';
      this.form.position = '';
      this.createPostTemplateModalShown = true;

      this.$nextTick(() => {
        this.$refs.newPostTemplate.focus();
      });
    },

    showCreateSectionModal(postTemplate) {
      this.form.label = '';
      this.form.position = '';
      this.createSectionModalShown = true;
      this.postTemplateId = postTemplate.id;

      this.$nextTick(() => {
        this.$refs.newSection.focus();
      });
    },

    renamePostTemplateModal(postTemplate) {
      this.form.label = postTemplate.label;
      this.editPostTemplateId = postTemplate.id;
    },

    renameSectionModal(postTemplate, section) {
      this.form.label = section.label;
      this.editPostTemplateId = postTemplate;
      this.editSectionId = section.id;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash('The post template has been created', 'success');
          this.localPostTemplates.push(response.data.data);
          this.loadingState = null;
          this.createPostTemplateModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(postTemplate) {
      this.loadingState = 'loading';

      axios
        .put(postTemplate.url.update, this.form)
        .then((response) => {
          this.flash('The post template has been updated', 'success');
          this.localPostTemplates[this.localPostTemplates.findIndex((x) => x.id === postTemplate.id)] =
            response.data.data;
          this.loadingState = null;
          this.editPostTemplateId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(postTemplate) {
      if (confirm('Are you sure? This can not be undone.')) {
        axios
          .delete(postTemplate.url.destroy)
          .then(() => {
            this.flash('The post template has been deleted', 'success');
            var id = this.localPostTemplates.findIndex((x) => x.id === postTemplate.id);
            this.localPostTemplates.splice(id, 1);
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
          this.flash('The order has been saved', 'success');
        })
        .catch((error) => {
          this.loadingState = null;
          this.errors = error.response.data;
        });
    },

    submitSection(postTemplate) {
      this.loadingState = 'loading';

      axios
        .post(postTemplate.url.store, this.form)
        .then((response) => {
          this.flash('The section has been created', 'success');
          var id = this.localPostTemplates.findIndex((x) => x.id === postTemplate.id);
          this.localPostTemplates[id].post_template_sections.push(response.data.data);
          this.loadingState = null;
          this.postTemplateId = 0;
          this.createSectionModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    updateSection(section) {
      this.loadingState = 'loading';

      axios
        .put(section.url.update, this.form)
        .then((response) => {
          this.flash('The section has been updated', 'success');

          var postTemplateId = this.localPostTemplates.findIndex((x) => x.id === section.post_template_id);
          var sectionId = this.localPostTemplates[postTemplateId].post_template_sections.findIndex(
            (x) => x.id === section.id,
          );
          this.localPostTemplates[postTemplateId].post_template_sections[sectionId] = response.data.data;

          this.loadingState = null;
          this.postTemplateId = 0;
          this.editSectionId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroySection(section) {
      if (confirm('Are you sure? This can not be undone.')) {
        axios
          .delete(section.url.destroy)
          .then(() => {
            this.flash('The section has been deleted', 'success');

            var postTemplateId = this.localPostTemplates.findIndex((x) => x.id === section.post_template_id);
            var sectionId = this.localPostTemplates[postTemplateId].post_template_sections.findIndex(
              (x) => x.id === section.id,
            );
            this.localPostTemplates[postTemplateId].post_template_sections.splice(sectionId, 1);
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

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
