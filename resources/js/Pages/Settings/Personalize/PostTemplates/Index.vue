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
            <li class="inline">{{ $t('Post templates') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0">
            <span class="me-1"> 📮 </span>
            {{ $t('Post templates') }}
          </h3>
          <pretty-button
            v-if="!createPostTemplateModalShown"
            :text="$t('Add a post template')"
            :icon="'plus'"
            @click="showCreatePostTemplateModal" />
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
            <p>
              {{
                $t(
                  'A post template defines how the content of a post should be displayed. You can define as many templates as you want, and choose which template should be used on which post.',
                )
              }}
            </p>
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
              ref="newPostTemplate"
              v-model="form.label"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createPostTemplateModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createPostTemplateModalShown = false" />
            <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
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
              <div v-if="editPostTemplateId !== element.id">
                <div
                  class="item-list mb-2 rounded-lg border border-gray-200 bg-white py-2 pe-5 ps-4 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-slate-800">
                  <div class="mb-3 flex items-center justify-between">
                    <!-- icon to move position -->
                    <div class="me-2 flex">
                      <svg
                        class="handle me-2 cursor-move"
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
                      <li class="inline cursor-pointer" @click="renamePostTemplateModal(element)">
                        <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                      </li>
                      <li
                        v-if="element.can_be_deleted"
                        class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900"
                        @click="destroy(element)">
                        {{ $t('Delete') }}
                      </li>
                    </ul>
                  </div>

                  <!-- available sections -->
                  <div class="ms-8">
                    <p class="mb-1 text-sm text-gray-500">{{ $t('Sections:') }}</p>

                    <draggable
                      :list="element.post_template_sections"
                      item-key="id"
                      :component-data="{ name: 'fade' }"
                      handle=".handle"
                      @change="updatePosition">
                      <template #item="{ element: element2 }">
                        <div v-if="editSectionId !== element2.id">
                          <div
                            class="item-list mb-2 rounded-lg border border-gray-200 bg-white py-2 pe-5 ps-4 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-slate-800">
                            <div class="flex items-center justify-between">
                              <!-- icon to move position -->
                              <div class="me-2 flex">
                                <svg
                                  class="handle me-2 cursor-move"
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

                                <span>{{ element2.label }}</span>
                              </div>

                              <!-- actions -->
                              <ul class="text-sm">
                                <li
                                  class="inline cursor-pointer text-blue-500 hover:underline"
                                  @click="renameSectionModal(element2)">
                                  <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                                </li>
                                <li
                                  v-if="element.can_be_deleted"
                                  class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900"
                                  @click="destroySection(element2)">
                                  {{ $t('Delete') }}
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>

                        <!-- edit a section form -->
                        <form
                          v-else
                          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                          @submit.prevent="updateSection(element2)">
                          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                            <errors :errors="form.errors" />

                            <text-input
                              ref="renameSection"
                              v-model="form.label"
                              :label="$t('Name')"
                              :type="'text'"
                              :autofocus="true"
                              :input-class="'block w-full'"
                              :required="true"
                              :autocomplete="false"
                              :maxlength="255"
                              @esc-key-pressed="editSectionId = 0" />
                          </div>

                          <div class="flex justify-between p-5">
                            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editSectionId = 0" />
                            <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
                          </div>
                        </form>
                      </template>
                    </draggable>

                    <!-- add a section -->
                    <span
                      v-if="
                        element.post_template_sections.length !== 0 &&
                        !createSectionModalShown &&
                        postTemplateId !== element.id
                      "
                      class="inline cursor-pointer text-sm text-blue-500 hover:underline"
                      @click="showCreateSectionModal(element)"
                      >{{ $t('add a section') }}</span
                    >

                    <!-- form: create new section -->
                    <form
                      v-if="createSectionModalShown && postTemplateId === element.id"
                      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                      @submit.prevent="submitSection(element)">
                      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <errors :errors="form.errors" />

                        <text-input
                          ref="newSection"
                          v-model="form.label"
                          :label="$t('Name')"
                          :type="'text'"
                          :autofocus="true"
                          :input-class="'block w-full'"
                          :required="true"
                          :autocomplete="false"
                          :maxlength="255"
                          @esc-key-pressed="
                            createSectionModalShown = false;
                            postTemplateId = 0;
                          " />
                      </div>

                      <div class="flex justify-between p-5">
                        <pretty-span
                          :text="$t('Cancel')"
                          :class="'me-3'"
                          @click="
                            createSectionModalShown = false;
                            postTemplateId = 0;
                          " />
                        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
                      </div>
                    </form>

                    <!-- blank state -->
                    <div
                      v-if="
                        element.post_template_sections.length === 0 &&
                        !createSectionModalShown &&
                        postTemplateId !== element.id
                      "
                      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                      <p class="p-5 text-center">
                        {{ $t('No roles yet.') }}

                        <span
                          class="block cursor-pointer text-sm text-blue-500 hover:underline"
                          @click="showCreateSectionModal(element)"
                          >{{ $t('add a section') }}</span
                        >
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <form
                v-else
                class="item-list mb-2 rounded-lg border border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
                @submit.prevent="update(element)">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                  <errors :errors="form.errors" />

                  <text-input
                    ref="renamePostTemplate"
                    v-model="form.label"
                    :label="$t('Name')"
                    :type="'text'"
                    :autofocus="true"
                    :input-class="'block w-full'"
                    :required="true"
                    :autocomplete="false"
                    :maxlength="255"
                    @esc-key-pressed="editPostTemplateId = 0" />
                </div>

                <div class="flex justify-between p-5">
                  <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="editPostTemplateId = 0" />
                  <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
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
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import draggable from 'vuedraggable';

export default {
  components: {
    InertiaLink: Link,
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
      this.postTemplateId = 0;
      this.createSectionModalShown = false;
      this.editPostTemplateId = 0;
      this.editSectionId = 0;

      this.$nextTick().then(() => this.$refs.newPostTemplate.focus());
    },

    showCreateSectionModal(postTemplate) {
      this.form.label = '';
      this.form.position = '';
      this.createSectionModalShown = true;
      this.postTemplateId = postTemplate.id;
      this.createPostTemplateModalShown = false;
      this.editPostTemplateId = 0;
      this.editSectionId = 0;

      this.$nextTick().then(() => this.$refs.newSection.focus());
    },

    renamePostTemplateModal(postTemplate) {
      this.form.label = postTemplate.label;
      this.editPostTemplateId = postTemplate.id;
      this.createSectionModalShown = false;
      this.editSectionId = 0;
      this.createPostTemplateModalShown = false;
      this.postTemplateId = 0;

      this.$nextTick().then(() => this.$refs.renamePostTemplate.focus());
    },

    renameSectionModal(section) {
      this.form.label = section.label;
      this.editSectionId = section.id;
      this.createSectionModalShown = false;
      this.createPostTemplateModalShown = false;
      this.editPostTemplateId = 0;
      this.postTemplateId = 0;

      this.$nextTick().then(() => this.$refs.renameSection.focus());
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The post template has been created'), 'success');
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
          this.flash(this.$t('The post template has been updated'), 'success');
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
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(postTemplate.url.destroy)
          .then(() => {
            this.flash(this.$t('The post template has been deleted'), 'success');
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
          this.flash(this.$t('The position has been saved'), 'success');
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
          this.flash(this.$t('The section has been created'), 'success');
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
          this.flash(this.$t('The section has been updated'), 'success');

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
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(section.url.destroy)
          .then(() => {
            this.flash(this.$t('The section has been deleted'), 'success');

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
