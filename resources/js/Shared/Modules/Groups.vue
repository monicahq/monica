<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <Handshake class="h-4 w-4 text-gray-600" />

        <span class="font-semibold"> {{ $t('Groups') }} </span>
      </div>
      <pretty-button
        :text="$t('Add to group')"
        :icon="'plus'"
        :class="'w-full sm:w-fit'"
        @click="addGroupMode = true" />
    </div>

    <form
      v-if="addGroupMode"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <!-- group type -->
          <dropdown
            v-model.number="form.group_id"
            :data="localAvailableGroups"
            :required="true"
            :placeholder="$t('Choose a value')"
            :dropdown-class="'block w-full'"
            :label="$t('Select a group or create a new one')"
            @change="toggleCreateGroup()" />
        </div>

        <!-- name -->
        <div v-if="chooseGroupTypeShown" class="border-b border-gray-200 p-5 dark:border-gray-700">
          <text-input
            ref="newName"
            v-model="form.name"
            :label="$t('Name')"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :autocomplete="false"
            :maxlength="255"
            :required="true"
            @esc-key-pressed="addPetModalShown = false" />
        </div>

        <div v-if="chooseGroupTypeShown" class="border-b border-gray-200 p-5 dark:border-gray-700">
          <!-- group type -->
          <dropdown
            v-model.number="form.group_type_id"
            :data="data.group_types"
            :placeholder="$t('Choose a value')"
            :dropdown-class="'block w-full'"
            :label="$t('Group type')"
            @change="loadGroupTypeRoles()" />
        </div>

        <div v-if="localGroupTypeRoles.length > 0 || chooseGroupTypeRoleShown" class="p-5">
          <!-- group role -->
          <dropdown
            v-model.number="form.group_type_role_id"
            :data="localGroupTypeRoles"
            :required="false"
            :placeholder="$t('Choose a value')"
            :dropdown-class="'block w-full'"
            :label="$t('Role')" />
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="addGroupMode = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- groups -->
    <ul v-if="filteredGroups.length > 0" class="mb-4 rounded-lg border border-gray-200 last:mb-0 dark:border-gray-700">
      <li
        v-for="group in filteredGroups"
        :key="group.id"
        class="item-list flex items-center justify-between border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
        <div>
          <p class="font-semibold">{{ group.name }}</p>

          <div v-if="group.contacts" class="relative flex -space-x-2 overflow-hidden py-1">
            <div v-for="contact in group.contacts" :key="contact.id" class="inline-block">
              <InertiaLink :href="contact.url.show">
                <avatar :data="contact.avatar" :class="'h-8 w-8 rounded-full ring-2 ring-white'" />
              </InertiaLink>
            </div>
          </div>
        </div>

        <!-- actions -->
        <ul class="text-sm">
          <li class="me-4 inline cursor-pointer">
            <InertiaLink :href="group.url.show" class="text-blue-500 hover:underline">{{ $t('Show') }}</InertiaLink>
          </li>
          <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(group)">
            {{ $t('Leave') }}
          </li>
        </ul>
      </li>
    </ul>

    <!-- blank state -->
    <div
      v-if="localGroups.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_group.svg" :alt="$t('Groups')" class="mx-auto mt-4 h-14 w-14" />
      <p class="px-5 pb-5 pt-2 text-center">{{ $t('The contact does not belong to any group yet.') }}</p>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Avatar from '@/Shared/Avatar.vue';
import { Handshake } from 'lucide-vue-next';

export default {
  components: {
    InertiaLink: Link,
    PrettyButton,
    PrettySpan,
    TextInput,
    Dropdown,
    Errors,
    Avatar,
    Handshake,
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
        group_type_id: '',
        group_type_role_id: 0,
        name: '',
        errors: [],
      },
    };
  },

  computed: {
    filteredGroups() {
      return this.localGroups.filter((group) => {
        return group.id > 0;
      });
    },
  },

  created() {
    this.localGroups = this.data.groups;
    this.localAvailableGroups = this.data.available_groups;
  },

  methods: {
    toggleCreateGroup() {
      this.form.name = '';

      if (this.form.group_id === 0) {
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
      var id = this.localAvailableGroups.findIndex((x) => x.id === this.form.group_id);
      this.form.group_type_id = this.localAvailableGroups[id].type.id;
      this.loadGroupTypeRoles();
    },

    loadGroupTypeRoles() {
      for (let i = 0; i < this.data.group_types.length; i++) {
        if (this.data.group_types[i].id === this.form.group_type_id) {
          this.localGroupTypeRoles = this.data.group_types[i].roles;
        }
      }
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The group has been added'), 'success');
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
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(group.url.destroy)
          .then(() => {
            this.flash(this.$t('The contact has been removed from the group'), 'success');
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
