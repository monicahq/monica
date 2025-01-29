<template>
  <div class="min-h-full">
    <div class="sm:fixed top-0 z-10 w-full">
      <!-- main nav - only displayed on desktop -->
      <nav
        class="hidden max-w-8xl mx-auto sm:flex h-10 items-center justify-between border-b bg-gray-50 px-3 dark:border-slate-600 dark:bg-gray-800 dark:text-slate-200 sm:px-6">
        <div
          class="dark:highlight-white/5 items-center rounded-lg border border-gray-200 bg-white px-2 py-1 text-sm dark:border-0 dark:border-gray-700 dark:bg-gray-400/20 dark:bg-gray-900 sm:flex">
          <InertiaLink :href="layoutData.url.vaults" class="shrink-0 dark:text-sky-400">
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
            class="dark:highlight-white/5 block w-64 rounded-md border border-gray-300 px-2 py-1 text-center placeholder:text-gray-600 hover:cursor-pointer focus:border-indigo-500 focus:ring-indigo-500 dark:border-0 dark:border-gray-700 dark:bg-slate-900 dark:placeholder:text-gray-400 dark:hover:bg-slate-700 sm:text-sm"
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
                  class="peer me-2 h-4 w-7 rounded-full bg-gray-200 after:absolute after:left-[2px] after:right-[14px] after:top-[2px] after:h-3 after:w-3 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-800 dark:peer-focus:ring-blue-800" />
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

      <!-- mobile nav -->
      <div
        class="sm:hidden pt-3 border-b bg-gray-50 px-3 dark:border-slate-600 dark:bg-gray-800 dark:text-slate-200 sm:px-6">
        <!-- user / vault & logout -->
        <div
          class="flex mb-2 dark:highlight-white/5 items-center justify-between text-sm dark:border-0 dark:border-gray-700 dark:bg-gray-400/20 dark:bg-gray-900">
          <div class="flex items-center border border-gray-200 rounded-lg bg-white px-2 py-1">
            <InertiaLink :href="layoutData.url.vaults" class="shrink-0 dark:text-sky-400">
              {{ layoutData.user.name }}
            </InertiaLink>

            <!-- information about the current vault -->
            <div v-if="layoutData.vault">
              <span class="relative mx-1">
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

          <div class="flex items-center">
            <!-- settings -->
            <InertiaLink
              :href="layoutData.url.settings"
              class="border border-gray-200 rounded-lg bg-white px-2 py-1 mr-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
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
            </InertiaLink>

            <!-- logout -->
            <InertiaLink
              class="border border-gray-200 rounded-lg bg-white px-2 py-1"
              method="post"
              :href="route('logout')"
              as="button">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </InertiaLink>
          </div>
        </div>

        <!-- search box -->
        <div v-if="insideVault" class="relative mb-2">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="absolute start-2 top-2 h-4 w-4 text-gray-400"
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
            class="dark:highlight-white/5 block w-full rounded-md border border-gray-300 px-2 py-1 text-center placeholder:text-gray-600 hover:cursor-pointer focus:border-indigo-500 focus:ring-indigo-500 dark:border-0 dark:border-gray-700 dark:bg-slate-900 dark:placeholder:text-gray-400 dark:hover:bg-slate-700 sm:text-sm"
            :placeholder="$t('Search something')"
            @focus="goToSearchPage" />
        </div>
      </div>

      <!-- vault sub menu on desktop -->
      <nav v-if="insideVault" class="hidden sm:block bg-white dark:border-slate-300/10 dark:bg-gray-900 sm:border-b">
        <div class="max-w-8xl mx-auto px-4 py-2 sm:px-6 block">
          <ul class="list-none text-sm font-medium">
            <li class="inline">
              <InertiaLink
                :href="layoutData.vault.url.dashboard"
                :class="{
                  'bg-blue-700 text-white dark:bg-blue-300 dark:text-gray-900':
                    $page.component === 'Vault/Dashboard/Index',
                }"
                class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                {{ $t('Dashboard') }}
              </InertiaLink>
            </li>
            <li class="inline">
              <InertiaLink
                :href="layoutData.vault.url.contacts"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Contact') }"
                class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                {{ $t('Contacts') }}
              </InertiaLink>
            </li>
            <li class="inline">
              <InertiaLink
                :href="layoutData.vault.url.calendar"
                v-if="layoutData.vault.visibility.show_calendar_tab"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Calendar') }"
                class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                {{ $t('Calendar') }}
              </InertiaLink>
            </li>
            <li class="inline">
              <InertiaLink
                :href="layoutData.vault.url.journals"
                v-if="layoutData.vault.visibility.show_journal_tab"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Journal') }"
                class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                {{ $t('Journals') }}
              </InertiaLink>
            </li>
            <li class="inline">
              <InertiaLink
                :href="layoutData.vault.url.groups"
                v-if="layoutData.vault.visibility.show_group_tab"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Group') }"
                class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                {{ $t('Groups') }}
              </InertiaLink>
            </li>
            <li class="inline">
              <InertiaLink
                :href="layoutData.vault.url.companies"
                v-if="layoutData.vault.visibility.show_companies_tab"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Companies') }"
                class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
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
                class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
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
                class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
                {{ $t('Reports') }}
              </InertiaLink>
            </li>
            <li class="inline">
              <InertiaLink
                :href="layoutData.vault.url.files"
                v-if="layoutData.vault.visibility.show_files_tab"
                :class="{ 'bg-blue-700 text-white': $page.component.startsWith('Vault/Files') }"
                class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:bg-sky-400/20 dark:text-slate-400 dark:hover:text-slate-300">
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
      </nav>

      <!-- vault sub menu on mobile -->
      <nav v-if="insideVault" class="block md:hidden px-4 py-2">
        <div class="relative">
          <select
            v-model="selectedOption"
            @change="navigateToSelected"
            class="w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:outline-hidden focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <option value="" disabled>{{ $t('Select a page') }}</option>
            <option :value="layoutData.vault.url.dashboard" :selected="$page.component.startsWith('Vault/Dashboard')">
              {{ $t('Dashboard') }}
            </option>
            <option :value="layoutData.vault.url.contacts" :selected="$page.component.startsWith('Vault/Contact')">
              {{ $t('Contacts') }}
            </option>
            <option
              v-if="layoutData.vault.visibility.show_calendar_tab"
              :value="layoutData.vault.url.calendar"
              :selected="$page.component.startsWith('Vault/Calendar')">
              {{ $t('Calendar') }}
            </option>
            <option
              v-if="layoutData.vault.visibility.show_journal_tab"
              :value="layoutData.vault.url.journals"
              :selected="$page.component.startsWith('Vault/Journal')">
              {{ $t('Journals') }}
            </option>
            <option
              v-if="layoutData.vault.visibility.show_group_tab"
              :value="layoutData.vault.url.groups"
              :selected="$page.component.startsWith('Vault/Group')">
              {{ $t('Groups') }}
            </option>
            <option
              v-if="layoutData.vault.visibility.show_companies_tab"
              :value="layoutData.vault.url.companies"
              :selected="$page.component.startsWith('Vault/Companies')">
              {{ $t('Companies') }}
            </option>
            <option
              v-if="layoutData.vault.visibility.show_tasks_tab"
              :value="layoutData.vault.url.tasks"
              :selected="$page.component.startsWith('Vault/Dashboard/Task')">
              {{ $t('Tasks') }}
            </option>
            <option
              v-if="layoutData.vault.visibility.show_reports_tab"
              :value="layoutData.vault.url.reports"
              :selected="$page.component.startsWith('Vault/Reports')">
              {{ $t('Reports') }}
            </option>
            <option
              v-if="layoutData.vault.visibility.show_files_tab"
              :value="layoutData.vault.url.files"
              :selected="$page.component.startsWith('Vault/Files')">
              {{ $t('Files') }}
            </option>
            <option
              v-if="layoutData.vault.permission.at_least_editor"
              :value="layoutData.vault.url.settings"
              :selected="$page.component.startsWith('Vault/Settings')">
              {{ $t('Vault settings') }}
            </option>
          </select>
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
      selectedOption: '',
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
      this.$inertia.visit(this.layoutData.vault.url.search, {
        data: { searchTerm: this.search },
      });
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

    navigateToSelected() {
      this.$inertia.visit(this.selectedOption);
    },
  },
};
</script>
