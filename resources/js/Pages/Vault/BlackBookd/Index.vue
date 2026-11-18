<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';

defineProps({
  layoutData: Object,
  data: Object,
});
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-6 flex items-center justify-between">
          <h1 class="text-xl font-semibold">BlackBookd</h1>
          <Link :href="data.url.pipeline" class="rounded bg-violet-600 px-3 py-1 text-sm text-white hover:bg-violet-500">
            Dating pipeline board
          </Link>
        </div>
        <p class="mb-6 text-sm text-gray-500">
          Private module — encounters, sexual health, and GayHook dating pipeline. Metro labels only; no live geo.
        </p>
        <div class="mb-8 grid gap-4 sm:grid-cols-3">
          <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm text-gray-500">Encounters</p>
            <p class="text-2xl font-semibold">{{ data.statistics.encounters }}</p>
          </div>
          <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm text-gray-500">Health log entries</p>
            <p class="text-2xl font-semibold">{{ data.statistics.health_entries }}</p>
          </div>
          <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm text-gray-500">Pipeline contacts</p>
            <p class="text-2xl font-semibold">{{ data.statistics.pipeline }}</p>
          </div>
        </div>
        <div class="grid gap-8 lg:grid-cols-2">
          <section>
            <h2 class="mb-3 font-medium">Recent encounters</h2>
            <ul class="space-y-2 text-sm">
              <li v-for="item in data.recent_encounters" :key="item.id" class="rounded border border-gray-200 p-3 dark:border-gray-700">
                <span class="text-gray-500">{{ item.occurred_at }}</span>
                — {{ item.location_label || 'No location label' }}
                <span v-if="item.contacts?.length" class="block text-gray-600">With: {{ item.contacts.join(', ') }}</span>
              </li>
            </ul>
          </section>
          <section>
            <h2 class="mb-3 font-medium">Health log</h2>
            <ul class="space-y-2 text-sm">
              <li v-for="item in data.recent_health" :key="item.id" class="rounded border border-gray-200 p-3 dark:border-gray-700">
                {{ item.occurred_on }} — {{ item.entry_type }}
              </li>
            </ul>
            <p class="mt-4 text-xs text-gray-500">
              Partner notification lookback: {{ data.partner_lookback_days }} days (account setting).
            </p>
          </section>
        </div>
      </div>
    </main>
  </layout>
</template>