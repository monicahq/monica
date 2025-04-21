<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import ContactCard from '@/Shared/ContactCard.vue';

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
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <Link :href="layoutData.vault.url.journals" class="text-blue-500 hover:underline">
                {{ $t('Journals') }}
              </Link>
            </li>
            <li class="relative me-2 inline">
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
              <Link :href="data.url.back" class="text-blue-500 hover:underline">
                {{ data.journal.name }}
              </Link>
            </li>
            <li class="relative me-2 inline">
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

    <main class="relative sm:mt-8">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="me-8">
            <!-- post previous/next -->
            <div class="mb-4 flex justify-between">
              <!-- previous post -->
              <div v-if="data.previousPost" class="flex items-center">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  class="me-1 h-4 w-4 text-gray-400">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 15.75L3 12m0 0l3.75-3.75M3 12h18" />
                </svg>

                <Link
                  v-if="data.previousPost"
                  :href="data.previousPost.url.show"
                  class="text-sm text-gray-400 hover:underline">
                  {{ data.previousPost.title }}
                </Link>
              </div>
              <div v-else>&nbsp;</div>

              <!-- next post -->
              <div v-if="data.nextPost" class="flex items-center">
                <Link v-if="data.nextPost" :href="data.nextPost.url.show" class="text-sm text-gray-400 hover:underline">
                  {{ data.nextPost.title }}
                </Link>

                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  class="ms-1 h-4 w-4 text-gray-400">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                </svg>
              </div>
              <div v-else>&nbsp;</div>
            </div>

            <div class="post relative rounded-xs bg-white dark:bg-gray-900">
              <!-- date of the post -->
              <p class="mb-2 text-sm text-gray-400">{{ data.written_at }}</p>

              <!-- tags -->
              <ul v-if="data.tags" class="p0 list mb-3">
                <li
                  v-for="tag in data.tags"
                  :key="tag.id"
                  class="me-2 inline-block rounded-xs bg-neutral-200 px-2 py-1 text-xs font-semibold text-neutral-500 last:me-0 dark:bg-neutral-800">
                  {{ tag.name }}
                </li>
              </ul>

              <!-- title -->
              <h1 v-if="data.title_exists" class="mb-4 text-2xl font-medium">{{ data.title }}</h1>

              <!-- photos -->
              <div v-if="data.photos.length > 0" class="mb-4 flex">
                <div
                  v-for="photo in data.photos"
                  :key="photo.id"
                  class="me-2 rounded-md border border-gray-200 p-2 shadow-xs hover:bg-slate-50 hover:shadow-lg dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                  <img :src="photo.url.display" :alt="photo.name" />
                </div>
              </div>

              <!-- sections -->
              <div v-if="data.sections.length > 0" class="prose">
                <div v-for="section in data.sections" :key="section.id" class="mb-4">
                  <div v-if="data.sections.length > 1" class="mb-1 italic text-gray-400">
                    {{ section.label }}
                  </div>

                  <div class="mb-6" v-html="section.content"></div>
                </div>
              </div>

              <!-- no section yet -->
              <div v-else class="text-gray-400">{{ $t('This post has no content yet.') }}</div>
            </div>
          </div>

          <!-- right -->
          <div>
            <!-- contacts -->
            <div v-if="data.contacts.length > 0" class="mb-4">
              <p class="mb-2 text-sm font-semibold">{{ $t('Contacts in this post') }}</p>

              <div v-for="contact in data.contacts" :key="contact.id" class="mb-2 block">
                <contact-card :contact="contact" :avatar-classes="'h-5 w-5 rounded-full me-2'" :display-name="true" />
              </div>
            </div>

            <!-- slices of life -->
            <div v-if="data.sliceOfLife" class="mb-4">
              <p class="mb-2 text-sm font-semibold">{{ $t('Slice of life') }}</p>
              <div class="mb-6 last:mb-0">
                <div
                  class="rounded-xs border-b border-s border-t border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
                  :class="data.sliceOfLife.cover_image ? '' : 'border-t'">
                  <Link :href="data.sliceOfLife.url.show" class="font-semibold">{{ data.sliceOfLife.name }}</Link>
                  <p class="text-xs text-gray-600">{{ data.sliceOfLife.date_range }}</p>
                </div>
              </div>
            </div>

            <!-- post metrics -->
            <div v-if="data.journalMetrics.length > 0" class="mb-4">
              <p class="mb-2 text-sm font-semibold">{{ $t('Post metrics') }}</p>
              <div v-for="journalMetric in data.journalMetrics" :key="journalMetric.id">
                <div class="mb-1 flex items-center justify-between font-semibold">
                  <span>{{ journalMetric.label }}</span>

                  <span class="font-mono text-sm">{{ journalMetric.total }}</span>
                </div>
                <ul
                  v-if="journalMetric.post_metrics.length > 0"
                  class="mb-2 rounded-xs border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                  <li
                    v-for="postMetric in journalMetric.post_metrics"
                    :key="postMetric.id"
                    class="item-list flex items-center justify-between border-b border-gray-200 px-3 py-1 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                    <span class="italic">{{ postMetric.label }}</span>

                    <div class="flex items-center">
                      <span class="font-mono text-sm">{{ postMetric.value }}</span>
                    </div>
                  </li>
                </ul>
              </div>
            </div>

            <!-- mood tracking events -->
            <div v-if="data.moodTrackingEvents.length > 0">
              <p class="mb-2 text-sm font-semibold">{{ $t('Your mood that you logged at this date') }}</p>

              <ul class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <li
                  v-for="mood in data.moodTrackingEvents"
                  :key="mood.id"
                  class="item-list border-b border-gray-200 p-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                  <span>{{ mood.mood_tracking_parameter.label }}</span>
                  <span class="block text-sm" v-if="mood.number_of_hours_slept">
                    {{
                      $tChoice('Slept :count hour|Slept :count hours', mood.number_of_hours_slept, {
                        count: mood.number_of_hours_slept,
                      })
                    }}
                  </span>
                  <span v-if="mood.note" class="block text-sm">{{ mood.note }}</span>
                </li>
              </ul>
            </div>

            <!-- options -->
            <ul class="mb-6 text-sm">
              <li class="flex items-center">
                <Link :href="data.url.edit" class="text-blue-500 hover:underline">
                  {{ $t('Edit post') }}
                </Link>
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
}

[dir='ltr'] .post {
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

[dir='rtl'] .post {
  &:before {
    background: #fafafa;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
    right: -5px;
    top: 4px;
    transform: rotate(-2.5deg);
  }

  &:after {
    background: #f6f6f6;
    box-shadow: 0 0 3px rgba(0, 0, 0, 0.2);
    left: -3px;
    top: 1px;
    transform: rotate(1.4deg);
  }
}

.dark .post {
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.05) !important;

  &:before {
    background: #09090b !important;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.1) !important;
    left: -5px !important;
    top: 4px !important;
    transform: rotate(-2.5deg) !important;
  }

  &:after {
    background: #171717 !important;
    box-shadow: 0 0 3px rgba(255, 255, 255, 0.1) !important;
    right: -3px !important;
    top: 1px !important;
    transform: rotate(1.4deg) !important;
  }
}

.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
