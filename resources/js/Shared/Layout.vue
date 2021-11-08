<style lang="scss" scoped>
</style>

<template>
  <div>
    dasfs
    <div class="min-h-full">
      <div class="fixed w-full z-10 top-0">
        <nav class="bg-gray-50 border-b">
          <div class="max-w-8xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-10">
              <div class="flew-grow flex items-center">
                <div class="flex-shrink-0">
                  Regis Freyd
                </div>
              </div>
              <div class="flew-grow">
                <input type="text" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md border border-gray-300 w-64 px-2 py-1" placeholder="Search a contact" />
              </div>
              <div class="flew-grow">
                <ul>
                  <li class="inline mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="inline text-gray-600 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                      />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </li>
                  <li class="inline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="inline text-gray-600 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                      />
                    </svg>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </nav>
        <nav class="border-b bg-white">
          <div class="max-w-8xl mx-auto px-4 sm:px-6">
            <div class="py-2">
              <div class="">
                <div class="hidden md:block">
                  <div class="flex items-baseline space-x-6">
                    <a href="#" class="bg-blue-400 text-white px-2 py-1 rounded-md text-sm font-medium"
                       aria-current="page"
                    >Dashboard</a>

                    <a href="#"
                       class="hover:bg-gray-700 hover:text-white px-2 py-1 rounded-md text-sm font-medium"
                    >Reports</a>

                    <a href="#"
                       class="hover:bg-gray-700 hover:text-white px-2 py-1 rounded-md text-sm font-medium"
                    >Contacts</a>

                    <a href="#"
                       class="hover:bg-gray-700 hover:text-white px-2 py-1 rounded-md text-sm font-medium"
                    >Gift center</a>

                    <a href="#"
                       class="hover:bg-gray-700 hover:text-white px-2 py-1 rounded-md text-sm font-medium"
                    >Loans & debts center</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </nav>
      </div>
      <main class="mt-20 relative">
        <slot></slot>
      </main>
    </div>

    <toaster />
  </div>
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
      default: '',
    },
  },

  data() {
    return {
    };
  },

  watch: {
    title(title) {
      this.updatePageTitle(title);
    }
  },

  mounted() {
    this.updatePageTitle(this.title);
  },


  methods: {
    updatePageTitle(title) {
      document.title = title ? `${title} | Butler` : 'Butler';
    },
  },
};
</script>
