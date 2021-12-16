<template>
  <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    <div>
      <inertia-link href="/">
        <breeze-application-logo class="w-20 h-20 fill-current text-gray-500" />
      </inertia-link>
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-md overflow-hidden sm:rounded-lg">
      <slot />
    </div>
  </div>
</template>

<script>
import BreezeApplicationLogo from '@/Components/ApplicationLogo.vue';


export default {
  components: {
    BreezeApplicationLogo,
  }
};
</script>
