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

<template>
  <div>
    <div class="border-b mb-4 pb-3 sm:flex items-center justify-between sm:mt-0 mt-8">
      <h3>
        Modules in this page
      </h3>
      <pretty-button v-if="!addModuleModalShown && moduleLoaded" :text="'Add a module'" :icon="'plus'" @click="showModuleModal" />
      <pretty-button v-if="addModuleModalShown && moduleLoaded" :text="'Cancel'" @click="addModuleModalShown = false" />
    </div>

    <!-- list of all the existing modules -->
    <ul v-if="addModuleModalShown" class="bg-white border border-gray-200 rounded-lg mb-6">
      <li class="text-sm border-b border-gray-200 bg-slate-50 pl-2 pr-5 py-2 item-list">Available modules:</li>
      <li v-for="module in localAllModules" :key="module.id" class="border-b border-gray-200 hover:bg-slate-50 item-list flex items-center pl-2 pr-5 py-2 justify-between">
        <span>{{ module.name }}</span>
        <span v-if="!module.already_used" class="cursor-pointer inline text-sky-500 hover:text-blue-900" @click="add(module)">Add</span>
        <span v-if="module.already_used" class="text-xs"><span class="mr-1">✅</span> Already in use on this page</span>
      </li>
    </ul>

    <!-- list of modules -->
    <ul v-if="localPageModules.length > 0" class="bg-white border border-gray-200 rounded-lg mb-6">
      <draggable
        :list="localPageModules"
        item-key="id"
        :component-data="{name:'fade'}"
        handle=".handle"
        @change="updatePosition"
      >
        <template #item="{ element }">
          <div class="border-b border-gray-200 hover:bg-slate-50 item-list flex items-center pl-2 pr-5 py-2">
            <!-- anchor to move module -->
            <div class="mr-2">
              <svg class="cursor-move handle" width="24" height="24" viewBox="0 0 24 24" fill="none"
                   xmlns="http://www.w3.org/2000/svg"
              >
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
            <div class="flex justify-between items-center w-full">
              <span>{{ element.name }}</span>

              <!-- actions -->
              <ul class="text-sm">
                <li class="cursor-pointer inline text-red-500 hover:text-red-900" @click="remove(element)">Remove</li>
              </ul>
            </div>
          </div>
        </template>
      </draggable>
    </ul>

    <!-- blank state -->
    <div v-if="localPageModules.length == 0 && moduleLoaded">
      <p class="p-5 text-center bg-white border border-gray-200 rounded-lg">Create at least one page to display contact's data.</p>
    </div>

    <!-- no page selected -->
    <div v-if="!moduleLoaded">
      <p class="p-5 text-center bg-white border border-gray-200 rounded-lg">Please select a page on the left to load modules.</p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/PrettyButton';
import draggable from 'vuedraggable';

export default {
  components: {
    PrettyButton,
    draggable,
  },

  props: {
    data: {
      type: Array,
      default: () => [],
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

      axios.post(this.data.url.store, this.form)
        .then(response => {
          this.flash('The module has been added', 'success');
          this.localPageModules.unshift(response.data.data);
          this.addModuleModalShown = false;
          this.localAllModules[this.localAllModules.findIndex(x => x.id === module.id)].already_used = true;
        })
        .catch(error => {
          this.form.errors = error.response.data;
        });
    },

    remove(module) {
      axios.delete(module.url.destroy)
        .then(response => {
          this.flash('The module has been added', 'success');
          this.localAllModules[this.localAllModules.findIndex(x => x.id === module.id)].already_used = false;

          var id = this.localPageModules.findIndex(x => x.id === module.id);
          this.localPageModules.splice(id, 1);
        })
        .catch(error => {
          this.form.errors = error.response.data;
        });
    },

    updatePosition(event) {
      // the event object comes from the draggable component
      this.form.position = event.moved.newIndex + 1;

      axios.post(event.moved.element.url.position, this.form)
        .then(response => {
          this.flash('The order has been saved', 'success');
        })
        .catch(error => {
          this.loadingState = null;
          this.errors = error.response.data;
        });
    },
  },
};
</script>
