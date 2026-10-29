<script setup>
import { ref, onMounted, onUnmounted, computed, reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { Upload, FileText, CheckCircle, XCircle, AlertCircle, ArrowLeft, Download, SquareX } from 'lucide-vue-next';
import Layout from '@/Layouts/Layout.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
  importJob: Object,
});

const file = ref(null);
const uploading = ref(false);
const polling = ref(null);
const errorMessage = ref('');

const importJob = reactive(props.importJob ? { ...props.importJob } : null);

const hasActiveImport = computed(() => {
  return importJob && (importJob.status === 'pending' || importJob.status === 'processing');
});

const hasCompletedImport = computed(() => {
  return (
    importJob && (importJob.status === 'completed' || importJob.status === 'failed' || importJob.status === 'cancelled')
  );
});

const progressPercent = computed(() => {
  if (!importJob || importJob.total_rows === 0) return 0;
  const done = importJob.processed_rows + importJob.failed_rows + importJob.skipped_rows;
  return Math.round((done / importJob.total_rows) * 100);
});

const cancelUrl = computed(() => {
  if (!importJob) return null;
  return props.data.url.cancel.replace('__IMPORT_JOB_ID__', importJob.id);
});

const cancelImport = () => {
  if (!confirm(trans('Are you sure you want to cancel this import?'))) return;

  axios.delete(cancelUrl.value).then(() => {
    importJob.status = 'cancelled';
    if (polling.value) {
      clearInterval(polling.value);
      polling.value = null;
    }
  });
};

const importStatusText = computed(() => {
  if (!importJob) return '';
  if (importJob.status === 'pending') return trans('Starting import...');
  if (importJob.status === 'processing') {
    return trans('Processing :processed of :total contacts...', {
      processed: importJob.processed_rows,
      total: importJob.total_rows,
    });
  }
  if (importJob.status === 'completed') return trans('Import completed');
  if (importJob.status === 'failed') return trans('Import failed');
  return '';
});

const handleFileChange = (event) => {
  file.value = event.target.files[0];
};

const submit = () => {
  if (!file.value) return;

  errorMessage.value = '';
  uploading.value = true;
  const formData = new FormData();
  formData.append('file', file.value);

  axios
    .post(props.data.url.import, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    .then((response) => {
      router.visit(response.data.data);
    })
    .catch((error) => {
      uploading.value = false;
      if (error.response?.data?.errors?.file) {
        errorMessage.value = error.response.data.errors.file[0];
      } else if (error.response?.data?.message) {
        errorMessage.value = error.response.data.message;
      } else if (error.message) {
        errorMessage.value = error.message;
      } else {
        errorMessage.value = trans('Upload failed. Please try again.');
      }
    });
};

const pollProgress = () => {
  if (!importJob) return;

  polling.value = setInterval(() => {
    axios.get(props.data.url.import + '/' + importJob.id + '/progress').then((response) => {
      importJob.processed_rows = response.data.processed_rows;
      importJob.failed_rows = response.data.failed_rows;
      importJob.skipped_rows = response.data.skipped_rows;
      importJob.status = response.data.status;
      importJob.total_rows = response.data.total_rows;
      importJob.error_log = response.data.error_log;
      importJob.errors = response.data.errors;
      importJob.errors_csv_url = response.data.errors_csv_url;
      importJob.completed_at = response.data.completed_at;
      if (
        response.data.status === 'completed' ||
        response.data.status === 'failed' ||
        response.data.status === 'cancelled'
      ) {
        clearInterval(polling.value);
        polling.value = null;
      }
    });
  }, 2000);
};

onMounted(() => {
  if (hasActiveImport.value) {
    pollProgress();
  }
});

onUnmounted(() => {
  if (polling.value) {
    clearInterval(polling.value);
  }
});
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <Link :href="data.url.contact.index" class="text-blue-500 hover:underline">
                {{ $t('Contacts') }}
              </Link>
            </li>
            <li class="relative me-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">
              {{ $t('Import contacts') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-lg px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- Upload form (shown when no active or completed import) -->
        <div
          v-if="!importJob"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5 dark:border-gray-700 dark:bg-blue-900">
            <div class="flex items-center justify-center gap-2">
              <Upload class="h-5 w-5" />
              <h1 class="text-center text-2xl font-medium">
                {{ $t('Import contacts from a file') }}
              </h1>
            </div>
          </div>

          <form @submit.prevent="submit">
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <div class="mb-4">
                <label
                  class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-8 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:hover:bg-gray-700">
                  <FileText class="mb-2 h-10 w-10 text-gray-400" />
                  <p class="mb-2 text-sm text-gray-500">
                    <span class="font-semibold">{{ $t('Click to upload') }}</span>
                    {{ $t('or drag and drop') }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ $t('.vcf, .csv files') }}
                  </p>
                  <input type="file" accept=".vcf,.vcard,.csv" class="hidden" @change="handleFileChange" />
                </label>
              </div>

              <div v-if="file" class="mb-4 flex items-center gap-2 rounded-md bg-gray-50 p-3 dark:bg-gray-800">
                <FileText class="h-4 w-4 text-blue-500" />
                <span class="text-sm">{{ file.name }}</span>
                <span class="text-xs text-gray-500">({{ (file.size / 1024).toFixed(1) }} KB)</span>
              </div>

              <div
                v-if="errorMessage"
                class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-600 dark:bg-red-900 dark:text-red-200">
                <div class="flex items-center gap-2">
                  <XCircle class="h-4 w-4 shrink-0" />
                  <span>{{ errorMessage }}</span>
                </div>
              </div>
            </div>

            <div class="flex justify-between p-5">
              <PrettyLink :href="data.url.contact.index" :text="$t('Cancel')" :class="'me-3'" />
              <button
                type="submit"
                :disabled="!file || uploading"
                class="rounded-md bg-blue-500 px-4 py-2 text-sm text-white hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50">
                <span v-if="uploading">{{ $t('Uploading...') }}</span>
                <span v-else>{{ $t('Import') }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Active import progress -->
        <div
          v-else-if="hasActiveImport"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5 dark:border-gray-700 dark:bg-blue-900">
            <h1 class="text-center text-2xl font-medium">
              {{ $t('Import in progress') }}
            </h1>
          </div>

          <div class="p-5">
            <p class="mb-4 text-center text-sm text-gray-600 dark:text-gray-400">
              {{ importStatusText }}
            </p>

            <div class="mb-2 h-4 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
              <div
                class="h-full rounded-full bg-blue-500 transition-all duration-500"
                :style="{ width: progressPercent + '%' }" />
            </div>

            <div class="flex justify-between text-xs text-gray-500">
              <span>{{ $t(':processed processed', { processed: importJob.processed_rows }) }}</span>
              <span>{{ progressPercent }}%</span>
              <span>{{ $t(':total total', { total: importJob.total_rows }) }}</span>
            </div>

            <div class="mt-4 flex justify-center">
              <button
                @click="cancelImport"
                class="inline-flex items-center gap-1 rounded-md border border-red-300 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900">
                <SquareX class="h-4 w-4" />
                {{ $t('Cancel import') }}
              </button>
            </div>

            <div v-if="importJob.failed_rows > 0" class="mt-2 flex items-center gap-1 text-sm text-red-600">
              <XCircle class="h-4 w-4" />
              <span>{{ $t(':failed failed', { failed: importJob.failed_rows }) }}</span>
            </div>
            <div v-if="importJob.skipped_rows > 0" class="flex items-center gap-1 text-sm text-amber-600">
              <AlertCircle class="h-4 w-4" />
              <span>{{ $t(':skipped skipped', { skipped: importJob.skipped_rows }) }}</span>
            </div>
          </div>
        </div>

        <!-- Completed import results -->
        <div
          v-else-if="hasCompletedImport"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <div
            class="section-head border-b p-5"
            :class="
              importJob.status === 'completed'
                ? 'border-gray-200 bg-green-50 dark:border-gray-700 dark:bg-green-900'
                : 'border-gray-200 bg-red-50 dark:border-gray-700 dark:bg-red-900'
            ">
            <div class="flex items-center justify-center gap-2">
              <CheckCircle v-if="importJob.status === 'completed'" class="h-6 w-6 text-green-500" />
              <XCircle v-else class="h-6 w-6 text-red-500" />
              <h1 class="text-center text-2xl font-medium">
                <template v-if="importJob.status === 'completed'">{{ $t('Import completed') }}</template>
                <template v-else>{{ $t('Import failed') }}</template>
              </h1>
            </div>
          </div>

          <div class="p-5">
            <div class="mb-4 grid grid-cols-3 gap-4 text-center">
              <div>
                <p class="text-2xl font-bold text-green-600">{{ importJob.processed_rows }}</p>
                <p class="text-xs text-gray-500">{{ $t('Imported') }}</p>
              </div>
              <div>
                <p class="text-2xl font-bold text-amber-600">{{ importJob.skipped_rows }}</p>
                <p class="text-xs text-gray-500">{{ $t('Skipped') }}</p>
              </div>
              <div>
                <p class="text-2xl font-bold" :class="importJob.failed_rows > 0 ? 'text-red-600' : 'text-gray-600'">
                  {{ importJob.failed_rows }}
                </p>
                <p class="text-xs text-gray-500">{{ $t('Failed') }}</p>
              </div>
            </div>

            <div
              v-if="importJob.error_log"
              class="mb-4 max-h-40 overflow-y-auto rounded-md bg-gray-50 p-3 dark:bg-gray-800">
              <p class="mb-1 text-xs font-semibold text-gray-500">{{ $t('Error details:') }}</p>
              <pre class="whitespace-pre-wrap text-xs text-red-600">{{ importJob.error_log }}</pre>
            </div>

            <div
              v-if="importJob.errors_csv_url || (importJob.skipped_rows > 0 && hasCompletedImport)"
              class="mb-4 rounded-md bg-amber-50 p-3 text-center dark:bg-amber-900">
              <p class="mb-2 text-xs font-medium text-amber-700 dark:text-amber-200">
                {{ $t(':skipped rows were skipped due to validation errors.', { skipped: importJob.skipped_rows }) }}
              </p>
              <a
                v-if="importJob.errors_csv_url"
                :href="importJob.errors_csv_url"
                class="inline-flex items-center gap-1 text-sm text-blue-500 hover:underline">
                <Download class="h-4 w-4" />
                {{ $t('Download error report (CSV)') }}
              </a>
            </div>

            <div class="flex justify-center">
              <Link
                :href="data.url.contact.index"
                class="inline-flex items-center gap-1 text-sm text-blue-500 hover:underline">
                <ArrowLeft class="h-4 w-4" />
                {{ $t('Back to contacts') }}
              </Link>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>
