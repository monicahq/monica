<style lang="scss" scoped>
.user-list {
  li:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  li:last-child {
    border-bottom: 0;
  }

  li:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }

  .icon-mail {
    top: -1px;
  }
}
</style>

<template>
  <layout title="Dashboard" :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600">You are here:</li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings.index" class="text-sky-500 hover:text-blue-900">
                Settings
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
            <li class="inline">Users</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 flex items-center justify-between">
          <h3><span class="mr-1"> 🥸 </span> All users in this account</h3>
          <pretty-link :href="data.url.users.create" :text="'Invite a new user'" :icon="'plus'" />
        </div>

        <!-- list of users -->
        <ul class="user-list mb-6 rounded-lg border border-gray-200 bg-white">
          <li v-for="user in data.users" :key="user.id" class="border-b border-gray-200 hover:bg-slate-50">
            <!-- case: user has been invited -->
            <div v-if="!user.name" class="flex items-center justify-between px-5 py-2">
              <div>
                <!-- email -->
                <span class="block">
                  {{ user.email }}

                  <!-- is administrator -->
                  <span
                    v-if="user.is_account_administrator"
                    class="ml-2 rounded bg-neutral-200 px-2 py-1 text-xs text-neutral-500"
                    >administrator</span
                  >
                </span>

                <!-- case : invitation sent -->
                <span class="relative text-sm text-gray-400">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="icon-mail relative inline h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>

                  Invitation sent
                </span>
              </div>
            </div>

            <!-- case: normal user -->
            <div v-if="user.name && editModalshownId != user.id" class="flex items-center justify-between px-5 py-2">
              <div>
                <span class="block">
                  {{ user.name }}
                  <!-- is administrator -->
                  <span
                    v-if="user.is_account_administrator"
                    class="ml-2 rounded bg-neutral-200 px-2 py-1 text-xs text-neutral-500"
                    >administrator</span
                  >
                </span>

                <span class="text-sm text-gray-400">{{ user.email }}</span>
              </div>

              <!-- actions -->
              <ul v-if="!user.is_logged_user" class="text-sm">
                <li @click="showEditModal(user)" class="mr-4 inline cursor-pointer text-sky-500 hover:text-blue-900">
                  Edit
                </li>
                <li @click="destroy(user)" class="inline cursor-pointer text-red-500 hover:text-red-900">Delete</li>
              </ul>
            </div>

            <!-- edit user -->
            <form v-if="editModalshownId == user.id" @submit.prevent="update(user)">
              <div class="border-b border-gray-200 p-5">
                <errors :errors="form.errors" />

                <p class="mb-2 block text-sm">What permission should {{ user.name }} have?</p>
                <div class="mb-2 flex items-start">
                  <input
                    id="viewer"
                    v-model="form.administrator"
                    value="false"
                    name="permission"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500" />
                  <label for="viewer" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                    Regular user
                  </label>
                </div>

                <!-- administrator -->
                <div class="flex items-start">
                  <input
                    id="manager"
                    v-model="form.administrator"
                    value="true"
                    name="permission"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500" />
                  <label for="manager" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                    Administrator
                    <span class="ml-4 font-normal text-gray-500">
                      Can do everything, including adding or removing other users, managing billing and closing the
                      account.
                    </span>
                  </label>
                </div>
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="'Cancel'" :classes="'mr-3'" @click.prevent="editModalshownId = 0" />
                <pretty-button :text="'Change'" :state="loadingState" :icon="'check'" :classes="'save'" />
              </div>
            </form>
          </li>
        </ul>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyLink from '@/Shared/Form/PrettyLink';
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    Layout,
    PrettyLink,
    PrettyButton,
    PrettySpan,
    TextInput,
    Errors,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Array,
      default: () => [],
    },
  },

  data() {
    return {
      loadingState: '',
      editModalshownId: 0,
      localUsers: [],
      form: {
        administrator: false,
        errors: [],
      },
    };
  },

  mounted() {
    this.localUsers = this.data.users;
  },

  methods: {
    showEditModal(user) {
      this.editModalshownId = user.id;
      this.form.administrator = user.is_account_administrator;
    },

    update(user) {
      this.loadingState = 'loading';

      axios
        .put(user.url.update, this.form)
        .then((response) => {
          this.editModalshownId = 0;
          this.flash('The user has been updated', 'success');
          this.localUsers[this.localUsers.findIndex((x) => x.id === user.id)] = response.data.data;
          this.loadingState = null;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(user) {
      if (confirm("Are you sure? This can't be recovered.")) {
        axios
          .delete(user.url.destroy)
          .then((response) => {
            this.flash('The user has been deleted', 'success');
            var id = this.localUsers.findIndex((x) => x.id === user.id);
            this.localUsers.splice(id, 1);
          })
          .catch((error) => {});
      }
    },
  },
};
</script>
