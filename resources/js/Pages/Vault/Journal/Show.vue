<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import { trans } from 'laravel-vue-i18n';
import { ChevronRight } from 'lucide-vue-next';
const props = defineProps({
  layoutData: Object,
  data: Object,
});

const form = useForm({});

const destroy = () => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    form.delete(props.data.url.destroy, {
      onFinish: () => {
        localStorage.success = trans('Changes saved');
      },
    });
  }
};
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b sm:border-gray-300 dark:border-gray-700">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-center gap-1 text-sm">
          <div class="text-gray-600 dark:text-gray-400">
            {{ $t('You are here:') }}
          </div>
          <div class="inline">
            <Link :href="layoutData.vault.url.journals" class="text-blue-500 hover:underline">
              {{ $t('Journals') }}
            </Link>
          </div>
          <div class="relative inline">
            <ChevronRight class="h-3 w-3" />
          </div>
          <div class="inline">
            {{ data.name }}
          </div>
        </div>
      </div>
    </nav>

    <main class="sm:mt-10 relative">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <h1 class="text-2xl" :class="data.description ? 'mb-4' : 'mb-8'">{{ data.name }}</h1>

        <p v-if="data.description" class="mb-8">{{ data.description }}</p>

        <!-- tabs -->
        <div class="flex justify-center">
          <div class="mb-8 inline-flex rounded-md shadow-xs">
            <Link
              :href="data.url.show"
              class="inline-flex items-center rounded-s-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-400 dark:font-bold dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-blue-500">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="me-2 h-4 w-4">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
              </svg>

              {{ $t('Journal entries') }}
            </Link>

            <Link
              :href="data.url.photo_index"
              :class="{ 'bg-gray-100 text-blue-700 dark:bg-gray-400 dark:font-bold': defaultTab === 'life_events' }"
              class="inline-flex items-center rounded-e-md border-b border-s border-t border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-blue-500">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="me-2 h-4 w-4">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
              </svg>

              {{ $t('Photos') }}
            </Link>
          </div>
        </div>

        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="p-3 sm:p-0">
            <!-- years -->
            <p v-if="data.years.length > 0" class="mb-2 font-medium">
              <span class="me-1"> 📆 </span> {{ $t('Years') }}
            </p>
            <ul v-if="data.years.length > 0" class="mb-8">
              <li v-for="year in data.years" :key="year.year" class="mb-2 flex items-center justify-between last:mb-0">
                <Link :href="year.url.show" class="text-blue-500 hover:underline">{{ year.year }}</Link>
                <span class="text-sm text-gray-400">{{ year.posts }}</span>
              </li>
            </ul>

            <!-- tags -->
            <p v-if="data.tags.length > 0" class="mb-2 font-medium">
              <span class="me-1"> ⚡ </span> {{ $t('All tags') }}
            </p>
            <ul v-if="data.tags.length > 0">
              <li v-for="tag in data.tags" :key="tag.id" class="mb-2 flex items-center justify-between">
                <span>{{ tag.name }}</span>
                <span class="text-sm text-gray-400">{{ tag.count }}</span>
              </li>
            </ul>

            <Link :href="data.url.journal_metrics" class="mb-2 mt-6 block text-sm text-blue-500 hover:underline">{{
              $t('Edit journal metrics')
            }}</Link>
            <Link :href="data.url.edit" class="mb-2 block text-sm text-blue-500 hover:underline">{{
              $t('Edit journal information')
            }}</Link>
            <span @click="destroy()" class="block cursor-pointer text-sm text-blue-500 hover:underline">{{
              $t('Delete journal')
            }}</span>
          </div>

          <!-- middle -->
          <div class="p-3 sm:p-0">
            <!-- all months in the year -->
            <div class="mb-2 grid grid-cols-12 gap-2">
              <div v-for="month in data.months" :key="month.id" class="text-center">
                <div class="mb-1 text-xs">{{ month.month }}</div>

                <div :class="month.color" class="h-3 rounded-md border border-gray-200 dark:border-gray-700"></div>
              </div>
            </div>

            <!-- list of posts -->
            <ul
              v-if="data.months.length > 0 && data.years.length > 0"
              class="post-list mb-6 rounded-lg border border-b-0 border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <!-- loop on months -->
              <li v-for="month in data.months" :key="month.id">
                <div v-if="month.posts.length > 0">
                  <div
                    class="border-b border-gray-200 bg-gray-100 px-5 py-2 text-sm font-semibold dark:border-gray-700 dark:bg-gray-900">
                    {{ month.month_human_format }}
                  </div>

                  <ul class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    <li
                      v-for="post in month.posts"
                      :key="post.id"
                      class="flex items-center border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                      <!-- written at -->
                      <div class="me-4 rounded-lg border border-gray-200 p-2 text-center leading-tight">
                        <span class="block text-xs uppercase">{{ post.written_at_day }}</span>
                        <span class="text-xl">{{ post.written_at_day_number }}</span>
                      </div>

                      <!-- title -->
                      <div class="flex w-full items-center justify-between">
                        <!-- title and excerpt -->
                        <div>
                          <span>
                            <Link :href="post.url.show" class="text-blue-500 hover:underline">{{ post.title }}</Link>
                          </span>
                          <p v-if="post.excerpt">{{ post.excerpt }}</p>
                        </div>

                        <!-- photo -->
                        <div
                          v-if="post.photo"
                          class="mr-2 rounded-md border border-gray-200 p-1 shadow-xs hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                          <img :src="post.photo.url.show" :alt="post.photo.id" />
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
              </li>
            </ul>

            <!-- blank state -->
            <div v-else class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <p class="p-5 text-center">
                <img src="/img/journal_blank_index.svg" class="mx-auto block h-32 w-32 py-6" />

                {{ $t('The journal lets you document your life with your own words.') }}
              </p>
            </div>
          </div>

          <!-- right -->
          <div class="p-3 sm:p-0">
            <!-- cta -->
            <div class="mb-8 flex justify-center">
              <pretty-link
                v-if="layoutData.vault.permission.at_least_editor"
                :href="data.url.create"
                :text="$t('Create a post')"
                :icon="'plus'" />
            </div>

            <!-- slices of life -->
            <p class="mb-2 font-medium"><span class="me-1"> 🍕 </span> {{ $t('Slices of life') }}</p>
            <div v-if="data.slices.length > 0" class="mb-2">
              <div v-for="slice in data.slices" :key="slice.id" class="mb-6 last:mb-0">
                <img v-if="slice.cover_image" class="h-32 w-full rounded-t" :src="slice.cover_image" alt="" />
                <div
                  class="rounded-b border-b border-s border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
                  :class="slice.cover_image ? '' : 'border-t'">
                  <Link :href="slice.url.show" class="font-semibold">{{ slice.name }}</Link>
                  <p class="text-xs text-gray-600">{{ slice.date_range }}</p>
                </div>
              </div>
            </div>

            <!-- no slices of life yet -->
            <div
              v-if="data.slices.length === 0"
              class="mb-1 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <img src="/img/journal_slice_of_life_blank.svg" :alt="$t('Journal')" class="mx-auto mt-4 h-14 w-14" />
              <p class="px-5 pb-5 pt-2 text-center">{{ $t('Group journal entries together with slices of life.') }}</p>
            </div>

            <div>
              <Link :href="data.url.slice_index" class="text-sm text-blue-500 hover:underline">{{
                $t('View all')
              }}</Link>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<style lang="scss" scoped>
.post-list {
  li:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  li:last-child {
    border-bottom: 0;
  }

  li:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}

.special-grid {
  grid-template-columns: 150px 1fr 200px;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}
</style>
