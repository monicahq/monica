<template>
  <div class="min-h-full">
    <div class="fixed top-0 z-10 w-full">
      <!-- main nav -->
      <nav
        class="max-w-8xl mx-auto flex h-10 items-center justify-between border-b bg-gray-50 px-3 dark:border-slate-600 dark:bg-gray-800 dark:text-slate-200 sm:px-6">
        <div
          class="dark:highlight-white/5 items-center rounded-lg border border-gray-200 bg-white px-2 py-1 text-sm dark:border-0 dark:border-gray-700 dark:bg-gray-900 dark:bg-gray-400/20 sm:flex">
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
            class="dark:highlight-white/5 block w-64 rounded-md border border-gray-300 px-2 py-1 text-center placeholder:text-gray-600 hover:cursor-pointer focus:border-indigo-500 focus:ring-indigo-500 dark:border-0 dark:border-gray-700 dark:bg-slate-900 placeholder:dark:text-gray-400 hover:dark:bg-slate-700 sm:text-sm"
            :placeholder="$t('app.layout_menu_search')"
            @focus="goToSearchPage" />
        </div>

        <!-- icons -->
        <div class="flew-grow">
          <ul class="relative">
            <li class="icon-dark-mode relative mr-4 inline">
              <label for="dark-mode-toggle" class="relative inline-flex cursor-pointer">
                <input
                  id="dark-mode-toggle"
                  v-model="style.checked"
                  type="checkbox"
                  class="peer sr-only"
                  @click="toggleStyle" />
                <div
                  class="peer mr-2 h-4 w-7 rounded-full bg-gray-200 after:absolute after:top-[2px] after:left-[2px] after:h-3 after:w-3 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-800 dark:peer-focus:ring-blue-800" />
                <svg
                  v-if="!style.checked"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 14 14"
                  height="15"
                  width="15">
                  <g>
                    <circle
                      cx="7"
                      cy="7"
                      r="2.5"
                      fill="none"
                      stroke="#4B5563"
                      stroke-linecap="round"
                      stroke-linejoin="round"></circle>
                    <line
                      x1="7"
                      y1="0.5"
                      x2="7"
                      y2="2.5"
                      fill="none"
                      stroke="#4B5563"
                      stroke-linecap="round"
                      stroke-linejoin="round"></line>
                    <line
                      x1="2.4"
                      y1="2.4"
                      x2="3.82"
                      y2="3.82"
                      fill="none"
                      stroke="#4B5563"
                      stroke-linecap="round"
                      stroke-linejoin="round"></line>
                    <line
                      x1="0.5"
                      y1="7"
                      x2="2.5"
                      y2="7"
                      fill="none"
                      stroke="#4B5563"
                      stroke-linecap="round"
                      stroke-linejoin="round"></line>
                    <line
                      x1="2.4"
                      y1="11.6"
                      x2="3.82"
                      y2="10.18"
                      fill="none"
                      stroke="#4B5563"
                      stroke-linecap="round"
                      stroke-linejoin="round"></line>
                    <line
                      x1="7"
                      y1="13.5"
                      x2="7"
                      y2="11.5"
                      fill="none"
                      stroke="#4B5563"
                      stroke-linecap="round"
                      stroke-linejoin="round"></line>
                    <line
                      x1="11.6"
                      y1="11.6"
                      x2="10.18"
                      y2="10.18"
                      fill="none"
                      stroke="#4B5563"
                      stroke-linecap="round"
                      stroke-linejoin="round"></line>
                    <line
                      x1="13.5"
                      y1="7"
                      x2="11.5"
                      y2="7"
                      fill="none"
                      stroke="#4B5563"
                      stroke-linecap="round"
                      stroke-linejoin="round"></line>
                    <line
                      x1="11.6"
                      y1="2.4"
                      x2="10.18"
                      y2="3.82"
                      fill="none"
                      stroke="#4B5563"
                      stroke-linecap="round"
                      stroke-linejoin="round"></line>
                  </g>
                </svg>
                <svg v-if="style.checked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14" height="15" width="15">
                  <path
                    d="M12,10.48A6.55,6.55,0,0,1,6.46.5a6.55,6.55,0,0,0,1,13A6.46,6.46,0,0,0,13,10.39,6.79,6.79,0,0,1,12,10.48Z"
                    fill="none"
                    stroke="#e5e7eb"
                    stroke-linecap="round"
                    stroke-linejoin="round"></path>
                </svg>
              </label>
            </li>
            <li class="mr-4 inline">
              <inertia-link :href="layoutData.url.settings" class="relative inline">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="icon-cog relative mr-1 inline-block h-4 w-4 cursor-pointer text-gray-600 dark:text-gray-400 dark:text-gray-300 sm:h-4 sm:w-4"
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

                <span class="text-sm dark:text-sky-400">{{ $t('app.layout_menu_settings') }}</span>
              </inertia-link>
            </li>
            <li class="inline">
              <inertia-link class="inline" method="post" :href="route('logout')" as="button">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="mr-1 inline-block h-4 w-4 cursor-pointer text-gray-600 dark:text-gray-400 dark:text-gray-300 sm:h-4 sm:w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>

                <span class="text-sm dark:text-sky-400">{{ $t('app.layout_menu_logout') }}</span>
              </inertia-link>
            </li>
          </ul>
        </div>
      </nav>

      <!-- vault sub menu -->
      <nav v-if="insideVault" class="bg-white dark:border-slate-300/10 dark:bg-gray-900 sm:border-b">
        <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
          <div class="flex items-baseline justify-between space-x-6">
            <div>
              <inertia-link
                :href="layoutData.vault.url.dashboard"
                :class="{
                  'bg-blue-700 text-white dark:bg-blue-300 dark:text-gray-900':
                    $page.component === 'Vault/Dashboard/Index',
                }"
                class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                {{ $t('app.layout_menu_dashboard') }}
              </inertia-link>

              <!-- <inertia-link
                href=""
                class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                {{ $t('app.layout_menu_reports') }}
              </inertia-link> -->

              <inertia-link
                :href="layoutData.vault.url.contacts"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Contact') }"
                class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                {{ $t('app.layout_menu_contacts') }}
              </inertia-link>

              <inertia-link
                :href="layoutData.vault.url.groups"
                v-if="layoutData.vault.visibility.show_group_tab"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Group') }"
                class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                {{ $t('app.layout_menu_groups') }}
              </inertia-link>

              <inertia-link
                :href="layoutData.vault.url.companies"
                v-if="layoutData.vault.visibility.show_companies_tab"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Companies') }"
                class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                {{ $t('app.layout_menu_companies') }}
              </inertia-link>

              <inertia-link
                :href="layoutData.vault.url.tasks"
                v-if="layoutData.vault.visibility.show_tasks_tab"
                :class="{
                  'bg-blue-700 text-white dark:bg-blue-300 dark:text-gray-900':
                    $page.component.startsWith('Vault/Dashboard/Task'),
                }"
                class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                {{ $t('app.layout_menu_tasks') }}
              </inertia-link>

              <!-- <inertia-link
                href=""
                class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                {{ $t('app.layout_menu_gift_center') }}
              </inertia-link> -->

              <!-- <inertia-link
                href=""
                class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                {{ $t('app.layout_menu_loans') }}
              </inertia-link> -->

              <inertia-link
                :href="layoutData.vault.url.files"
                v-if="layoutData.vault.visibility.show_files_tab"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Files') }"
                class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                {{ $t('app.layout_menu_files') }}
              </inertia-link>

              <inertia-link
                :href="layoutData.vault.url.journals"
                v-if="layoutData.vault.visibility.show_journal_tab"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Journal') }"
                class="mr-2 rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                {{ $t('app.layout_menu_journals') }}
              </inertia-link>

              <inertia-link
                v-if="layoutData.vault.permission.at_least_editor"
                :href="layoutData.vault.url.settings"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Settings') }"
                class="rounded-md px-2 py-1 text-sm font-medium hover:bg-gray-700 hover:text-white">
                {{ $t('app.layout_menu_vault_settings') }}
              </inertia-link>
            </div>
          </div>
        </div>
      </nav>
    </div>

    <!-- Page Heading -->
    <header v-if="$slots.header" class="relative mt-10 mb-8 bg-white dark:bg-gray-900 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <slot name="header" />
      </div>
    </header>

    <main class="relative mt-10 mb-8">
      <slot />
    </main>

    <footer class="mb-10 text-center text-xs">
      <ul class="dark:text-gray-300">
        <li class="mr-4 mb-2 sm:mb-0 sm:inline">
          {{ $t('app.layout_footer_monica') }}
        </li>
      </ul>
    </footer>
  </div>

  <toaster />
</template>

<script>
import Toaster from '@/Shared/Toaster.vue';

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

  data() {
    return {
      style: {
        checked: false,
      },
    };
  },

  mounted() {
    this.style.checked =
      localStorage.theme === 'dark' ||
      (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
    if (localStorage.success) {
      this.flash(localStorage.success, 'success');
      localStorage.removeItem('success');
    }
  },

  methods: {
    goToSearchPage() {
      this.$inertia.visit(this.layoutData.vault.url.search);
    },

    toggleStyle() {
      this.style.checked = !this.style.checked;
      if (this.style.checked) {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
      } else {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.icon-search {
  left: 8px;
  top: 8px;
}

.sha {
  padding: 0.2em 0.4em;
}

.icon-cog {
  top: -1px;
}

.icon-dark-mode {
  top: 3px;
}
</style>
