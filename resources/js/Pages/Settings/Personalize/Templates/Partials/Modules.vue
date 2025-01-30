<template>
  <div>
    <div class="mb-4 mt-8 items-center justify-between border-b pb-3 sm:mt-0 sm:flex">
      <h3>{{ $t('Modules in this page') }}</h3>

      <!-- add module -->
      <pretty-button
        v-if="!addModuleModalShown && moduleLoaded"
        :text="$t('Add a module')"
        :icon="'plus'"
        @click="showModuleModal" />

      <!-- cancel button -->
      <pretty-button
        v-if="addModuleModalShown && moduleLoaded"
        :text="$t('Cancel')"
        @click="addModuleModalShown = false" />
    </div>

    <!-- list of all the existing modules -->
    <ul
      v-if="addModuleModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <li
        class="item-list border-b border-gray-200 bg-slate-50 py-2 pe-5 ps-2 text-sm dark:border-gray-700 dark:bg-slate-900">
        {{ $t('Available modules:') }}
      </li>
      <li
        v-for="module in localAllModules"
        :key="module.id"
        class="item-list flex items-center justify-between border-b border-gray-200 py-2 pe-5 ps-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
        <span>{{ module.name }}</span>
        <span
          v-if="!module.already_used"
          class="inline cursor-pointer text-blue-500 hover:underline"
          @click="add(module)"
          >{{ $t('Add') }}</span
        >
        <span v-if="module.already_used" class="text-xs"
          ><span class="me-1">✅</span> {{ $t('Already used on this page') }}</span
        >
      </li>
    </ul>

    <!-- list of modules -->
    <ul
      v-if="localPageModules.length > 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <draggable
        :list="localPageModules"
        item-key="id"
        :component-data="{ name: 'fade' }"
        handle=".handle"
        @change="updatePosition">
        <template #item="{ element }">
          <div
            class="item-list flex items-center border-b border-gray-200 py-2 pe-5 ps-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
            <!-- anchor to move module -->
            <div class="me-2">
              <svg
                class="handle cursor-move"
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
            </div>

            <!-- detail of the module -->
            <div class="flex w-full items-center justify-between">
              <span>{{ element.name }}</span>

              <!-- actions -->
              <ul class="text-sm">
                <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="remove(element)">
                  {{ $t('Remove') }}
                </li>
              </ul>
            </div>
          </div>
        </template>
      </draggable>
    </ul>

    <!-- blank state -->
    <div v-if="localPageModules.length === 0 && moduleLoaded">
      <p class="rounded-lg border border-gray-200 bg-white p-5 text-center dark:border-gray-700 dark:bg-gray-900">
        {{ $t('Add at least one module.') }}
      </p>
    </div>

    <!-- no page selected -->
    <div v-if="!moduleLoaded">
      <p class="rounded-lg border border-gray-200 bg-white p-5 text-center dark:border-gray-700 dark:bg-gray-900">
        {{ $t('Please select a page on the left to load modules.') }}
      </p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import draggable from 'vuedraggable';

export default {
  components: {
    PrettyButton,
    draggable,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      showModuleList: false,
      moduleLoaded: false,
      allModules: [],
      localAllModules: [],
      localPageModules: [],
      addModuleModalShown: false,
      drag: false,
      form: {
        position: '',
        module_id: 0,
        errors: [],
      },
    };
  },

  watch: {
    data(data) {
      this.moduleLoaded = true;
      this.addModuleModalShown = false;
      this.localAllModules = data.modules_in_account;
      this.localPageModules = data.modules;
    },
  },

  methods: {
    showModuleModal() {
      this.addModuleModalShown = true;
    },

    add(module) {
      this.form.module_id = module.id;

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The module has been added'), 'success');
          this.localPageModules.unshift(response.data.data);
          this.addModuleModalShown = false;
          this.localAllModules[this.localAllModules.findIndex((x) => x.id === module.id)].already_used = true;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    remove(module) {
      axios
        .delete(module.url.destroy)
        .then(() => {
          this.flash(this.$t('The module has been removed'), 'success');
          this.localAllModules[this.localAllModules.findIndex((x) => x.id === module.id)].already_used = false;

          var id = this.localPageModules.findIndex((x) => x.id === module.id);
          this.localPageModules.splice(id, 1);
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
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
  },
};
</script>

<style lang="scss" scoped>
.item-list {
  &:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

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
