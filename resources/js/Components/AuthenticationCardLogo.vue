<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
</script>

<template>
  <Link :href="'/'">
    <div class="flex items-center">
      <ApplicationLogo />
    </div>
  </Link>
</template>
