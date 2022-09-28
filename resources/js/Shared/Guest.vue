<script setup>
import { Link } from '@inertiajs/inertia-vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
</script>

<template>
  <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 dark:bg-gray-800 sm:justify-center sm:pt-0">
    <div>
      <Link href="/">
        <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
      </Link>
    </div>

    <div class="mt-6 w-full overflow-hidden bg-white px-6 py-6 shadow-md dark:bg-gray-900 sm:max-w-md sm:rounded-lg">
      <slot />
    </div>
  </div>
</template>
