<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-slate-200">
              {{ $t('app.breadcrumb_location') }}
            </li>
            <li class="mr-2 inline">
              <inertia-link :href="layoutData.vault.url.contacts" class="text-blue-500 hover:underline">
                {{ $t('app.breadcrumb_contact_index') }}
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
              <inertia-link :href="data.url.contact" class="text-blue-500 hover:underline">
                {{ $t('app.breadcrumb_contact_show', { name: data.contact.name }) }}
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
              {{ $t('app.breadcrumb_contact_goal') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-18 relative">
      <div class="mx-auto max-w-5xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div>
            <div @click="showEditModal()" class="cursor-pointer text-blue-500 hover:underline">
              {{ $t('app.rename') }}
            </div>
            <div @click="destroy()" class="cursor-pointer text-blue-500 hover:underline">{{ $t('app.delete') }}</div>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <h1 v-if="!editMode" class="mb-4 font-semibold">{{ localGoal.name }}</h1>

            <!-- edit modal form -->
            <form v-if="editMode" class="bg-form mb-4 rounded-lg border border-gray-200" @submit.prevent="update()">
              <div class="border-b border-gray-200 p-5">
                <errors :errors="form.errors" />

                <!-- name -->
                <text-input
                  :ref="'newName'"
                  v-model="form.name"
                  :label="'Name'"
                  :type="'text'"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="editMode = false" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="editMode = false" />
                <pretty-button :text="$t('app.update')" :state="loadingState" :icon="'check'" :classes="'save'" />
              </div>
            </form>

            <!-- stats -->
            <div class="mb-6 flex justify-between rounded border border-gray-200 p-3">
              <div class="mr-6 flex items-center">
                <div class="mr-3 w-14 text-right text-sm text-gray-500">Total streaks</div>
                <div class="text-4xl">
                  {{ localGoal.count }}
                </div>
              </div>
              <div class="mr-6 flex items-center">
                <div class="mr-3 w-14 text-right text-sm text-gray-500">Current streak</div>
                <div class="text-4xl">
                  {{ localGoal.streaks_statistics.current_streak }}
                </div>
              </div>
              <div class="flex items-center">
                <div class="mr-3 w-14 text-right text-sm text-gray-500">Longest streak</div>
                <div class="text-4xl">
                  {{ localGoal.streaks_statistics.max_streak }}
                </div>
              </div>
            </div>

            <!-- details -->
            <p class="mb-2 text-xs">Details in the last year</p>
            <div class="grid-calendar grid">
              <div v-for="week in localGoal.weeks" :key="week.id">
                <div v-for="streak in week.streaks" :key="streak.id" class="">
                  <a-tooltip placement="topLeft" :title="streak.date" arrow-point-at-center>
                    <!-- there is a streak for this day -->
                    <div
                      v-if="!streak.not_yet_happened && streak.streak"
                      class="h-3 w-3 cursor-pointer rounded border border-transparent bg-green-400 hover:border-green-500"></div>

                    <!-- there is not a streak for this day -->
                    <div
                      v-if="!streak.not_yet_happened && !streak.streak"
                      class="h-3 w-3 cursor-pointer rounded bg-slate-200"></div>
                  </a-tooltip>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import TextInput from '@/Shared/Form/TextInput';
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';

export default {
  components: {
    Layout,
    TextInput,
    PrettyButton,
    PrettySpan,
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
      localGoal: [],
      editMode: false,
      form: {
        name: '',
        errors: [],
      },
    };
  },

  created() {
    this.localGoal = this.data;
  },

  methods: {
    showEditModal() {
      this.editMode = true;
      this.form.name = this.localGoal.name;

      this.$nextTick(() => {
        this.$refs.newName.focus();
      });
    },

    update() {
      axios
        .put(this.localGoal.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('contact.goals_update_success'), 'success');
          this.localGoal = response.data.data;
          this.editMode = false;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    destroy() {
      if (confirm(this.$t('contact.goals_delete_confirm'))) {
        axios
          .delete(this.localGoal.url.destroy)
          .then((response) => {
            localStorage.success = this.$t('contact.goals_delete_success');
            this.$inertia.visit(response.data.data);
          })
          .catch((error) => {
            this.form.errors = error.response.data;
          });
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.grid-calendar {
  grid-template-columns: repeat(53, 1fr);

  & > div {
    grid-column: 2px;

    & > div {
      margin-bottom: 2px;
    }
  }
}

.special-grid {
  grid-template-columns: 100px 1fr;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}
</style>
