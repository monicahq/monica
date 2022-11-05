<script setup>
import Layout from '@/Shared/Layout.vue';

defineProps({
  layoutData: Object,
  data: Object,
});
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('app.breadcrumb_location') }}
            </li>
            <li class="mr-2 inline">
              <inertia-link :href="layoutData.vault.url.journals" class="text-blue-500 hover:underline">
                {{ $t('app.breadcrumb_journal_index') }}
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
              <inertia-link :href="data.url.back" class="text-blue-500 hover:underline">
                {{ data.journal.name }}
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
            <li class="relative inline">
              {{ data.title }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="mr-8">
            <div class="post relative rounded bg-white">
              <p class="mb-2 text-sm text-gray-400">{{ data.written_at }}</p>

              <ul v-if="data.tags" class="p0 list mb-3">
                <li
                  v-for="tag in data.tags"
                  :key="tag.id"
                  class="mr-2 inline-block rounded bg-neutral-200 py-1 px-2 text-xs font-semibold text-neutral-500 last:mr-0">
                  {{ tag.name }}
                </li>
              </ul>

              <h1 v-if="data.title_exists" class="mb-4 text-2xl font-medium">{{ data.title }}</h1>

              <!-- sections -->
              <div v-if="data.sections.length > 0" class="prose">
                <div v-for="section in data.sections" :key="section.id" class="mb-4">
                  <div class="mb-1 italic text-gray-400">
                    {{ section.label }}
                  </div>

                  <div class="mb-6">{{ section.content }}</div>
                </div>
              </div>

              <!-- no section yet -->
              <div v-else class="text-gray-400">This post has no content yet.</div>
            </div>
          </div>

          <!-- right -->
          <div class="">
            <p class="mb-2 font-bold">{{ $t('vault.journal_show_options') }}</p>
            <ul class="mb-6 text-sm">
              <li class="flex items-center">
                <svg
                  class="mr-2 h-4 w-4"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H12m-8.457 3.077l1.41-.513m14.095-5.13l1.41-.513M5.106 17.785l1.15-.964m11.49-9.642l1.149-.964M7.501 19.795l.75-1.3m7.5-12.99l.75-1.3m-6.063 16.658l.26-1.477m2.605-14.772l.26-1.477m0 17.726l-.26-1.477M10.698 4.614l-.26-1.477M16.5 19.794l-.75-1.299M7.5 4.205L12 12m6.894 5.785l-1.149-.964M6.256 7.178l-1.15-.964m15.352 8.864l-1.41-.513M4.954 9.435l-1.41-.514M12.002 12l-3.75 6.495" />
                </svg>

                <inertia-link :href="data.url.edit" class="text-blue-500 hover:underline">{{
                  $t('app.edit')
                }}</inertia-link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<style lang="scss" scoped>
.special-grid {
  grid-template-columns: 1fr 300px;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}

.post {
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
  min-height: 300px;
  padding: 24px;

  &:before,
  &:after {
    content: '';
    height: 98%;
    position: absolute;
    width: 100%;
    z-index: -1;
  }

  &:before {
    background: #fafafa;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
    left: -5px;
    top: 4px;
    transform: rotate(-2.5deg);
  }

  &:after {
    background: #f6f6f6;
    box-shadow: 0 0 3px rgba(0, 0, 0, 0.2);
    right: -3px;
    top: 1px;
    transform: rotate(1.4deg);
  }
}
</style>
