<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';

defineProps({
  layoutData: Object,
  data: Object,
});

const stageLabel = (stage) => stage.replaceAll('_', ' ');
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-[1400px] overflow-x-auto px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
          <h1 class="text-xl font-semibold">Dating pipeline</h1>
          <Link :href="layoutData.vault.url.blackbookd" class="text-sm text-violet-500 hover:underline">Back to BlackBookd</Link>
        </div>
        <div class="flex gap-3 pb-4">
          <div
            v-for="stage in data.stages"
            :key="stage"
            class="min-w-[220px] flex-1 rounded-lg border border-gray-200 bg-slate-50 p-2 dark:border-gray-700 dark:bg-slate-900">
            <h3 class="mb-2 text-center text-xs font-semibold uppercase tracking-wide text-violet-600">
              {{ stageLabel(stage) }}
            </h3>
            <div
              v-for="card in data.columns[stage]"
              :key="card.id"
              class="mb-2 rounded border border-gray-200 bg-white p-2 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800"
              :class="{ 'opacity-50': card.boundary_held_do_not_reengage }">
              <p class="font-medium">{{ card.handle }}</p>
              <p class="text-xs text-gray-500">{{ card.contact_name }}</p>
              <p v-if="card.metro_location" class="text-xs">{{ card.metro_location }}</p>
              <p v-if="card.next_step" class="mt-1 text-xs">Next: {{ card.next_step }}</p>
              <p v-if="card.long_term_fit_score" class="text-xs text-emerald-600">Fit: {{ card.long_term_fit_score }}/10</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>