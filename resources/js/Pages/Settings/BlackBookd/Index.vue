<script setup>
import { useForm } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const form = useForm({
  blackbookd_enabled: props.data.blackbookd_enabled,
  blackbookd_blur_dashboard: props.data.blackbookd_blur_dashboard,
  blackbookd_partner_lookback_days: props.data.blackbookd_partner_lookback_days,
});

const submit = () => {
  form.put(props.data.url.update);
};
</script>

<template>
  <Layout :layout-data="layoutData">
    <main class="mx-auto max-w-lg px-4 py-8 sm:mt-20">
      <h1 class="mb-2 text-xl font-semibold">BlackBookd module</h1>
      <p class="mb-6 text-sm text-gray-500">
        Private relationship, health, and dating data for this account. Hidden until enabled. GayHook uses the REST API with the same Sanctum tokens as Monica.
      </p>
      <form class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900" @submit.prevent="submit">
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.blackbookd_enabled" type="checkbox" class="rounded" />
          Enable BlackBookd private module
        </label>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.blackbookd_blur_dashboard" type="checkbox" class="rounded" />
          Blur BlackBookd stats on vault dashboard
        </label>
        <label class="block text-sm">
          Partner notification lookback (days)
          <input v-model.number="form.blackbookd_partner_lookback_days" type="number" min="1" max="730" class="mt-1 w-full rounded border px-2 py-1 dark:bg-gray-800" />
        </label>
        <button type="submit" class="rounded bg-violet-600 px-4 py-2 text-sm text-white hover:bg-violet-500" :disabled="form.processing">
          Save
        </button>
      </form>
      <p class="mt-6">
        <a :href="data.url.export" class="text-sm text-violet-500 hover:underline">Download JSON export of BlackBookd data</a>
      </p>
    </main>
  </Layout>
</template>