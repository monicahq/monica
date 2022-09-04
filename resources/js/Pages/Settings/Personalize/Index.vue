<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('app.breadcrumb_location') }}
            </li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings" class="text-blue-500 hover:underline">
                {{ $t('app.breadcrumb_settings') }}
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
            <li class="inline">
              {{ $t('app.breadcrumb_settings_personalize') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-md px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <h2 class="mb-6 text-center text-lg">
          {{ $t('settings.personalize_title') }}
        </h2>
        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
          <ul>
            <li class="mb-2 flex">
              <span class="mr-2">📐</span>
              <inertia-link :href="data.url.manage_templates" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_template') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">🥪</span>
              <inertia-link :href="data.url.manage_modules" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_module') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">🥸</span>
              <inertia-link :href="data.url.manage_relationships" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_rel_types') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">👩‍🍼</span>
              <inertia-link :href="data.url.manage_life_event_categories" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_life_event_categories') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">👥</span>
              <inertia-link :href="data.url.manage_group_types" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_group_types') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">🚲</span>
              <inertia-link :href="data.url.manage_activity_types" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_activity_types') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">👩‍🔬</span>
              <inertia-link :href="data.url.manage_pronouns" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_pronouns') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">🚻</span>
              <inertia-link :href="data.url.manage_genders" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_genders') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">🏖</span>
              <inertia-link :href="data.url.manage_address_types" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_adress_types') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">☎️</span>
              <inertia-link :href="data.url.manage_contact_information_types" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_contact_information_types') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">📲</span>
              <inertia-link :href="data.url.manage_call_reasons" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_call_reasons') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">🐱</span>
              <inertia-link :href="data.url.manage_pet_categories" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_pet_categories') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">🎁</span>
              <inertia-link :href="data.url.manage_gift_occasions" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_gift_occasions') }}
              </inertia-link>
            </li>
            <li class="mb-2 flex">
              <span class="mr-2">🎁</span>
              <inertia-link :href="data.url.manage_gift_states" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_gift_states') }}
              </inertia-link>
            </li>
            <li class="flex">
              <span class="mr-2">💵</span>
              <inertia-link :href="data.url.manage_currencies" class="text-blue-500 hover:underline">
                {{ $t('settings.personalize_title_manage_currencies') }}
              </inertia-link>
            </li>
          </ul>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout.vue';

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

<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>
