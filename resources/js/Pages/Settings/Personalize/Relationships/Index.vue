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
              {{ $t('Relationship types') }}
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
            <span class="me-1"> 🥸 </span>
            {{ $t('All the relationship types') }}
          </h3>
          <pretty-button
            v-if="!createRelationshipGroupTypeModalShown"
            :text="$t('Add a relationship group type')"
            :icon="'plus'"
            @click="showCreateRelationshipGroupTypeModal" />
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
                  'When you define a relationship between two contacts, for instance a father-son relationship, Monica creates two relations, one for each contact:',
                )
              }}
            </p>
            <ul class="mb-2 list-disc ps-4">
              <li>{{ $t('a father-son relation shown on the father page,') }}</li>
              <li>{{ $t('a son-father relation shown on the son page.') }}</li>
            </ul>
            <p class="mb-2">
              {{
                $t(
                  'We call them a relation, and its reverse relation. For each relation you define, you need to define its counterpart.',
                )
              }}
            </p>
          </div>
        </div>

        <!-- modal to create a relationship -->
        <form
          v-if="createRelationshipGroupTypeModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submitGroupType()">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              ref="newGroupType"
              v-model="form.relationshipGroupTypeName"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createRelationshipGroupTypeModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createRelationshipGroupTypeModalShown = false" />
            <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
          </div>
        </form>

        <!-- list of relationships -->
        <ul
          v-if="localGroupTypes.length > 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <li v-for="groupType in localGroupTypes" :key="groupType.id">
            <!-- detail of the relationship -->
            <div
              v-if="renameRelationshipGroupTypeModalShownId !== groupType.id"
              class="item-list flex items-center justify-between border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <span class="text-base font-semibold">{{ groupType.name }}</span>

              <!-- actions -->
              <ul class="text-sm">
                <li class="inline cursor-pointer" @click="renameRelationshipGroupTypeModal(groupType)">
                  <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                </li>
                <li
                  v-if="groupType.can_be_deleted"
                  class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900"
                  @click="destroyGroupType(groupType)">
                  {{ $t('Delete') }}
                </li>
              </ul>
            </div>

            <!-- rename a group type modal -->
            <form
              v-if="renameRelationshipGroupTypeModalShownId === groupType.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
              @submit.prevent="updateGroupType(groupType)">
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <errors :errors="form.errors" />

                <text-input
                  ref="renameGroupType"
                  v-model="form.relationshipGroupTypeName"
                  :label="$t('Name')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="renameRelationshipGroupTypeModalShownId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span
                  :text="$t('Cancel')"
                  :class="'me-3'"
                  @click.prevent="renameRelationshipGroupTypeModalShownId = 0" />
                <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
              </div>
            </form>

            <!-- list of relationship types -->
            <div
              v-for="type in groupType.types"
              :key="type.id"
              class="border-b border-gray-200 px-5 py-2 ps-6 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <!-- detail of the relationship type -->
              <div v-if="renameRelationshipTypeModalId !== type.id" class="flex items-center justify-between">
                <div class="relative">
                  <!-- relation type name -->
                  <span>{{ type.name }}</span>

                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="relative inline h-5 w-5 px-1"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 8l4 4m0 0l-4 4m4-4H3" />
                  </svg>

                  <!-- relation type reverse name -->
                  <span>{{ type.name_reverse_relationship }}</span>
                </div>

                <!-- actions -->
                <ul class="text-sm">
                  <li class="inline cursor-pointer" @click="renameRelationTypeModal(type)">
                    <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                  </li>
                  <li
                    v-if="type.can_be_deleted"
                    class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900"
                    @click="destroyRelationshipType(groupType, type)">
                    {{ $t('Delete') }}
                  </li>
                </ul>
              </div>

              <!-- rename the relationship type modal -->
              <form
                v-if="renameRelationshipTypeModalId === type.id"
                class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
                @submit.prevent="updateRelationType(groupType, type)">
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
                    :class="'mb-4'"
                    :placeholder="$t('Parent')"
                    :autocomplete="false"
                    :maxlength="255"
                    @esc-key-pressed="renameRelationshipTypeModalId = 0" />

                  <text-input
                    v-model="form.nameReverseRelationship"
                    :label="$t('Name of the reverse relationship')"
                    :type="'text'"
                    :autofocus="true"
                    :input-class="'block w-full'"
                    :required="true"
                    :autocomplete="false"
                    :placeholder="$t('Child')"
                    :maxlength="255"
                    @esc-key-pressed="renameRelationshipTypeModalId = 0" />
                </div>

                <div class="flex justify-between p-5">
                  <pretty-span
                    :text="$t('Cancel')"
                    :class="'me-3'"
                    @click.prevent="renameRelationshipTypeModalId = 0" />
                  <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
                </div>
              </form>
            </div>

            <!-- create a new relationship type line -->
            <div
              v-if="createRelationshipTypeModalId !== groupType.id"
              class="item-list border-b border-gray-200 px-5 py-2 ps-6 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <span
                class="cursor-pointer text-sm text-blue-500 hover:underline"
                @click="showRelationshipTypeModal(groupType)"
                >{{ $t('Add a relationship type') }}</span
              >
            </div>

            <!-- create a new relationship type -->
            <form
              v-if="createRelationshipTypeModalId === groupType.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
              @submit.prevent="storeRelationshipType(groupType)">
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <errors :errors="form.errors" />

                <text-input
                  ref="newRelationshipType"
                  v-model="form.name"
                  :label="$t('Name')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :class="'mb-4'"
                  :placeholder="$t('Parent')"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createRelationshipTypeModalId = 0" />

                <text-input
                  v-model="form.nameReverseRelationship"
                  :label="$t('Name of the reverse relationship')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :placeholder="$t('Child')"
                  :maxlength="255"
                  @esc-key-pressed="createRelationshipTypeModalId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="createRelationshipTypeModalId = 0" />
                <pretty-button :text="$t('Add')" :state="loadingState" :icon="'plus'" :class="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div
          v-if="localGroupTypes.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{ $t('Relationship types let you link contacts and document how they are connected.') }}
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
      createRelationshipGroupTypeModalShown: false,
      renameRelationshipGroupTypeModalShownId: 0,
      createRelationshipTypeModalId: 0,
      renameRelationshipTypeModalId: 0,
      localGroupTypes: [],
      form: {
        relationshipGroupTypeName: '',
        name: '',
        nameReverseRelationship: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localGroupTypes = this.data.group_types;
  },

  methods: {
    showCreateRelationshipGroupTypeModal() {
      this.form.relationshipGroupTypeName = '';
      this.createRelationshipGroupTypeModalShown = true;
      this.renameRelationshipGroupTypeModalShownId = 0;
      this.createRelationshipTypeModalId = 0;
      this.renameRelationshipTypeModalId = 0;

      this.$nextTick().then(() => {
        this.$refs.newGroupType.focus();
      });
    },

    renameRelationshipGroupTypeModal(groupType) {
      this.form.relationshipGroupTypeName = groupType.name;
      this.renameRelationshipGroupTypeModalShownId = groupType.id;
      this.createRelationshipGroupTypeModalShown = false;
      this.createRelationshipTypeModalId = 0;
      this.renameRelationshipTypeModalId = 0;

      this.$nextTick().then(() => {
        this.$refs.renameGroupType[0].focus();
      });
    },

    showRelationshipTypeModal(groupType) {
      this.form.name = '';
      this.form.nameReverseRelationship = '';
      this.createRelationshipTypeModalId = groupType.id;
      this.renameRelationshipTypeModalId = 0;
      this.renameRelationshipGroupTypeModalShownId = 0;
      this.createRelationshipGroupTypeModalShown = false;

      this.$nextTick().then(() => {
        this.$refs.newRelationshipType[0].focus();
      });
    },

    renameRelationTypeModal(type) {
      this.form.name = type.name;
      this.form.nameReverseRelationship = type.name_reverse_relationship;
      this.renameRelationshipTypeModalId = type.id;
      this.createRelationshipTypeModalId = 0;
      this.renameRelationshipGroupTypeModalShownId = 0;
      this.createRelationshipGroupTypeModalShown = false;

      this.$nextTick().then(() => {
        this.$refs.rename[0].focus();
      });
    },

    submitGroupType() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.group_type_store, this.form)
        .then((response) => {
          this.flash(this.$t('The group type has been created'), 'success');
          this.localGroupTypes.unshift(response.data.data);
          this.loadingState = null;
          this.createRelationshipTypeModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    updateGroupType(groupType) {
      this.loadingState = 'loading';

      axios
        .put(groupType.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The group type has been updated'), 'success');
          this.localGroupTypes[this.localGroupTypes.findIndex((x) => x.id === groupType.id)] = response.data.data;
          this.loadingState = null;
          this.renameRelationshipGroupTypeModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroyGroupType(groupType) {
      if (
        confirm(
          this.$t(
            'Are you sure? This will delete all the relationships of this type for all the contacts that were using it.',
          ),
        )
      ) {
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

    storeRelationshipType(groupType) {
      this.loadingState = 'loading';

      axios
        .post(groupType.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The relationship type has been created'), 'success');
          this.loadingState = null;
          this.createRelationshipTypeModalId = 0;
          var id = this.localGroupTypes.findIndex((x) => x.id === groupType.id);
          this.localGroupTypes[id].types.unshift(response.data.data);
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    updateRelationType(groupType, type) {
      this.loadingState = 'loading';

      axios
        .put(type.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The relationship type has been updated'), 'success');
          this.loadingState = null;
          this.renameRelationshipTypeModalId = 0;
          var groupTypeId = this.localGroupTypes.findIndex((x) => x.id === groupType.id);
          var typeId = this.localGroupTypes[groupTypeId].types.findIndex((x) => x.id === type.id);
          this.localGroupTypes[groupTypeId].types[typeId] = response.data.data;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroyRelationshipType(groupType, type) {
      if (
        confirm(
          this.$t(
            'Are you sure? This will delete all the relationships of this type for all the contacts that were using it.',
          ),
        )
      ) {
        axios
          .delete(type.url.destroy)
          .then(() => {
            this.flash(this.$t('The relationship type has been deleted'), 'success');
            var groupTypeId = this.localGroupTypes.findIndex((x) => x.id === groupType.id);
            var typeId = this.localGroupTypes[groupTypeId].types.findIndex((x) => x.id === type.id);
            this.localGroupTypes[groupTypeId].types.splice(typeId, 1);
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
