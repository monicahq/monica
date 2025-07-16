<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <InertiaLink :href="layoutData.vault.url.contacts" class="text-blue-500 hover:underline">
                {{ $t('Contacts') }}
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
              <InertiaLink :href="data.url.contact" class="text-blue-500 hover:underline">
                {{ $t('Profile of :name', { name: data.contact.name }) }}
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
              {{ $t('Detail of a goal') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-18 relative">
      <div class="mx-auto max-w-5xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div>
            <div @click="showEditModal()" class="cursor-pointer text-blue-500 hover:underline">
              {{ $t('Rename') }}
            </div>
            <div @click="destroy()" class="cursor-pointer text-blue-500 hover:underline">{{ $t('Delete') }}</div>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <h1 v-if="!editMode" class="mb-4 font-semibold">{{ localGoal.name }}</h1>

            <!-- edit modal form -->
            <form
              v-if="editMode"
              class="mb-4 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
              @submit.prevent="update()">
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <errors :errors="form.errors" />

                <!-- name -->
                <text-input
                  ref="newName"
                  v-model="form.name"
                  :label="$t('Name')"
                  :type="'text'"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="editMode = false" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editMode = false" />
                <pretty-button :text="$t('Update')" :state="loadingState" :icon="'check'" :class="'save'" />
              </div>
            </form>

            <!-- stats -->
            <div class="mb-6 flex justify-between rounded-xs border border-gray-200 p-3 dark:border-gray-700">
              <div class="me-6 flex items-center">
                <div class="me-3 w-14 text-right text-sm text-gray-500">{{ $t('Total streaks') }}</div>
                <div class="text-4xl">
                  {{ localGoal.count }}
                </div>
              </div>
              <div class="me-6 flex items-center">
                <div class="me-3 w-14 text-right text-sm text-gray-500">{{ $t('Current streak') }}</div>
                <div class="text-4xl">
                  {{ localGoal.streaks_statistics.current_streak }}
                </div>
              </div>
              <div class="flex items-center">
                <div class="me-3 w-14 text-right text-sm text-gray-500">{{ $t('Longest streak') }}</div>
                <div class="text-4xl">
                  {{ localGoal.streaks_statistics.max_streak }}
                </div>
              </div>
            </div>

            <!-- details -->
            <p class="mb-2 text-xs">{{ $t('Details in the last year') }}</p>
            <div class="grid-calendar grid">
              <div v-for="week in localGoal.weeks" :key="week.id">
                <div v-for="streak in week.streaks" :key="streak.id">
                  <a-tooltip placement="topLeft" :title="streak.date" arrow-point-at-center>
                    <!-- there is a streak for this day -->
                    <div
                      v-if="!streak.not_yet_happened && streak.streak"
                      class="h-3 w-3 cursor-pointer rounded-xs border border-transparent bg-green-400 hover:border-green-500"></div>

                    <!-- there is not a streak for this day -->
                    <div
                      v-if="!streak.not_yet_happened && !streak.streak"
                      class="h-3 w-3 cursor-pointer rounded-xs bg-slate-200"></div>
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
import { Link } from '@inertiajs/vue3';
import { Tooltip as ATooltip } from 'ant-design-vue';
import Layout from '@/Layouts/Layout.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';

export default {
  components: {
    InertiaLink: Link,
    ATooltip,
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

      this.$nextTick().then(() => {
        this.$refs.newName.focus();
      });
    },

    update() {
      axios
        .put(this.localGoal.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The goal has been updated'), 'success');
          this.localGoal = response.data.data;
          this.editMode = false;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    destroy() {
      if (confirm(this.$t('Are you sure? This will delete the goal and all the streaks permanently.'))) {
        axios
          .delete(this.localGoal.url.destroy)
          .then((response) => {
            localStorage.success = this.$t('The goal has been deleted');
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
