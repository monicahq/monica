<script setup>
const props = defineProps({
  data: Object,
});
</script>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative mr-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            class="icon-sidebar relative inline h-4 w-4">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6A2.25 2.25 0 016 3.75h1.5m9 0h-9" />
          </svg>
        </span>

        <span class="font-semibold"> Posts </span>
      </div>
    </div>

    <!-- posts -->
    <div v-if="props.data.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <!-- body of the post -->
        <li
          v-for="post in props.data"
          :key="post.id"
          class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
          <inertia-link :href="post.url.show" class="mb-2 block text-blue-500 hover:underline">{{
            post.title
          }}</inertia-link>
          <div class="flex items-center text-sm">
            <!-- journal -->
            <p class="mr-2">
              in
              <inertia-link :href="post.journal.url.show" class="text-blue-500 hover:underline">{{
                post.journal.name
              }}</inertia-link>
            </p>

            <!-- date -->
            <div class="relative mr-3 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-note relative inline h-3 w-3 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ post.written_at }}
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-if="props.data.length == 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_posts.svg" :alt="$t('Notes')" class="mx-auto mt-4 h-20 w-20" />
      <p class="px-5 pb-5 pt-2 text-center">There are no posts yet.</p>
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
