<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

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
              d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
          </svg>
        </span>

        <span class="font-semibold">Groups</span>
      </div>
      <pretty-span @click="addGroupMode = true" :text="'Add to group'" :icon="'plus'" :classes="'sm:w-fit w-full'" />
    </div>

    <form v-if="addGroupMode" class="bg-form mb-6 rounded-lg border border-gray-200" @submit.prevent="submit()">
      <div class="border-b border-gray-200">
        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <div class="border-b border-gray-200 p-5">
          <!-- group type -->
          <dropdown
            v-model="form.group_id"
            :data="localAvailableGroups"
            :required="true"
            :placeholder="$t('app.choose_value')"
            :dropdown-class="'block w-full'"
            @change="toggleCreateGroup()"
            :label="'Select a group or create a new one'" />
        </div>

        <!-- name -->
        <div v-if="chooseGroupTypeShown" class="border-b border-gray-200 p-5">
          <text-input
            :ref="'newName'"
            v-model="form.name"
            :label="'Name'"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :autocomplete="false"
            :maxlength="255"
            :required="true"
            @esc-key-pressed="addPetModalShown = false" />
        </div>

        <div v-if="chooseGroupTypeShown" class="border-b border-gray-200 p-5">
          <!-- group type -->
          <dropdown
            v-model="form.group_type_id"
            :data="data.group_types"
            :required="true"
            :placeholder="$t('app.choose_value')"
            :dropdown-class="'block w-full'"
            :label="'Group type'"
            @change="loadGroupTypeRoles()" />
        </div>

        <div v-if="localGroupTypeRoles.length > 0 || chooseGroupTypeRoleShown" class="p-5">
          <!-- group role -->
          <dropdown
            v-model="form.group_type_role_id"
            :data="localGroupTypeRoles"
            :required="false"
            :placeholder="$t('app.choose_value')"
            :dropdown-class="'block w-full'"
            :label="'Role'" />
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="addGroupMode = false" />
        <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
      </div>
    </form>

    <!-- groups -->
    <ul v-if="filteredGroups.length > 0" class="mb-4 rounded-lg border border-gray-200 last:mb-0">
      <li
        v-for="group in filteredGroups"
        :key="group.id"
        class="item-list flex items-center justify-between border-b border-gray-200 px-5 py-2 hover:bg-slate-50">
        <div>
          <p>{{ group.name }}</p>

          <div v-if="group.contacts" class="relative flex -space-x-2 overflow-hidden py-1">
            <div v-for="contact in group.contacts" :key="contact.id" class="inline-block">
              <inertia-link :href="contact.url.show"
                ><div v-html="contact.avatar" class="h-8 w-8 rounded-full ring-2 ring-white"></div
              ></inertia-link>
            </div>
          </div>
        </div>

        <!-- actions -->
        <ul class="text-sm">
          <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(group)">Leave</li>
        </ul>
      </li>
    </ul>

    <!-- blank state -->
    <div v-if="localGroups.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">The contact does not belong to any group yet.</p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Dropdown from '@/Shared/Form/Dropdown';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    PrettyButton,
    PrettySpan,
    TextInput,
    Dropdown,
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
      loadingState: null,
      localGroups: [],
      localAvailableGroups: [],
      localGroupTypeRoles: [],
      addGroupMode: false,
      nameInputShown: false,
      chooseGroupTypeShown: false,
      chooseGroupTypeRoleShown: false,
      form: {
        search: '',
        group_id: -1,
        group_type_id: -1,
        group_type_role_id: 0,
        name: '',
        errors: [],
      },
    };
  },

  created() {
    this.localGroups = this.data.groups;
    this.localAvailableGroups = this.data.available_groups;
  },

  computed: {
    filteredGroups() {
      return this.localGroups.filter((group) => {
        return group.id > 0;
      });
    },
  },

  methods: {
    toggleCreateGroup() {
      this.form.name = '';

      if (this.form.group_id == 0) {
        this.createAddGroupModal();
      } else {
        this.chooseExistingGroupModal();
      }
    },

    createAddGroupModal() {
      this.nameInputShown = true;
      this.chooseGroupTypeShown = true;
      this.chooseGroupTypeRoleShown = false;
    },

    chooseExistingGroupModal() {
      this.nameInputShown = false;
      this.chooseGroupTypeShown = false;
      this.chooseGroupTypeRoleShown = true;

      // we need to load the list of existing roles for this group
      var id = this.localAvailableGroups.findIndex((x) => x.id == this.form.group_id);
      this.form.group_type_id = this.localAvailableGroups[id].type.id;
      this.loadGroupTypeRoles();
    },

    loadGroupTypeRoles() {
      for (let i = 0; i < this.data.group_types.length; i++) {
        if (this.data.group_types[i].id == this.form.group_type_id) {
          this.localGroupTypeRoles = this.data.group_types[i].roles;
        }
      }
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash('The group has been added', 'success');
          this.localGroups.unshift(response.data.data);
          this.loadingState = null;
          this.addGroupMode = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(group) {
      if (confirm('Are you sure?')) {
        axios
          .delete(group.url.destroy)
          .then((response) => {
            this.flash('The contact has been removed from the group', 'success');
            var id = this.localGroups.findIndex((x) => x.id === group.id);
            this.localGroups.splice(id, 1);
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
