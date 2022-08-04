<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative mr-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-sidebar relative inline h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5" />
          </svg>
        </span>

        <span class="font-semibold"> Goals </span>
      </div>
      <pretty-button :text="'Add a goal'" :icon="'plus'" :classes="'sm:w-fit w-full'" @click="showCreateGoalModal" />
    </div>

    <!-- add a note modal -->
    <form v-if="createGoalModalShown" class="bg-form mb-6 rounded-lg border border-gray-200" @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5">
        <errors :errors="form.errors" />
        <!-- name -->
        <text-input
          :ref="'newName'"
          v-model="form.name"
          :label="'Name'"
          :type="'text'"
          :input-class="'block w-full mb-3'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="createGoalModalShown = false" />
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="createGoalModalShown = false" />
        <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'check'" :classes="'save'" />
      </div>
    </form>

    <!-- goals -->
    <div v-if="localGoals.length > 0">
      <div v-for="goal in localGoals" :key="goal.id" class="mb-4 rounded border border-gray-200 last:mb-0">
        <div v-if="editedGoalId !== goal.id">
          <div class="flex items-center justify-between border-b border-gray-200 p-3">
            <div class="font-semibold text-gray-600">
              {{ goal.name }}
            </div>

            <div>
              <inertia-link :href="goal.url.show" class="text-sm text-blue-500 hover:underline">
                View details
              </inertia-link>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row sm:justify-between">
            <!-- streaks -->
            <div class="flex flex-col p-0 sm:flex-row sm:p-3">
              <div
                v-for="streak in goal.last_7_days"
                :key="streak.id"
                class="mr-0 flex flex-row items-center justify-between border-b border-gray-200 p-3 text-center sm:mr-7 sm:mb-0 sm:w-9 sm:flex-col sm:border-0 sm:p-0"
                :class="{ 'text-gray-500': !streak.active }">
                <div>
                  <span class="mb-0 mr-2 block text-xs font-semibold sm:mr-0">
                    {{ streak.day }}
                  </span>
                  <span class="mr-2 sm:mr-0">
                    {{ streak.day_number }}
                  </span>
                </div>

                <!-- active streak -->
                <span
                  v-if="streak.active"
                  class="mr-2 cursor-pointer text-2xl sm:mr-0"
                  @click="toggleStreak(goal, streak)"
                  >👍</span
                >

                <!-- inactive streak -->
                <span
                  v-else
                  class="mr-2 cursor-pointer text-center text-2xl sm:mr-0"
                  @click="toggleStreak(goal, streak)">
                  <div class="rounded-md border border-gray-200 bg-slate-100 py-1 px-2">
                    <svg
                      class="z-50"
                      width="18"
                      height="18"
                      viewBox="0 0 18 18"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg">
                      <g clip-path="url(#clip0_522_316)">
                        <path
                          fill-rule="evenodd"
                          clip-rule="evenodd"
                          d="M1.6875 9C1.6875 7.0606 2.45792 5.20064 3.82928 3.82928C5.20064 2.45792 7.0606 1.6875 9 1.6875C10.9394 1.6875 12.7994 2.45792 14.1707 3.82928C15.5421 5.20064 16.3125 7.0606 16.3125 9C16.3125 10.9394 15.5421 12.7994 14.1707 14.1707C12.7994 15.5421 10.9394 16.3125 9 16.3125C7.0606 16.3125 5.20064 15.5421 3.82928 14.1707C2.45792 12.7994 1.6875 10.9394 1.6875 9ZM9 0C6.61305 0 4.32387 0.948212 2.63604 2.63604C0.948212 4.32387 0 6.61305 0 9C0 11.3869 0.948212 13.6761 2.63604 15.364C4.32387 17.0518 6.61305 18 9 18C11.3869 18 13.6761 17.0518 15.364 15.364C17.0518 13.6761 18 11.3869 18 9C18 6.61305 17.0518 4.32387 15.364 2.63604C13.6761 0.948212 11.3869 0 9 0V0ZM5.625 9C5.92337 9 6.20952 8.88147 6.4205 8.6705C6.63147 8.45952 6.75 8.17337 6.75 7.875C6.75 7.57663 6.63147 7.29048 6.4205 7.0795C6.20952 6.86853 5.92337 6.75 5.625 6.75C5.32663 6.75 5.04048 6.86853 4.8295 7.0795C4.61853 7.29048 4.5 7.57663 4.5 7.875C4.5 8.17337 4.61853 8.45952 4.8295 8.6705C5.04048 8.88147 5.32663 9 5.625 9ZM13.5 7.875C13.5 8.17337 13.3815 8.45952 13.1705 8.6705C12.9595 8.88147 12.6734 9 12.375 9C12.0766 9 11.7905 8.88147 11.5795 8.6705C11.3685 8.45952 11.25 8.17337 11.25 7.875C11.25 7.57663 11.3685 7.29048 11.5795 7.0795C11.7905 6.86853 12.0766 6.75 12.375 6.75C12.6734 6.75 12.9595 6.86853 13.1705 7.0795C13.3815 7.29048 13.5 7.57663 13.5 7.875ZM5.985 10.8405C6.16651 10.7134 6.39072 10.6628 6.60922 10.6997C6.82771 10.7365 7.02294 10.8578 7.15275 11.0374L7.16062 11.0475C7.2765 11.1802 7.40813 11.2972 7.55438 11.3962C7.85138 11.5965 8.32275 11.8125 9 11.8125C9.67725 11.8125 10.1475 11.5965 10.4456 11.3951C10.5919 11.2961 10.7235 11.1791 10.8394 11.0464L10.8472 11.0374C10.9115 10.9471 10.9929 10.8704 11.0868 10.8116C11.1808 10.7528 11.2853 10.713 11.3946 10.6947C11.5039 10.6763 11.6157 10.6796 11.7237 10.7044C11.8317 10.7293 11.9337 10.7751 12.024 10.8394C12.1143 10.9036 12.191 10.9851 12.2498 11.079C12.3086 11.1729 12.3483 11.2775 12.3667 11.3867C12.3851 11.496 12.3818 11.6078 12.357 11.7158C12.3321 11.8238 12.2863 11.9259 12.222 12.0161L11.5312 11.5312C12.222 12.015 12.222 12.0161 12.2209 12.0161V12.0173L12.2198 12.0195L12.2175 12.0229L12.2119 12.0308L12.1961 12.0521C12.1329 12.1368 12.0637 12.2169 11.9891 12.2917C11.8091 12.4785 11.6089 12.6473 11.3929 12.7924C10.6841 13.2626 9.85163 13.5101 9 13.5C7.93575 13.5 7.1415 13.1535 6.60825 12.7913C6.30535 12.5856 6.03433 12.3366 5.80387 12.0521L5.78813 12.0296L5.7825 12.0229L5.78025 12.0195V12.0173H5.77913L6.46875 11.5312L5.778 12.015C5.64971 11.8319 5.59933 11.6054 5.63793 11.3852C5.67653 11.1649 5.80095 10.969 5.98387 10.8405H5.985Z"
                          fill="#CFCFCF" />
                      </g>
                      <defs>
                        <clipPath id="clip0_522_316">
                          <rect width="18" height="18" fill="white" />
                        </clipPath>
                      </defs>
                    </svg>
                  </div>
                </span>
              </div>
            </div>

            <!-- stats -->
            <div class="flex justify-between p-3">
              <div class="mr-6 flex items-center">
                <div class="mr-3 w-14 text-right text-sm text-gray-500">Current streak</div>
                <div class="text-4xl">
                  {{ goal.streaks_statistics.current_streak }}
                </div>
              </div>
              <div class="flex items-center">
                <div class="mr-3 w-14 text-right text-sm text-gray-500">Longest streak</div>
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
    <div v-if="localGoals.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">There are no goals yet.</p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    PrettyButton,
    PrettySpan,
    TextInput,
    Errors,
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

      this.$nextTick(() => {
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
          this.flash('The goal has been created', 'success');
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
          this.flash('The goal has been edited', 'success');
          this.localGoals[this.localGoals.findIndex((x) => x.id === goal.id)] = response.data.data;
          this.editedGoalId = 0;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    destroy(goal) {
      if (confirm('Are you sure? This will delete the goal permanently.')) {
        axios
          .delete(goal.url.destroy)
          .then(() => {
            this.flash('The goal has been deleted', 'success');
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
