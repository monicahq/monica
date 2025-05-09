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
            <li class="inline">{{ $t('Group types') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0">
            <span class="me-1"> 👥 </span>
            {{ $t('All the group types') }}
          </h3>
          <pretty-button
            v-if="!createGroupTypeModalShown"
            :text="$t('Add a group type')"
            :icon="'plus'"
            @click="showCreateGroupTypeModal" />
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
                  'A group is two or more people together. It can be a family, a household, a sport club. Whatever is important to you.',
                )
              }}
            </p>
          </div>
        </div>

        <!-- modal to create a group type -->
        <form
          v-if="createGroupTypeModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              ref="newGroupType"
              v-model="form.label"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createGroupTypeModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createGroupTypeModalShown = false" />
            <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
          </div>
        </form>

        <!-- list of group types -->
        <div v-if="localGroupTypes.length > 0" class="mb-6">
          <draggable
            :list="localGroupTypes"
            item-key="id"
            :component-data="{ name: 'fade' }"
            handle=".handle"
            @change="updatePosition">
            <template #item="{ element }">
              <div v-if="editGroupTypeId !== element.id">
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
                      <li class="inline cursor-pointer" @click="renameGroupTypeModal(element)">
                        <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                      </li>
                      <li class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(element)">
                        {{ $t('Delete') }}
                      </li>
                    </ul>
                  </div>

                  <!-- available roles -->
                  <div class="ms-8">
                    <p class="mb-1 text-sm text-gray-500">{{ $t('Roles:') }}</p>

                    <draggable
                      :list="element.group_type_roles"
                      item-key="id"
                      :component-data="{ name: 'fade' }"
                      handle=".handle"
                      @change="updatePosition">
                      <template #item="{ element: element2 }">
                        <div v-if="editRoleId !== element2.id">
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
                                <li class="inline cursor-pointer" @click="renameRoleModal(element2)">
                                  <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                                </li>
                                <li
                                  class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900"
                                  @click="destroyRole(element2)">
                                  {{ $t('Delete') }}
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>

                        <!-- edit a role form -->
                        <form
                          v-else
                          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                          @submit.prevent="updateRole(element2)">
                          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                            <errors :errors="form.errors" />

                            <text-input
                              ref="renameRole"
                              v-model="form.label"
                              :label="$t('Name')"
                              :type="'text'"
                              :autofocus="true"
                              :input-class="'block w-full'"
                              :required="true"
                              :autocomplete="false"
                              :maxlength="255"
                              @esc-key-pressed="
                                editRoleId = 0;
                                editGroupTypeId = 0;
                              " />
                          </div>

                          <div class="flex justify-between p-5">
                            <pretty-span
                              :text="$t('Cancel')"
                              :class="'me-3'"
                              @click="
                                editRoleId = 0;
                                editGroupTypeId = 0;
                              " />
                            <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
                          </div>
                        </form>
                      </template>
                    </draggable>

                    <!-- add a role -->
                    <span
                      v-if="
                        element.group_type_roles.length !== 0 && !createRoleModalShown && roleGroupTypeId !== element.id
                      "
                      class="inline cursor-pointer text-sm text-blue-500 hover:underline"
                      @click="showCreateRoleModal(element)"
                      >{{ $t('add a role') }}</span
                    >

                    <!-- form: create new role -->
                    <form
                      v-if="createRoleModalShown && roleGroupTypeId === element.id"
                      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                      @submit.prevent="submitRole(element)">
                      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <errors :errors="form.errors" />

                        <text-input
                          ref="newRole"
                          v-model="form.label"
                          :label="$t('Name')"
                          :type="'text'"
                          :autofocus="true"
                          :input-class="'block w-full'"
                          :required="true"
                          :autocomplete="false"
                          :maxlength="255"
                          @esc-key-pressed="
                            createRoleModalShown = false;
                            roleGroupTypeId = 0;
                          " />
                      </div>

                      <div class="flex justify-between p-5">
                        <pretty-span
                          :text="$t('Cancel')"
                          :class="'me-3'"
                          @click="
                            createRoleModalShown = false;
                            roleGroupTypeId = 0;
                          " />
                        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
                      </div>
                    </form>

                    <!-- blank state -->
                    <div
                      v-if="
                        element.group_type_roles.length === 0 && !createRoleModalShown && roleGroupTypeId !== element.id
                      "
                      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                      <p class="p-5 text-center">
                        {{ $t('No roles yet.') }}
                        <span
                          class="block cursor-pointer text-sm text-blue-500 hover:underline"
                          @click="showCreateRoleModal(element)"
                          >{{ $t('add a role') }}</span
                        >
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <form
                v-else
                class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
                @submit.prevent="update(element)">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                  <errors :errors="form.errors" />

                  <text-input
                    ref="renameGroupType"
                    v-model="form.label"
                    :label="$t('Name')"
                    :type="'text'"
                    :autofocus="true"
                    :input-class="'block w-full'"
                    :required="true"
                    :autocomplete="false"
                    :maxlength="255"
                    @esc-key-pressed="editGroupTypeId = 0" />
                </div>

                <div class="flex justify-between p-5">
                  <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="editGroupTypeId = 0" />
                  <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
                </div>
              </form>
            </template>
          </draggable>
        </div>

        <!-- blank state -->
        <div
          v-if="localGroupTypes.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">{{ $t('Group types let you group people together.') }}</p>
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
      createGroupTypeModalShown: false,
      createRoleModalShown: false,
      roleGroupTypeId: 0,
      editGroupTypeId: 0,
      editRoleId: 0,
      localGroupTypes: [],
      form: {
        label: '',
        position: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localGroupTypes = this.data.group_types;
  },

  methods: {
    showCreateGroupTypeModal() {
      this.form.label = '';
      this.form.position = '';
      this.createGroupTypeModalShown = true;
      this.createRoleModalShown = false;
      this.roleGroupTypeId = 0;
      this.editGroupTypeId = 0;
      this.editRoleId = 0;

      this.$nextTick().then(() => {
        this.$refs.newGroupType.focus();
      });
    },

    showCreateRoleModal(groupType) {
      this.form.label = '';
      this.form.position = '';
      this.createRoleModalShown = true;
      this.roleGroupTypeId = groupType.id;
      this.editGroupTypeId = 0;
      this.editRoleId = 0;
      this.createGroupTypeModalShown = false;

      this.$nextTick().then(() => {
        this.$refs.newRole.focus();
      });
    },

    renameGroupTypeModal(groupType) {
      this.form.label = groupType.label;
      this.editGroupTypeId = groupType.id;
      this.roleGroupTypeId = 0;
      this.editRoleId = 0;
      this.createRoleModalShown = false;
      this.createGroupTypeModalShown = false;

      this.$nextTick().then(() => this.$refs.renameGroupType.focus());
    },

    renameRoleModal(role) {
      this.form.label = role.label;
      this.editRoleId = role.id;
      this.roleGroupTypeId = 0;
      this.editGroupTypeId = 0;
      this.createRoleModalShown = false;
      this.createGroupTypeModalShown = false;

      this.$nextTick().then(() => this.$refs.renameRole.focus());
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The group type has been created'), 'success');
          this.localGroupTypes.push(response.data.data);
          this.loadingState = null;
          this.createGroupTypeModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(groupType) {
      this.loadingState = 'loading';

      axios
        .put(groupType.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The group type has been updated'), 'success');
          this.localGroupTypes[this.localGroupTypes.findIndex((x) => x.id === groupType.id)] = response.data.data;
          this.loadingState = null;
          this.editGroupTypeId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(groupType) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(groupType.url.destroy)
          .then(() => {
            this.flash(this.$t('The group type has been deleted'), 'success');
            var id = this.localGroupTypes.findIndex((x) => x.id === groupType.id);
            this.localGroupTypes.splice(id, 1);
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

    submitRole(groupType) {
      this.loadingState = 'loading';

      axios
        .post(groupType.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The role has been created'), 'success');
          var id = this.localGroupTypes.findIndex((x) => x.id === groupType.id);
          this.localGroupTypes[id].group_type_roles.push(response.data.data);
          this.loadingState = null;
          this.roleGroupTypeId = 0;
          this.createRoleModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    updateRole(role) {
      this.loadingState = 'loading';

      axios
        .put(role.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The role has been updated'), 'success');

          var groupTypeId = this.localGroupTypes.findIndex((x) => x.id === role.group_type_id);
          var roleId = this.localGroupTypes[groupTypeId].group_type_roles.findIndex((x) => x.id === role.id);
          this.localGroupTypes[groupTypeId].group_type_roles[roleId] = response.data.data;

          this.loadingState = null;
          this.roleGroupTypeId = 0;
          this.editRoleId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroyRole(role) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(role.url.destroy)
          .then(() => {
            this.flash(this.$t('The role has been deleted'), 'success');

            var groupTypeId = this.localGroupTypes.findIndex((x) => x.id === role.group_type_id);
            var roleId = this.localGroupTypes[groupTypeId].group_type_roles.findIndex((x) => x.id === role.id);
            this.localGroupTypes[groupTypeId].group_type_roles.splice(roleId, 1);
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
