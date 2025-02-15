<script setup>
import { Link } from '@inertiajs/vue3';
import ClockIcon from '@/Shared/Icons/ClockIcon.vue';
import { PenTool } from 'lucide-vue-next';

defineProps({
  data: Object,
});
</script>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <PenTool class="h-4 w-4 text-gray-600" />

        <span class="font-semibold"> {{ $t('Posts') }} </span>
      </div>
    </div>

    <!-- posts -->
    <div v-if="data.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <!-- body of the post -->
        <li
          v-for="post in data"
          :key="post.id"
          class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <Link :href="post.url.show" class="mb-2 block text-blue-500 hover:underline">{{ post.title }}</Link>
          <div class="flex items-center text-sm">
            <!-- journal -->
            <p class="me-2">
              {{ $t('in') }}
              <Link :href="post.journal.url.show" class="text-blue-500 hover:underline">{{ post.journal.name }}</Link>
            </p>

            <!-- date -->
            <div class="relative me-3 inline">
              <ClockIcon :time="'four'" />
              {{ post.written_at }}
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-if="data.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_posts.svg" :alt="$t('Notes')" class="mx-auto mt-4 h-20 w-20" />
      <p class="px-5 pb-5 pt-2 text-center">{{ $t('There are no posts yet.') }}</p>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

.icon-note {
  top: -1px;
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
