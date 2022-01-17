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

<template>
  <div class="mb-12">
    <!-- title + cta -->
    <div class="sm:flex items-center justify-between mb-3 sm:mt-0 mt-8">
      <h3 class="mb-4 sm:mb-0">
        <span class="mr-1">
          🐱
        </span> Users
      </h3>
      <pretty-span v-if="!addUserModalShown" :text="'Add a user'" :icon="'plus'" @click="showAddUserModal" />
      <pretty-span v-if="addUserModalShown && localUsersInAccount.length == 0" :text="'Cancel'" @click="addUserModalShown = false" />
    </div>

    <!-- modal to add a new user -->
    <form v-if="addUserModalShown && localUsersInAccount.length > 0" class="bg-white border border-gray-200 rounded-lg mb-6" @submit.prevent="store()">
      <div class="p-5 border-b border-gray-200">
        <errors :errors="form.errors" />

        <!-- list of potential new users -->
        <p class="mb-2">Who should we invite in this vault?</p>

        <div v-for="user in localUsersInAccount" :key="user.id" class="flex items-center mb-2 dropdown-list">
          <input :id="'user' + user.id" v-model="form.user_id" :value="user.id" name="user" type="radio"
                 class="h-4 w-4 text-sky-500 border-gray-300"
          >
          <label :for="'user' + user.id" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
            {{ user.name }}
          </label>
        </div>
      </div>

      <div class="p-5 border-b border-gray-200">
        <!-- role types -->
        <div>
          <p class="mb-2">What permission should the user have?</p>

          <!-- view -->
          <div class="flex items-center mb-2">
            <input id="viewer" v-model="form.permission" value="300" name="permission" type="radio"
                   class="h-4 w-4 text-sky-500 border-gray-300"
            >
            <label for="viewer" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
              Viewer <span class="text-gray-500 font-normal ml-4">
                Can view data, but can't edit it.
              </span>
            </label>
          </div>

          <!-- editor -->
          <div class="flex items-center mb-2">
            <input id="editor" v-model="form.permission" value="200" name="permission" type="radio"
                   class="h-4 w-4 text-sky-500 border-gray-300"
            >
            <label for="editor" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
              Editor <span class="text-gray-500 font-normal ml-4">
                Can edit data, but can't manage the vault.
              </span>
            </label>
          </div>

          <!-- manager -->
          <div class="flex items-center">
            <input id="manager" v-model="form.permission" value="100" name="permission" type="radio"
                   class="h-4 w-4 text-sky-500 border-gray-300"
            >
            <label for="manager" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
              Manager <span class="text-gray-500 font-normal ml-4">
                Can do everything, including adding or removing other users.
              </span>
            </label>
          </div>
        </div>
      </div>

      <div class="p-5 flex justify-between">
        <pretty-span :text="'Cancel'" @click="addUserModalShown = false" />
        <pretty-button :text="'Add user'" :state="loadingState" :icon="'plus'" :classes="'save'" />
      </div>
    </form>

    <!-- blank state -->
    <div v-if="addUserModalShown && localUsersInAccount.length == 0" class="p-5 text-center bg-white border border-gray-200 rounded-lg mb-6">
      <p>There are no more users in this account.</p>
    </div>

    <!-- list of existing users -->
    <div class="border rounded mb-6 text-sm">
      <ul v-if="localUsersInVault.length > 0" class="bg-white rounded-b rounded-t">
        <li v-for="user in localUsersInVault" :key="user.id" class="border-b border-gray-200 hover:bg-slate-50 item-list">
          <div v-if="editedUser.id != user.id" class="flex justify-between items-center px-5 py-2">
            <span>{{ user.name }}</span>

            <!-- actions -->
            <ul v-if="user.id != layoutData.user.id" class="text-sm">
              <li class="mr-4 cursor-pointer inline text-sky-500 hover:text-blue-900" @click="showChangePermissionModal(user)">Change permission</li>
              <li class="cursor-pointer inline text-red-500 hover:text-red-900" @click="destroy(user)">Remove</li>
            </ul>
          </div>

          <!-- change permission modal -->
          <form v-if="editedUser.id == user.id" class="bg-white" @submit.prevent="update(user)">
            <div class="p-5 border-b border-gray-200">
              <errors :errors="form.errors" />

              <p class="mb-2">Permission for {{ user.name }}</p>

              <!-- view -->
              <div class="flex items-center mb-2">
                <input id="viewer" v-model="form.permission" value="300" name="permission" type="radio"
                       class="h-4 w-4 text-sky-500 border-gray-300"
                >
                <label for="viewer" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                  Viewer <span class="text-gray-500 font-normal ml-4">
                    Can view data, but can't edit it.
                  </span>
                </label>
              </div>

              <!-- editor -->
              <div class="flex items-center mb-2">
                <input id="editor" v-model="form.permission" value="200" name="permission" type="radio"
                       class="h-4 w-4 text-sky-500 border-gray-300"
                >
                <label for="editor" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                  Editor <span class="text-gray-500 font-normal ml-4">
                    Can edit data, but can't manage the vault.
                  </span>
                </label>
              </div>

              <!-- manager -->
              <div class="flex items-center">
                <input id="manager" v-model="form.permission" value="100" name="permission" type="radio"
                       class="h-4 w-4 text-sky-500 border-gray-300"
                >
                <label for="manager" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                  Manager <span class="text-gray-500 font-normal ml-4">
                    Can do everything, including adding or removing other users.
                  </span>
                </label>
              </div>
            </div>

            <div class="p-5 flex justify-between">
              <pretty-span :text="'Cancel'" @click="editedUser = []" />
              <pretty-button :text="'Change permission'" :state="loadingState" :icon="'check'" :classes="'save'" />
            </div>
          </form>
        </li>
      </ul>

      <!-- blank state -->
      <div v-if="localUsersInVault.length == 0">
        <p class="p-5 text-center">There are no templates in the account. Go to the account settings to create one.</p>
      </div>
    </div>
  </div>
</template>

<script>
import PrettySpan from '@/Shared/PrettySpan';
import PrettyButton from '@/Shared/PrettyButton';
import Errors from '@/Shared/Errors';

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

      axios.post(this.data.url.user_store, this.form)
        .then(response => {
          this.flash('The user has been added', 'success');
          this.loadingState = '';

          // add user in the list of users in the vault
          this.localUsersInVault.unshift(response.data.data);

          // remove user from the list of users in the account
          var id = this.localUsersInAccount.findIndex(x => x.id === this.form.user_id);
          this.localUsersInAccount.splice(id, 1);

          this.addUserModalShown = false;
        })
        .catch(error => {
          this.form.errors = error.response.data;
          this.loadingState = '';
        });
    },

    update(user) {
      this.loadingState = 'loading';

      axios.put(user.url.update, this.form)
        .then(response => {
          this.flash('The user has been updated', 'success');
          var id = this.localUsersInVault.findIndex(x => x.id === user.id);
          this.localUsersInVault[id] = response.data.data;
          this.loadingState = '';
          this.editedUser = [];
        })
        .catch(error => {
          this.form.errors = error.response.data;
          this.loadingState = '';
        });
    },

    destroy(user) {
      axios.delete(user.url.destroy)
        .then(response => {
          this.flash('The user has been removed', 'success');

          // remove the user from the list of users in the vault
          var id = this.localUsersInVault.findIndex(x => x.id === user.id);
          this.localUsersInVault.splice(id, 1);

          // add the user back to the list of users in the account
          this.localUsersInAccount.unshift(user);
        })
        .catch(error => {
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>
