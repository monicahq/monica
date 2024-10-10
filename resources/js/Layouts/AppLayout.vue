<script setup>
import { ref } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import JetBanner from '@/Components/Jetstream/Banner.vue';
import JetDropdown from '@/Components/Dropdown.vue';
import JetDropdownLink from '@/Components/DropdownLink.vue';
import JetNavLink from '@/Components/NavLink.vue';
import JetResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import FooterLayout from '@/Layouts/FooterLayout.vue';

defineProps({
  title: String,
});

const showingNavigationDropdown = ref(false);
const showingOverflowMenu = ref(false);

const logout = () => {
  router.post(route('logout'));
};

const toggleOverflowMenu = () => {
  showingOverflowMenu.value = !showingOverflowMenu.value;
};
</script>

<template>
  <div>
    <Head :title="title" />
    <JetBanner />

    <div class="flex min-h-screen flex-col bg-gray-100 dark:bg-gray-800">
      <nav class="border-b border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto max-w-5xl px-3 sm:px-6 lg:px-8">
          <div class="flex h-16 justify-between">
            <div class="flex">
              <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                <JetNavLink :href="route('vault.index')" :active="route().current('vault.index')">
                  {{ $t('Home') }}
                </JetNavLink>
              </div>
            </div>

            <div class="hidden sm:ms-6 sm:flex sm:items-center">
              <div class="relative ms-3">
                <JetDropdown align="right" width="48">
                  <template #trigger>
                    <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex rounded-full border-2 border-transparent text-sm transition focus:border-gray-300 focus:outline-none focus:dark:border-gray-700">
                      <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.auth.user?.profile_photo_url" :alt="$page.props.auth.user?.name" />
                    </button>

                    <span v-else class="inline-flex rounded-md">
                      <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition hover:text-gray-700 focus:outline-none dark:bg-gray-900 hover:dark:text-gray-300">
                        {{ $page.props.auth.user?.name }}
                        <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                      </button>
                    </span>
                  </template>

                  <template #content>
                    <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-600">
                      {{ $t('Manage Account') }}
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-900" />
                    <form @submit.prevent="logout">
                      <JetDropdownLink as="button">
                        {{ $t('Log Out') }}
                      </JetDropdownLink>
                    </form>
                  </template>
                </JetDropdown>
              </div>
            </div>

            <!-- Hamburger Menu -->
            <div class="-me-2 flex items-center sm:hidden">
              <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-600 hover:dark:bg-gray-900 focus:dark:bg-gray-900">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                  <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                  <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <!-- Overflow Menu Button -->
            <div class="flex sm:hidden">
              <button @click="toggleOverflowMenu" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-600 hover:dark:bg-gray-900 focus:dark:bg-gray-900">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                </svg>
              </button>
              <div v-if="showingOverflowMenu" class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 shadow-lg rounded-md z-20">
                <div class="py-1">
                  <JetDropdownLink :href="route('settings.index')">{{ $t('Settings') }}</JetDropdownLink>
                  <JetDropdownLink :href="route('account.show')">{{ $t('Account Info') }}</JetDropdownLink>
                  <form @submit.prevent="logout">
                    <JetDropdownLink as="button">{{ $t('Log Out') }}</JetDropdownLink>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Responsive Navigation Menu -->
        <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
          <div class="space-y-1 pb-3 pt-2">
            <JetResponsiveNavLink :href="route('vault.index')" :active="route().current('vault.index')">
              {{ $t('Home') }}
            </JetResponsiveNavLink>
          </div>

          <!-- Responsive Settings Options -->
          <div class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-800">
            <div class="flex items-center px-4">
              <div v-if="$page.props.jetstream.managesProfilePhotos" class="me-3 shrink-0">
                <img class="h-10 w-10 rounded-full object-cover" :src="$page.props.auth.user?.profile_photo_url" :alt="$page.props.auth.user?.name" />
              </div>
              <div>
                <div class="text-base font-medium text-gray-800 dark:text-gray-200">
                  {{ $page.props.auth.user?.name }}
                </div>
                <div class="text-sm font-medium text-gray-500">
                  {{ $page.props.auth.user?.email }}
                </div>
              </div>
            </div>
            <div class="mt-3 space-y-1">
              <form method="POST" @submit.prevent="logout">
                <JetResponsiveNavLink as="button">{{ $t('Log Out') }}</JetResponsiveNavLink>
              </form>
            </div>
          </div>
        </div>
      </nav>

      <header v-if="$slots.header" class="bg-white shadow dark:bg-gray-900 dark:shadow-gray-700">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
          <slot name="header" />
        </div>
      </header>

      <main class="flex-grow">
        <slot />
      </main>

      <FooterLayout />
    </div>
  </div>
</template>
