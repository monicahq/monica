<script setup>
import { ref, onMounted } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
import Toaster from '@/Shared/Toaster.vue';
import FooterLayout from '@/Layouts/FooterLayout.vue';
import ChevronIcon from '@/Shared/Icons/ChevronIcon.vue';
import DarkModeIcon from '@/Shared/Icons/DarkModeIcon.vue';
import LayoutNav from '@/Layouts/LayoutNav.vue';
import { Settings, LogOut, ScanSearch } from 'lucide-vue-next';
import { isDark, flash } from '@/methods.js';

const props = defineProps({
  title: String,
  insideVault: Boolean,
  layoutData: Object,
});

const checked = ref(isDark());

onMounted(() => {
  if (localStorage.success) {
    flash(localStorage.success, 'success');
    localStorage.removeItem('success');
  }
});

const goToSearchPage = () => {
  router.visit(props.layoutData.vault.url.search, {
    data: { searchTerm: '' },
  });
};

const toggleStyle = () => {
  checked.value = !checked.value;
  if (checked.value) {
    document.documentElement.classList.add('dark');
    localStorage.theme = 'dark';
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.theme = 'light';
  }
};
</script>

<template>
  <Head :title="title" />

  <div class="min-h-full">
    <div class="sm:fixed top-0 z-10 w-full">
      <!-- main nav - only displayed on desktop -->
      <nav
        class="hidden max-w-8xl mx-auto sm:flex h-10 items-center justify-between border-b border-gray-300 bg-gray-50 px-3 dark:border-slate-600 dark:bg-gray-800 dark:text-slate-200 sm:px-6">
        <div
          class="dark:highlight-white/5 items-center rounded-lg border border-gray-200 bg-white px-2 py-1 text-sm dark:border-0 dark:border-gray-700 dark:bg-gray-400/20 dark:bg-gray-900 sm:flex">
          <Link :href="layoutData.url.vaults" class="shrink-0 dark:text-sky-400">
            {{ layoutData.user.name }}
          </Link>

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
          <ScanSearch class="absolute start-2 top-2 h-4 w-4 text-gray-400" />
          <input
            type="text"
            class="dark:highlight-white/5 block w-64 rounded-md border border-gray-300 px-2 py-1 text-center placeholder:text-gray-600 hover:cursor-pointer focus:border-indigo-500 focus:ring-indigo-500 dark:border-0 dark:border-gray-700 dark:bg-slate-900 dark:placeholder:text-gray-400 dark:hover:bg-slate-700 sm:text-sm"
            :placeholder="$t('Search something')"
            @focus="goToSearchPage" />
        </div>

        <!-- icons -->
        <div class="flex items-center justify-end gap-4">
          <div class="relative top-[3px] me-4 inline">
            <label for="dark-mode-toggle" class="relative inline-flex cursor-pointer">
              <input id="dark-mode-toggle" v-model="checked" type="checkbox" class="peer hidden" @click="toggleStyle" />
              <div
                class="peer me-2 h-4 w-7 rounded-full bg-gray-200 after:absolute after:left-[2px] after:right-[14px] after:top-[2px] after:h-3 after:w-3 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-800 dark:peer-focus:ring-blue-800" />
              <DarkModeIcon :checked="checked" />
            </label>
          </div>
          <Link :href="layoutData.url.settings" class="relative flex items-center gap-1">
            <Settings
              class="h-4 w-4 cursor-pointer text-gray-600 hover:text-gray-900 dark:text-gray-600 dark:hover:text-gray-100" />

            <span class="text-sm dark:text-sky-400">{{ $t('Settings') }}</span>
          </Link>
          <Link
            class="relative flex items-center gap-1 cursor-pointer"
            method="post"
            :href="route('logout')"
            as="button">
            <LogOut
              class="h-4 w-4 cursor-pointer text-gray-600 hover:text-gray-900 dark:text-gray-600 dark:hover:text-gray-100" />

            <span class="text-sm dark:text-sky-400">{{ $t('Logout') }}</span>
          </Link>
        </div>
      </nav>

      <!-- mobile nav -->
      <div
        class="sm:hidden pt-3 border-b bg-gray-50 px-3 dark:border-slate-600 dark:bg-gray-800 dark:text-slate-200 sm:px-6">
        <!-- user / vault & logout -->
        <div
          class="flex mb-2 dark:highlight-white/5 items-center justify-between text-sm dark:border-0 dark:border-gray-700 dark:bg-gray-400/20 dark:bg-gray-900">
          <div class="flex items-center border border-gray-200 rounded-lg bg-white px-2 py-1">
            <Link :href="layoutData.url.vaults" class="shrink-0 dark:text-sky-400">
              {{ layoutData.user.name }}
            </Link>

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
            <Link :href="layoutData.url.settings" class="border border-gray-200 rounded-lg bg-white px-2 py-1 mr-2">
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
            </Link>

            <!-- logout -->
            <Link
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
            </Link>
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

      <LayoutNav
        v-if="insideVault"
        :links="
          [
            {
              url: layoutData.vault.url.dashboard,
              title: $t('Dashboard'),
              selected: $page.component === 'Vault/Dashboard/Index',
            },
            {
              url: layoutData.vault.url.contacts,
              title: $t('Contacts'),
              selected: $page.component.startsWith('Vault/Contact'),
            },
            layoutData.vault.visibility.show_calendar_tab
              ? {
                  url: layoutData.vault.url.calendar,
                  title: $t('Calendar'),
                  selected: $page.component.startsWith('Vault/Calendar'),
                }
              : null,
            layoutData.vault.visibility.show_journal_tab
              ? {
                  url: layoutData.vault.url.journals,
                  title: $t('Journals'),
                  selected: $page.component.startsWith('Vault/Journal'),
                }
              : null,
            layoutData.vault.visibility.show_group_tab
              ? {
                  url: layoutData.vault.url.groups,
                  title: $t('Groups'),
                  selected: $page.component.startsWith('Vault/Group'),
                }
              : null,
            layoutData.vault.visibility.show_companies_tab
              ? {
                  url: layoutData.vault.url.companies,
                  title: $t('Companies'),
                  selected: $page.component.startsWith('Vault/Companies'),
                }
              : null,
            layoutData.vault.visibility.show_tasks_tab
              ? {
                  url: layoutData.vault.url.tasks,
                  title: $t('Tasks'),
                  selected: $page.component.startsWith('Vault/Dashboard/Task'),
                }
              : null,
            layoutData.vault.visibility.show_reports_tab
              ? {
                  url: layoutData.vault.url.reports,
                  title: $t('Reports'),
                  selected: $page.component.startsWith('Vault/Reports'),
                }
              : null,
            layoutData.vault.visibility.show_files_tab
              ? {
                  url: layoutData.vault.url.files,
                  title: $t('Files'),
                  selected: $page.component.startsWith('Vault/Files'),
                }
              : null,
            layoutData.vault.permission.at_least_editor
              ? {
                  url: layoutData.vault.url.settings,
                  title: $t('Vault settings'),
                  selected: $page.component.startsWith('Vault/Settings'),
                }
              : null,
          ].filter((f) => f !== null)
        " />
    </div>

    <!-- Page Heading -->
    <header
      v-if="$slots.header"
      class="relative mb-8 mt-10 bg-white dark:bg-gray-900 sm:border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <slot name="header" />
      </div>
    </header>

    <main class="relative mb-8 mt-10">
      <slot />
    </main>

    <FooterLayout />
  </div>

  <Toaster />
</template>
