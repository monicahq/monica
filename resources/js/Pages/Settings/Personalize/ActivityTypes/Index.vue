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
            <li class="mr-2 inline text-gray-600">You are here:</li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings" class="text-blue-500 hover:underline">Settings</inertia-link>
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
            <li class="inline">Activity Types</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0"><span class="mr-1"> 🚲 </span> All the activity types</h3>
          <pretty-button
            v-if="!createActivityTypeModalShown"
            :text="'Add a new activity type'"
            :icon="'plus'"
            @click="showActivityTypeModal" />
        </div>

        <!-- modal to create an activity type -->
        <form
          v-if="createActivityTypeModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white"
          @submit.prevent="submitActivityType()">
          <div class="border-b border-gray-200 p-5">
            <errors :errors="form.errors" />

            <text-input
              :ref="'newActivityType'"
              v-model="form.activityTypeName"
              :label="'Name of the activity type'"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createActivityTypeModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="createActivityTypeModalShown = false" />
            <pretty-button :text="'Save'" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>

        <!-- list of activity types -->
        <ul v-if="localActivityTypes.length > 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
          <li v-for="activityType in localActivityTypes" :key="activityType.id">
            <!-- detail of the activity type -->
            <div
              v-if="renameActivityTypeModalShownId != activityType.id"
              class="item-list flex items-center justify-between border-b border-gray-200 px-5 py-2 hover:bg-slate-50">
              <span class="text-base font-semibold">{{ activityType.label }}</span>

              <!-- actions -->
              <ul class="text-sm">
                <li
                  class="inline cursor-pointer text-blue-500 hover:underline"
                  @click="renameActivityTypeModal(activityType)">
                  Rename
                </li>
                <li
                  class="ml-4 inline cursor-pointer text-red-500 hover:text-red-900"
                  @click="destroyActivityType(activityType)">
                  Delete
                </li>
              </ul>
            </div>

            <!-- rename an activity type modal -->
            <form
              v-if="renameActivityTypeModalShownId == activityType.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50"
              @submit.prevent="updateActivityType(activityType)">
              <div class="border-b border-gray-200 p-5">
                <errors :errors="form.errors" />

                <text-input
                  :ref="'rename' + activityType.id"
                  v-model="form.activityTypeName"
                  :label="'Name of the new group type'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="renameActivityTypeModalShownId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="'Cancel'" :classes="'mr-3'" @click.prevent="renameActivityTypeModalShownId = 0" />
                <pretty-button :text="'Rename'" :state="loadingState" :icon="'check'" :classes="'save'" />
              </div>
            </form>

            <!-- list of activities -->
            <div
              v-for="activity in activityType.activities"
              :key="activity.id"
              class="border-b border-gray-200 hover:bg-slate-50">
              <div v-if="renameActivityModalId != activity.id" class="flex items-center justify-between px-5 py-2 pl-6">
                <span>{{ activity.label }}</span>

                <!-- actions -->
                <ul class="text-sm">
                  <li
                    class="inline cursor-pointer text-blue-500 hover:underline"
                    @click="renameActivityModal(activity)">
                    Rename
                  </li>
                  <li
                    class="ml-4 inline cursor-pointer text-red-500 hover:text-red-900"
                    @click="destroyActivity(activityType, activity)">
                    Delete
                  </li>
                </ul>
              </div>

              <!-- rename the activity modal -->
              <form
                v-if="renameActivityModalId == activity.id"
                class="item-list border-b border-gray-200 hover:bg-slate-50"
                @submit.prevent="updateActivity(activityType, activity)">
                <div class="border-b border-gray-200 p-5">
                  <errors :errors="form.errors" />

                  <text-input
                    :ref="'rename' + activity.id"
                    v-model="form.label"
                    :label="'Label'"
                    :type="'text'"
                    :autofocus="true"
                    :input-class="'block w-full'"
                    :required="true"
                    :div-outer-class="'mb-4'"
                    :placeholder="'Wish good day'"
                    :autocomplete="false"
                    :maxlength="255"
                    @esc-key-pressed="renameActivityModalId = 0" />
                </div>

                <div class="flex justify-between p-5">
                  <pretty-span :text="'Cancel'" :classes="'mr-3'" @click.prevent="renameActivityModalId = 0" />
                  <pretty-button :text="'Rename'" :state="loadingState" :icon="'check'" :classes="'save'" />
                </div>
              </form>
            </div>

            <!-- create a new activity -->
            <div
              v-if="createActivityModalId != activityType.id"
              class="item-list border-b border-gray-200 px-5 py-2 pl-6 hover:bg-slate-50">
              <span
                class="cursor-pointer text-sm text-blue-500 hover:underline"
                @click="showActivityModal(activityType)"
                >Add a new activity</span
              >
            </div>

            <!-- create an activity -->
            <form
              v-if="createActivityModalId == activityType.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50"
              @submit.prevent="storeActivity(activityType)">
              <div class="border-b border-gray-200 p-5">
                <errors :errors="form.errors" />

                <text-input
                  :ref="'newActivity'"
                  v-model="form.label"
                  :label="'Label'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :placeholder="'Parent'"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createActivityModalId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="'Cancel'" :classes="'mr-3'" @click.prevent="createActivityModalId = 0" />
                <pretty-button :text="'Add'" :state="loadingState" :icon="'plus'" :classes="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div v-if="localActivityTypes.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
          <p class="p-5 text-center">Activity types let you indicate what you've done with your contacts.</p>
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
      createActivityTypeModalShown: false,
      renameActivityTypeModalShownId: 0,
      createActivityModalId: 0,
      renameActivityModalId: 0,
      localActivityTypes: [],
      form: {
        activityTypeName: '',
        label: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localActivityTypes = this.data.activity_types;
  },

  methods: {
    showActivityTypeModal() {
      this.form.activityTypeName = '';
      this.createActivityTypeModalShown = true;

      this.$nextTick(() => {
        this.$refs.newActivityType.focus();
      });
    },

    renameActivityTypeModal(activityType) {
      this.form.activityTypeName = activityType.label;
      this.renameActivityTypeModalShownId = activityType.id;

      this.$nextTick(() => {
        this.$refs[`rename${activityType.id}`].focus();
      });
    },

    showActivityModal(activityType) {
      this.createActivityModalId = activityType.id;
      this.form.label = '';

      this.$nextTick(() => {
        this.$refs.newActivity.focus();
      });
    },

    renameActivityModal(activity) {
      this.form.label = activity.label;
      this.renameActivityModalId = activity.id;

      this.$nextTick(() => {
        this.$refs[`rename${activity.id}`].focus();
      });
    },

    submitActivityType() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.activity_type_store, this.form)
        .then((response) => {
          this.flash('The activity type has been created', 'success');
          this.localActivityTypes.unshift(response.data.data);
          this.loadingState = null;
          this.createActivityTypeModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    updateActivityType(activityType) {
      this.loadingState = 'loading';

      axios
        .put(activityType.url.update, this.form)
        .then((response) => {
          this.flash('The activity type has been updated', 'success');
          this.localActivityTypes[this.localActivityTypes.findIndex((x) => x.id === activityType.id)] =
            response.data.data;
          this.loadingState = null;
          this.renameActivityTypeModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroyActivityType(activityType) {
      if (
        confirm(
          'Are you sure? This will delete all the activity types of this type for all the activities that were using it.',
        )
      ) {
        axios
          .delete(activityType.url.destroy)
          .then((response) => {
            this.flash('The activity type has been deleted', 'success');
            var id = this.localActivityTypes.findIndex((x) => x.id === activityType.id);
            this.localActivityTypes.splice(id, 1);
          })
          .catch((error) => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },

    storeActivity(activityType) {
      this.loadingState = 'loading';

      axios
        .post(activityType.url.store, this.form)
        .then((response) => {
          this.flash('The activity has been created', 'success');
          this.loadingState = null;
          this.createActivityModalId = 0;
          var id = this.localActivityTypes.findIndex((x) => x.id === activityType.id);
          this.localActivityTypes[id].activities.unshift(response.data.data);
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    updateActivity(activityType, activity) {
      this.loadingState = 'loading';

      axios
        .put(activity.url.update, this.form)
        .then((response) => {
          this.flash('The activity has been updated', 'success');
          this.loadingState = null;
          this.renameActivityModalId = 0;
          var activityTypeId = this.localActivityTypes.findIndex((x) => x.id === activityType.id);
          var typeId = this.localActivityTypes[activityTypeId].activities.findIndex((x) => x.id === activity.id);
          this.localActivityTypes[activityTypeId].activities[typeId] = response.data.data;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroyActivity(activityType, activity) {
      if (
        confirm(
          'Are you sure? This will delete all the activities of this type for all the contacts that were using it.',
        )
      ) {
        axios
          .delete(activity.url.destroy)
          .then((response) => {
            this.flash('The activity has been deleted', 'success');
            var activityTypeId = this.localActivityTypes.findIndex((x) => x.id === activityType.id);
            var typeId = this.localActivityTypes[activityTypeId].activities.findIndex((x) => x.id === activity.id);
            this.localActivityTypes[activityTypeId].activities.splice(typeId, 1);
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
