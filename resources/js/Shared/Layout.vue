<style lang="scss" scoped>
main {
  color: #343a4b;
}

.icon-search {
  left: 8px;
  top: 8px;
}
</style>

<template>
  <main>
    <div class="min-h-full">
      <div class="fixed top-0 z-10 w-full">
        <!-- main nav -->
        <nav
          class="max-w-8xl mx-auto flex h-10 items-center justify-between border-b bg-gray-50 px-3 dark:border-slate-300/10 dark:bg-slate-900 dark:text-slate-200 sm:px-6">
          <div
            class="dark:highlight-white/5 items-center rounded-lg border border-gray-200 bg-white px-2 py-1 text-sm dark:border-0 dark:bg-sky-400/20 sm:flex">
            <inertia-link :href="layoutData.url.vaults" class="flex-shrink-0 dark:text-sky-400">
              {{ layoutData.user.name }}
            </inertia-link>

            <!-- information about the current vault -->
            <div v-if="layoutData.vault">
              <span class="relative mr-1 ml-1">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="relative inline h-3 w-3"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </span>
              {{ layoutData.vault.name }}
            </div>
          </div>

          <!-- search box -->
          <div v-if="insideVault" class="flew-grow relative">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="icon-search absolute h-4 w-4 text-gray-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              type="text"
              class="dark:highlight-white/5 block w-64 rounded-md border border-gray-300 px-2 py-1 text-center hover:cursor-pointer focus:border-indigo-500 focus:ring-indigo-500 dark:border-0 dark:bg-slate-800 dark:hover:bg-slate-700 sm:text-sm"
              placeholder="Search something"
              @focus="goToSearchPage" />
          </div>

          <!-- icons -->
          <div class="flew-grow">
            <ul>
              <li class="mr-3 inline">
                <inertia-link :href="layoutData.url.settings" class="inline">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="mr-1 inline-block h-4 w-4 cursor-pointer text-gray-600 sm:h-5 sm:w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>

                  <span class="text-sm dark:text-sky-400">Settings</span>
                </inertia-link>
              </li>
              <li class="inline">
                <inertia-link @click="logout()" class="inline">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="mr-1 inline-block h-4 w-4 cursor-pointer text-gray-600 sm:h-5 sm:w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>

                  <span class="text-sm dark:text-sky-400">Logout</span>
                </inertia-link>
              </li>
            </ul>
          </div>
        </nav>

        <!-- vault sub menu -->
        <nav v-if="insideVault" class="bg-white dark:border-slate-300/10 dark:bg-slate-900 sm:border-b">
          <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
            <div class="flex items-baseline justify-between space-x-6">
              <div>
                <inertia-link
                  :href="layoutData.vault.url.dashboard"
                  :class="{ 'bg-blue-700 text-white dark:text-white': $page.component.startsWith('Vault/Dashboard') }"
                  class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                  Dashboard
                </inertia-link>

                <inertia-link
                  href=""
                  class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                  Reports
                </inertia-link>

                <inertia-link
                  :href="layoutData.vault.url.contacts"
                  :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Contact') }"
                  class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                  Contacts
                </inertia-link>

                <inertia-link
                  href=""
                  class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                  Gift center
                </inertia-link>

                <inertia-link
                  href=""
                  class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                  Loans & debts center
                </inertia-link>

                <inertia-link
                  v-if="layoutData.vault.permission.at_least_editor"
                  :href="layoutData.vault.url.settings"
                  :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Settings') }"
                  class="rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white">
                  Vault settings
                </inertia-link>
              </div>
            </div>
          </div>
        </nav>
      </div>
      <main class="relative mt-10">
        <slot />
      </main>
    </div>

    <toaster />
  </main>
</template>

<script>
import Toaster from '@/Shared/Toaster';

export default {
  components: {
    Toaster,
  },

  props: {
    title: {
      type: String,
      default: null,
    },
    insideVault: {
      type: Boolean,
      default: false,
    },
    layoutData: {
      type: Object,
      default: null,
    },
  },

  mounted() {
    if (localStorage.success) {
      this.flash(localStorage.success, 'success');
      localStorage.removeItem('success');
    }
  },

  methods: {
    logout() {
      this.$inertia.visit(this.layoutData.url.logout);
    },

    goToSearchPage() {
      this.$inertia.visit(this.layoutData.vault.url.search);
    },
  },
};
</script>
