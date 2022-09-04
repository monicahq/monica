<template>
  <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
    <div>
      <inertia-link href="/">
        <breeze-application-logo class="h-20 w-20 fill-current text-gray-500" />
      </inertia-link>
    </div>

    <div class="mt-6 w-full overflow-hidden bg-white px-6 py-6 shadow-md dark:bg-gray-900 sm:max-w-md sm:rounded-lg">
      <slot />
    </div>
  </div>
</template>

<script>
import BreezeApplicationLogo from '@/Components/ApplicationLogo.vue';

export default {
  components: {
    BreezeApplicationLogo,
  },
};
</script>
