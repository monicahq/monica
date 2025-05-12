<template>
  <div class="mb-12">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0">
        <span class="me-1"> 🐱 </span>
        {{ $t('Users') }}
      </h3>
      <pretty-span v-if="!addUserModalShown" :text="$t('Add a user')" :icon="'plus'" @click="showAddUserModal" />
      <pretty-span
        v-if="addUserModalShown && localUsersInAccount.length === 0"
        :text="$t('Cancel')"
        @click="addUserModalShown = false" />
    </div>

    <!-- modal to add a user -->
    <form
      v-if="addUserModalShown && localUsersInAccount.length > 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="store()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <!-- list of potential new users -->
        <p class="mb-2">
          {{ $t('Who should we invite in this vault?') }}
        </p>

        <div v-for="user in localUsersInAccount" :key="user.id" class="dropdown-list mb-2 flex items-center">
          <input
            :id="'user' + user.id"
            v-model="form.user_id"
            :value="user.id"
            name="user"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
          <label
            :for="'user' + user.id"
            class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ user.name }}
          </label>
        </div>
      </div>

      <!-- permissions -->
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <!-- role types -->
        <div>
          <p class="mb-2">
            {{ $t('What permission should the user have?') }}
          </p>

          <!-- view -->
          <div class="mb-2 flex items-center">
            <input
              id="viewer"
              v-model="form.permission"
              value="300"
              name="permission"
              type="radio"
              class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
            <label for="viewer" class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('Viewer') }}
              <span class="ms-4 font-normal text-gray-500">
                {{ $t('Can view data, but can’t edit it.') }}
              </span>
            </label>
          </div>

          <!-- editor -->
          <div class="mb-2 flex items-center">
            <input
              id="editor"
              v-model="form.permission"
              value="200"
              name="permission"
              type="radio"
              class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
            <label for="editor" class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('Editor') }}
              <span class="ms-4 font-normal text-gray-500">
                {{ $t('Can edit data, but can’t manage the vault.') }}
              </span>
            </label>
          </div>

          <!-- manager -->
          <div class="flex items-center">
            <input
              id="manager"
              v-model="form.permission"
              value="100"
              name="permission"
              type="radio"
              class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
            <label for="manager" class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('Manager') }}
              <span class="ms-4 font-normal text-gray-500">
                {{ $t('Can do everything, including adding or removing other users.') }}
              </span>
            </label>
          </div>
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" @click="addUserModalShown = false" />
        <pretty-button :text="$t('Add')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- blank state -->
    <div
      v-if="addUserModalShown && localUsersInAccount.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white p-5 text-center dark:border-gray-700 dark:bg-gray-900">
      <p>{{ $t('There are no other users in this account.') }}</p>
    </div>

    <!-- list of existing users -->
    <div class="mb-6 rounded-xs border border-gray-200 text-sm dark:border-gray-700">
      <ul v-if="localUsersInVault.length > 0" class="rounded-b rounded-t bg-white dark:bg-gray-900">
        <li
          v-for="user in localUsersInVault"
          :key="user.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <div v-if="editedUser.id !== user.id" class="flex items-center justify-between px-5 py-2">
            <span>{{ user.name }}</span>

            <!-- actions -->
            <ul v-if="user.id !== layoutData.user.id" class="text-sm">
              <li class="me-4 inline cursor-pointer" @click="showChangePermissionModal(user)">
                <span class="text-blue-500 hover:underline">{{ $t('Change permission') }}</span>
              </li>
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(user)">
                {{ $t('Remove') }}
              </li>
            </ul>
          </div>

          <!-- change permission modal -->
          <form v-if="editedUser.id === user.id" class="bg-white dark:bg-gray-900" @submit.prevent="update(user)">
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <errors :errors="form.errors" />

              <p class="mb-2">
                {{ $t('Permission for :name', { name: user.name }) }}
              </p>

              <!-- view -->
              <div class="mb-2 flex items-center">
                <input
                  id="viewer"
                  v-model="form.permission"
                  value="300"
                  name="permission"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                {{ $t('Viewer') }}
                <span class="ms-4 font-normal text-gray-500">
                  {{ $t('Can view data, but can’t edit it.') }}
                </span>
              </div>

              <!-- editor -->
              <div class="mb-2 flex items-center">
                <input
                  id="editor"
                  v-model="form.permission"
                  value="200"
                  name="permission"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                <label
                  for="editor"
                  class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t('Editor') }}
                  <span class="ms-4 font-normal text-gray-500">
                    {{ $t('Can edit data, but can’t manage the vault.') }}
                  </span>
                </label>
              </div>

              <!-- manager -->
              <div class="flex items-center">
                <input
                  id="manager"
                  v-model="form.permission"
                  value="100"
                  name="permission"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                <label
                  for="manager"
                  class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t('Manager') }}
                  <span class="ms-4 font-normal text-gray-500">
                    {{ $t('Can do everything, including adding or removing other users.') }}
                  </span>
                </label>
              </div>
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('Cancel')" @click="editedUser = []" />
              <pretty-button :text="$t('Change permission')" :state="loadingState" :icon="'check'" :class="'save'" />
            </div>
          </form>
        </li>
      </ul>

      <!-- blank state -->
      <div v-if="localUsersInVault.length === 0">
        <p class="p-5 text-center">
          {{ $t('There are no other users in this account.') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import Errors from '@/Shared/Form/Errors.vue';

export default {
  components: {
    PrettySpan,
    PrettyButton,
    Errors,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
    layoutData: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      localUsersInVault: [],
      localUsersInAccount: [],
      addUserModalShown: false,
      loadingState: '',
      editedUser: [],
      form: {
        permission: null,
        user_id: 0,
        errors: [],
      },
    };
  },

  mounted() {
    this.localUsersInVault = this.data.users_in_vault;
    this.localUsersInAccount = this.data.users_in_account;
  },

  methods: {
    showAddUserModal() {
      this.form.errors = [];
      this.form.permission = null;
      this.form.user_id = 0;
      this.addUserModalShown = true;
    },

    showChangePermissionModal(user) {
      this.form.errors = [];
      this.form.permission = user.permission;
      this.editedUser = user;
    },

    store() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.user_store, this.form)
        .then((response) => {
          this.flash(this.$t('The user has been added'), 'success');
          this.loadingState = '';

          // add user in the list of users in the vault
          this.localUsersInVault.unshift(response.data.data);

          // remove user from the list of users in the account
          var id = this.localUsersInAccount.findIndex((x) => x.id === this.form.user_id);
          this.localUsersInAccount.splice(id, 1);

          this.addUserModalShown = false;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
          this.loadingState = '';
        });
    },

    update(user) {
      this.loadingState = 'loading';

      axios
        .put(user.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The user has been updated'), 'success');
          var id = this.localUsersInVault.findIndex((x) => x.id === user.id);
          this.localUsersInVault[id] = response.data.data;
          this.loadingState = '';
          this.editedUser = [];
        })
        .catch((error) => {
          this.form.errors = error.response.data;
          this.loadingState = '';
        });
    },

    destroy(user) {
      axios
        .delete(user.url.destroy)
        .then(() => {
          this.flash(this.$t('The user has been removed'), 'success');

          // remove the user from the list of users in the vault
          var id = this.localUsersInVault.findIndex((x) => x.id === user.id);
          this.localUsersInVault.splice(id, 1);

          // add the user back to the list of users in the account
          this.localUsersInAccount.unshift(user);
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
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
.dropdown-list {
  &:last-child {
    margin-bottom: 0;
  }
}
</style>
