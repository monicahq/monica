<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>

<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600">You are here:</li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings" class="text-sky-500 hover:text-blue-900"> Settings </inertia-link>
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
            <li class="inline">Personalize your account</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-md px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <h2 class="mb-6 text-center text-lg">Personalize the account</h2>
        <div class="rounded-lg border border-gray-200 bg-white p-5">
          <ul>
            <li class="mb-2">
              <span class="mr-2">📐</span>
              <inertia-link :href="data.url.manage_templates" class="text-sky-500 hover:text-blue-900">
                Manage templates
              </inertia-link>
            </li>
            <li class="mb-2">
              <span class="mr-2">🥪</span>
              <inertia-link :href="data.url.manage_modules" class="text-sky-500 hover:text-blue-900">
                Manage modules
              </inertia-link>
            </li>
            <li class="mb-2">
              <span class="mr-2">🥸</span>
              <inertia-link :href="data.url.manage_relationships" class="text-sky-500 hover:text-blue-900">
                Manage relationship types
              </inertia-link>
            </li>
            <li class="mb-2">
              <span class="mr-2">🚲</span>
              <inertia-link :href="data.url.manage_activity_types" class="text-sky-500 hover:text-blue-900">
                Manage activity types
              </inertia-link>
            </li>
            <li class="mb-2">
              <span class="mr-2">👩‍🔬</span>
              <inertia-link :href="data.url.manage_pronouns" class="text-sky-500 hover:text-blue-900">
                Manage pronouns
              </inertia-link>
            </li>
            <li class="mb-2">
              <span class="mr-2">🚻</span>
              <inertia-link :href="data.url.manage_genders" class="text-sky-500 hover:text-blue-900">
                Manage genders
              </inertia-link>
            </li>
            <li class="mb-2">
              <span class="mr-2">🏖</span>
              <inertia-link :href="data.url.manage_address_types" class="text-sky-500 hover:text-blue-900">
                Manage address types
              </inertia-link>
            </li>
            <li class="mb-2">
              <span class="mr-2">☎️</span>
              <inertia-link :href="data.url.manage_contact_information_types" class="text-sky-500 hover:text-blue-900">
                Manage contact information types
              </inertia-link>
            </li>
            <li class="mb-2">
              <span class="mr-2">📲</span>
              <inertia-link :href="data.url.manage_call_reasons" class="text-sky-500 hover:text-blue-900">
                Manage call reasons
              </inertia-link>
            </li>
            <li class="mb-2">
              <span class="mr-2">🐱</span>
              <inertia-link :href="data.url.manage_pet_categories" class="text-sky-500 hover:text-blue-900">
                Manage pet categories
              </inertia-link>
            </li>
            <li class="">
              <span class="mr-2">💵</span>
              <inertia-link :href="data.url.manage_currencies" class="text-sky-500 hover:text-blue-900">
                Manage currencies
              </inertia-link>
            </li>
          </ul>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';

export default {
  components: {
    Layout,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
  },
};
</script>
