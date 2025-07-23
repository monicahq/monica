<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <Crosshair class="h-4 w-4 text-gray-600" />

        <span class="font-semibold"> {{ $t('Goals') }} </span>
      </div>
      <pretty-button :text="$t('Add a goal')" :icon="'plus'" :class="'w-full sm:w-fit'" @click="showCreateGoalModal" />
    </div>

    <!-- add a note modal -->
    <form
      v-if="createGoalModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />
        <!-- name -->
        <text-input
          ref="newName"
          v-model="form.name"
          :label="$t('Name')"
          :type="'text'"
          :input-class="'block w-full mb-3'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="createGoalModalShown = false" />
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createGoalModalShown = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
      </div>
    </form>

    <!-- goals -->
    <div v-if="localGoals.length > 0">
      <div
        v-for="goal in localGoals"
        :key="goal.id"
        class="mb-4 rounded-xs border border-gray-200 last:mb-0 dark:border-gray-700 dark:bg-gray-900">
        <div v-if="editedGoalId !== goal.id">
          <div class="flex items-center justify-between border-b border-gray-200 p-3 dark:border-gray-700">
            <div class="font-semibold text-gray-600 dark:text-gray-400">
              {{ goal.name }}
            </div>

            <div>
              <InertiaLink :href="goal.url.show" class="text-sm text-blue-500 hover:underline">
                {{ $t('View details') }}
              </InertiaLink>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row sm:justify-between">
            <!-- streaks -->
            <div class="flex flex-col p-0 sm:flex-row sm:p-3">
              <div
                v-for="streak in goal.last_7_days"
                :key="streak.id"
                class="me-0 flex flex-row items-center justify-between border-b border-gray-200 p-3 text-center dark:border-gray-700 sm:mb-0 sm:me-7 sm:w-9 sm:flex-col sm:border-0 sm:p-0"
                :class="{ 'text-gray-500': !streak.active }">
                <div>
                  <span class="mb-0 me-2 block text-xs font-semibold sm:me-0">
                    {{ streak.day }}
                  </span>
                  <span class="me-2 sm:me-0">
                    {{ streak.day_number }}
                  </span>
                </div>

                <!-- active streak -->
                <span
                  v-if="streak.active"
                  class="me-2 cursor-pointer text-2xl sm:me-0"
                  @click="toggleStreak(goal, streak)"
                  >👍</span
                >

                <!-- inactive streak -->
                <span
                  v-else
                  class="me-2 cursor-pointer text-center text-2xl sm:me-0"
                  @click="toggleStreak(goal, streak)">
                  <div
                    class="rounded-md border border-gray-200 bg-slate-100 px-2 py-1 dark:border-gray-700 dark:bg-slate-900">
                    <FaceIcon />
                  </div>
                </span>
              </div>
            </div>

            <!-- stats -->
            <div class="flex justify-between p-3">
              <div class="me-6 flex items-center">
                <div class="me-3 w-14 text-right text-sm text-gray-500">{{ $t('Current streak') }}</div>
                <div class="text-4xl">
                  {{ goal.streaks_statistics.current_streak }}
                </div>
              </div>
              <div class="flex items-center">
                <div class="me-3 w-14 text-right text-sm text-gray-500">{{ $t('Longest streak') }}</div>
                <div class="text-4xl">
                  {{ goal.streaks_statistics.max_streak }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- blank state -->
    <div
      v-if="localGoals.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_goal.svg" :alt="$t('Goals')" class="mx-auto mt-4 h-14 w-14" />
      <p class="px-5 pb-5 pt-2 text-center">{{ $t('There are no goals yet.') }}</p>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import { Crosshair } from 'lucide-vue-next';

export default {
  components: {
    InertiaLink: Link,
    PrettyButton,
    PrettySpan,
    TextInput,
    Errors,
    Crosshair,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      loadingState: '',
      localGoals: [],
      editedGoalId: 0,
      createGoalModalShown: false,
      form: {
        name: '',
        errors: [],
      },
    };
  },

  created() {
    this.localGoals = this.data.active_goals;
  },

  methods: {
    showCreateGoalModal() {
      this.form.errors = [];
      this.form.name = '';
      this.createGoalModalShown = true;

      this.$nextTick().then(() => {
        this.$refs.newName.focus();
      });
    },

    showEditGoalModal(goal) {
      this.editedGoalId = goal.id;
      this.form.name = goal.name;
    },

    submit() {
      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The goal has been created'), 'success');
          this.localGoals.unshift(response.data.data);
          this.createGoalModalShown = false;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    toggleStreak(goal, streak) {
      this.form.happened_at = streak.happened_at;

      axios
        .put(goal.url.streak_update, this.form)
        .then((response) => {
          this.flash(this.$t('The goal has been updated'), 'success');
          this.localGoals[this.localGoals.findIndex((x) => x.id === goal.id)] = response.data.data;
          this.editedGoalId = 0;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    destroy(goal) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(goal.url.destroy)
          .then(() => {
            this.flash(this.$t('The goal has been deleted'), 'success');
            var id = this.localGoals.findIndex((x) => x.id === goal.id);
            this.localGoals.splice(id, 1);
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
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}
</style>
