<template>
  <div class="min-h-full">
    <div class="fixed top-0 z-10 w-full">
      <!-- main nav -->
      <nav
        class="max-w-8xl mx-auto flex h-10 items-center justify-between border-b bg-gray-50 px-3 dark:border-slate-600 dark:bg-gray-800 dark:text-slate-200 sm:px-6">
        <div
          class="dark:highlight-white/5 items-center rounded-lg border border-gray-200 bg-white px-2 py-1 text-sm dark:border-0 dark:border-gray-700 dark:bg-gray-400/20 dark:bg-gray-900 sm:flex">
          <InertiaLink :href="layoutData.url.vaults" class="flex-shrink-0 dark:text-sky-400">
            {{ layoutData.user.name }}
          </InertiaLink>

          <!-- information about the current vault -->
          <div v-if="layoutData.vault">
            <span class="relative mx-1">
              <ChevronIcon :type="'right'" />
            </span>
            {{ layoutData.vault.name }}
          </div>
        </div>

        <!-- search box -->
        <div v-if="insideVault" class="flew-grow relative">
          <SearchIcon />
          <input
            type="text"
            class="dark:highlight-white/5 block w-64 rounded-md border border-gray-300 px-2 py-1 text-center placeholder:text-gray-600 hover:cursor-pointer focus:border-indigo-500 focus:ring-indigo-500 dark:border-0 dark:border-gray-700 dark:bg-slate-900 placeholder:dark:text-gray-400 hover:dark:bg-slate-700 sm:text-sm"
            :placeholder="$t('Search something')"
            @focus="goToSearchPage" />
        </div>

        <!-- icons -->
        <div class="flew-grow">
          <ul class="relative">
            <li class="relative top-[3px] me-4 inline">
              <label for="dark-mode-toggle" class="relative inline-flex cursor-pointer">
                <input
                  id="dark-mode-toggle"
                  v-model="style.checked"
                  type="checkbox"
                  class="peer hidden"
                  @click="toggleStyle" />
                <div
                  class="peer me-2 h-4 w-7 rounded-full bg-gray-200 after:absolute after:left-[2px] after:right-[14px] after:top-[2px] after:h-3 after:w-3 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-800 dark:peer-focus:ring-blue-800" />
                <DarkModeIcon :checked="style.checked" />
              </label>
            </li>
            <li class="me-4 inline">
              <InertiaLink :href="layoutData.url.settings" class="relative inline">
                <SettingIcon />

                <span class="text-sm dark:text-sky-400">{{ $t('Settings') }}</span>
              </InertiaLink>
            </li>
            <li class="inline">
              <InertiaLink class="inline" method="post" :href="route('logout')" as="button">
                <DoorIcon />

                <span class="text-sm dark:text-sky-400">{{ $t('Logout') }}</span>
              </InertiaLink>
            </li>
          </ul>
        </div>
      </nav>

      <!-- vault sub menu -->
      <nav v-if="insideVault" class="bg-white dark:border-slate-300/10 dark:bg-gray-900 sm:border-b">
        <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
          <div class="flex items-baseline justify-between space-x-6">
            <ul class="list-none text-sm font-medium">
              <li class="inline">
                <InertiaLink
                  :href="layoutData.vault.url.dashboard"
                  :class="{
                    'bg-blue-700 text-white dark:bg-blue-300 dark:text-gray-900':
                      $page.component === 'Vault/Dashboard/Index',
                  }"
                  class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                  {{ $t('Dashboard') }}
                </InertiaLink>
              </li>
              <li class="inline">
                <InertiaLink
                  :href="layoutData.vault.url.contacts"
                  :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Contact') }"
                  class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                  {{ $t('Contacts') }}
                </InertiaLink>
              </li>
              <li class="inline">
                <InertiaLink
                  :href="layoutData.vault.url.calendar"
                  v-if="layoutData.vault.visibility.show_calendar_tab"
                  :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Calendar') }"
                  class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                  {{ $t('Calendar') }}
                </InertiaLink>
              </li>
              <li class="inline">
                <InertiaLink
                  :href="layoutData.vault.url.journals"
                  v-if="layoutData.vault.visibility.show_journal_tab"
                  :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Journal') }"
                  class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                  {{ $t('Journals') }}
                </InertiaLink>
              </li>
              <li class="inline">
                <InertiaLink
                  :href="layoutData.vault.url.groups"
                  v-if="layoutData.vault.visibility.show_group_tab"
                  :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Group') }"
                  class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                  {{ $t('Groups') }}
                </InertiaLink>
              </li>
              <li class="inline">
                <InertiaLink
                  :href="layoutData.vault.url.companies"
                  v-if="layoutData.vault.visibility.show_companies_tab"
                  :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Companies') }"
                  class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                  {{ $t('Companies') }}
                </InertiaLink>
              </li>
              <li class="inline">
                <InertiaLink
                  :href="layoutData.vault.url.tasks"
                  v-if="layoutData.vault.visibility.show_tasks_tab"
                  :class="{
                    'bg-blue-700 text-white dark:bg-blue-300 dark:text-gray-900':
                      $page.component.startsWith('Vault/Dashboard/Task'),
                  }"
                  class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                  {{ $t('Tasks') }}
                </InertiaLink>
              </li>
              <li class="inline">
                <InertiaLink
                  :href="layoutData.vault.url.reports"
                  v-if="layoutData.vault.visibility.show_reports_tab"
                  :class="{
                    'bg-blue-700 text-white dark:bg-blue-300 dark:text-gray-900':
                      $page.component.startsWith('Vault/Reports'),
                  }"
                  class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                  {{ $t('Reports') }}
                </InertiaLink>
              </li>
              <li class="inline">
                <InertiaLink
                  :href="layoutData.vault.url.files"
                  v-if="layoutData.vault.visibility.show_files_tab"
                  :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Files') }"
                  class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 hover:dark:text-slate-300">
                  {{ $t('Files') }}
                </InertiaLink>
              </li>
              <li class="inline">
                <InertiaLink
                  v-if="layoutData.vault.permission.at_least_editor"
                  :href="layoutData.vault.url.settings"
                  :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Settings') }"
                  class="rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white">
                  {{ $t('Vault settings') }}
                </InertiaLink>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </div>

    <!-- Page Heading -->
    <header v-if="$slots.header" class="relative mb-8 mt-10 bg-white dark:bg-gray-900 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <slot name="header" />
      </div>
    </header>

    <main class="relative mb-8 mt-10">
      <slot />
    </main>

    <FooterLayout />
  </div>

  <toaster />
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Toaster from '@/Shared/Toaster.vue';
import FooterLayout from '@/Layouts/FooterLayout.vue';
import ChevronIcon from '@/Shared/Icons/ChevronIcon.vue';
import SearchIcon from '@/Shared/Icons/SearchIcon.vue';
import DarkModeIcon from '@/Shared/Icons/DarkModeIcon.vue';
import SettingIcon from '@/Shared/Icons/SettingIcon.vue';
import DoorIcon from '@/Shared/Icons/DoorIcon.vue';

export default {
  components: {
    InertiaLink: Link,
    Toaster,
    FooterLayout,
    ChevronIcon,
    SearchIcon,
    DarkModeIcon,
    SettingIcon,
    DoorIcon,
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
    this.style.checked = this.isDark();
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
