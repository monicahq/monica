<style lang="scss" scoped>
main {
  color: #343a4b;
}
</style>

<template>
  <main>
    <div class="min-h-full">
      <div class="fixed w-full z-10 top-0">

        <!-- main nav -->
        <nav class="bg-gray-50 border-b max-w-8xl mx-auto px-3 sm:px-6 flex items-center justify-between h-10">
          <div class="border border-gray-200 rounded-lg bg-white items-center sm:flex px-3">
            <Link :href="layoutData.url.vaults" class="flex-shrink-0">
              {{ layoutData.user.name }}
            </Link>
            <div v-if="layoutData.vault"><span class="mr-1 ml-1">></span> {{ layoutData.vault.name }}</div>
          </div>

          <!-- search box -->
          <div v-if="insideVault" class="flew-grow">
            <input type="text" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md border border-gray-300 w-64 px-2 py-1" placeholder="Search a contact" />
          </div>

          <!-- icons -->
          <div class="flew-grow">
            <ul>
              <li class="inline mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="inline text-gray-600 h-4 w-4 sm:h-5 sm:w-5 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                  />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </li>
              <li class="inline">
                <svg @click="logout()" xmlns="http://www.w3.org/2000/svg" class="inline text-gray-600 h-4 w-4 sm:h-5 sm:w-5 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                  />
                </svg>
              </li>
            </ul>
          </div>
        </nav>

        <!-- sub menu -->
        <nav v-if="insideVault" class="sm:border-b bg-white">
          <div class="max-w-8xl mx-auto px-4 sm:px-6">
            <div class="py-2">
              <div class="">
                <div class="hidden md:block">
                  <div class="flex items-baseline justify-between space-x-6">
                    <div>
                      <Link href="" class="bg-blue-700 text-white px-2 py-1 mr-2 rounded-md text-sm font-medium">
                        Dashboard
                      </Link>

                      <Link href="" class="hover:bg-gray-700 hover:text-white px-2 py-1 mr-2 rounded-md text-sm font-medium">
                        Reports
                      </Link>

                      <Link href="contacts'" class="hover:bg-gray-700 hover:text-white px-2 py-1 mr-2 rounded-md text-sm font-medium">
                        Contacts
                      </Link>

                      <Link href="" class="hover:bg-gray-700 hover:text-white px-2 py-1 mr-2 rounded-md text-sm font-medium">
                        Gift center
                      </Link>

                      <Link href="" class="hover:bg-gray-700 hover:text-white px-2 py-1 mr-2 rounded-md text-sm font-medium">
                        Loans & debts center
                      </Link>
                    </div>

                    <Link href="" class="hover:bg-gray-700 hover:text-white px-2 py-1 rounded-md text-sm font-medium">
                        Settings
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </nav>
      </div>
      <main class="mt-10 sm:mt-20 relative">
        <slot></slot>
      </main>
    </div>

    <toaster />
  </main>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3';
import Toaster from '@/Shared/Toaster';

export default {
  components: {
    Link,
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

  created() {
    if (localStorage.success) {
      this.flash(localStorage.success, 'success');
      localStorage.removeItem('success');
    }
  },

  methods: {
    logout() {
      window.open(this.user.url.logout);
    },
  },
};
</script>
