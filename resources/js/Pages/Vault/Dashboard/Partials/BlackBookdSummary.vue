<script setup>
defineProps({
  summary: Object,
});
</script>

<template>
  <div v-if="summary" class="mb-6 rounded-lg border border-violet-500/30 bg-violet-950/20 p-4">
    <h3 class="mb-2 text-sm font-semibold text-violet-300">BlackBookd</h3>
    <template v-if="summary.blurred">
      <p class="text-sm text-gray-400">Private stats hidden on dashboard.</p>
    </template>
    <template v-else>
      <p class="text-sm">Encounters: {{ summary.encounters }} · Pipeline: {{ summary.pipeline_rows }}</p>
    </template>
    <a :href="summary.url" class="mt-2 inline-block text-sm text-violet-400 hover:underline">Open BlackBookd</a>
  </div>
</template>