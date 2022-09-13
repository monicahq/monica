<template>
  <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 dark:bg-gray-800 sm:justify-center sm:pt-0">
    <div>
      <slot name="logo" />
    </div>

    <div
      class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md dark:bg-gray-900 dark:shadow-gray-700 sm:max-w-md sm:rounded-lg">
      <slot />
    </div>
  </div>
</template>
