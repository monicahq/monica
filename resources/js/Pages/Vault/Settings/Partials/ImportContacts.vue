<template>
  <div class="mb-12">
    <!-- title + icon -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0">
        <span class="me-1"> 📥 </span>
        {{ $t('Import contacts') }}
      </h3>
    </div>

    <!-- main container -->
    <div class="mb-6 rounded-xs border border-gray-200 bg-white text-sm dark:border-gray-700 dark:bg-gray-900">
      <!-- help banner -->
      <div class="flex rounded-t border-b border-gray-200 bg-slate-50 px-3 py-2 dark:border-gray-700 dark:bg-slate-900">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 me-2 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <p class="font-medium text-slate-800 dark:text-slate-200">
            {{ $t('Import contacts asynchronously in the background.') }}
          </p>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
            {{ $t('Supported headers: first_name, last_name, email, phone, nickname (or a single full name/name column).') }}
          </p>
        </div>
      </div>

      <!-- upload action / active import -->
      <div class="p-5">
        <div v-if="activeJob" class="rounded border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-900 dark:bg-blue-950/20">
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-blue-800 dark:text-blue-300">
              {{ $t('Importing:') }} {{ activeJob.filename }}
            </span>
            <span class="text-xs uppercase px-2 py-0.5 rounded font-mono font-bold" :class="statusClass(activeJob.status)">
              {{ activeJob.status }}
            </span>
          </div>

          <!-- progress info -->
          <div class="space-y-2 text-xs text-slate-600 dark:text-slate-400 mt-2">
            <div class="flex justify-between font-medium">
              <span>{{ $t('Progress:') }} {{ activeJob.processed_rows + activeJob.failed_rows }} / {{ activeJob.total_rows }} {{ $t('rows') }}</span>
              <span v-if="activeJob.estimated_remaining_seconds > 0" class="text-blue-600 dark:text-blue-400">
                {{ $t('ETA:') }} {{ activeJob.estimated_remaining_seconds }}s
              </span>
            </div>
            
            <!-- progress bar -->
            <div class="w-full bg-slate-200 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
              <div class="bg-blue-600 h-full transition-all duration-500" :style="{ width: activeJob.progress_percentage + '%' }"></div>
            </div>

            <div class="flex justify-between pt-1 font-semibold">
              <span class="text-emerald-600 dark:text-emerald-400">
                ✅ {{ $t('Created:') }} {{ activeJob.processed_rows }}
              </span>
              <span v-if="activeJob.failed_rows > 0" class="text-rose-600 dark:text-rose-400">
                ❌ {{ $t('Failed:') }} {{ activeJob.failed_rows }}
              </span>
            </div>
          </div>

          <!-- cancel button -->
          <div class="mt-4 flex justify-end" v-if="['pending', 'processing'].includes(activeJob.status)">
            <button 
              @click="cancelImport(activeJob.id)" 
              class="px-3 py-1.5 rounded border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-semibold dark:border-rose-900 dark:text-rose-400 dark:hover:bg-rose-950/30 transition-colors">
              {{ $t('Cancel Import') }}
            </button>
          </div>
        </div>

        <!-- file upload form -->
        <div v-else class="space-y-4">
          <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-300 rounded-lg p-6 hover:border-blue-400 transition-colors bg-slate-50/20 dark:border-slate-850 dark:bg-slate-900/10">
            <input 
              type="file" 
              ref="fileInput" 
              accept=".csv,.txt" 
              @change="onFileChange" 
              class="hidden" 
              id="csv-file-picker"
            />
            <label for="csv-file-picker" class="cursor-pointer flex flex-col items-center text-center space-y-2 w-full py-4">
              <span class="text-3xl mb-1">📤</span>
              <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                {{ file ? file.name : $t('Click to choose a CSV file') }}
              </span>
              <span class="text-xs text-slate-500" v-if="!file">
                {{ $t('Max file size: 10MB') }}
              </span>
            </label>
          </div>

          <div v-if="uploadError" class="text-xs text-rose-600 dark:text-rose-400 font-semibold">
            {{ uploadError }}
          </div>

          <div class="flex justify-end" v-if="file">
            <button 
              @click="startImport" 
              :disabled="uploading"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded cursor-pointer text-xs font-semibold shadow transition-colors">
              {{ uploading ? $t('Uploading...') : $t('Start Import') }}
            </button>
          </div>
        </div>
      </div>

      <!-- list of recent imports -->
      <div v-if="recentImports.length > 0" class="border-t border-gray-200 dark:border-gray-700">
        <div class="bg-slate-50 px-5 py-2 font-semibold text-xs text-slate-500 uppercase tracking-wider dark:bg-slate-900/50">
          {{ $t('Recent Imports') }}
        </div>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
          <li v-for="job in recentImports" :key="job.id" class="px-5 py-3 hover:bg-slate-50/50 dark:hover:bg-slate-900/20">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <div>
                <div class="font-medium text-slate-700 dark:text-slate-300">{{ job.filename }}</div>
                <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                  {{ formatDate(job.created_at) }} &middot; 
                  {{ job.total_rows }} {{ $t('rows') }} ({{ job.processed_rows }} {{ $t('created') }}, {{ job.failed_rows }} {{ $t('failed') }})
                </div>
              </div>
              <div class="flex items-center gap-2 self-end sm:self-auto">
                <span class="text-xxs px-2 py-0.5 rounded font-mono uppercase font-semibold" :class="statusClass(job.status)">
                  {{ job.status }}
                </span>
                <a 
                  v-if="job.failed_rows > 0"
                  :href="'/api/import/' + job.id + '/errors.csv'" 
                  class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 text-xxs font-semibold flex items-center gap-1 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 transition-colors"
                  download
                >
                  <span>⚠️</span>
                  {{ $t('Download Errors') }}
                </a>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      file: null,
      uploading: false,
      uploadError: null,
      activeJob: null,
      recentImports: [],
      pollInterval: null,
    };
  },

  mounted() {
    this.fetchRecentImports();
  },

  beforeUnmount() {
    if (this.pollInterval) {
      clearInterval(this.pollInterval);
    }
  },

  methods: {
    onFileChange(e) {
      this.file = e.target.files[0] || null;
      this.uploadError = null;
    },

    startImport() {
      if (!this.file) return;

      this.uploading = true;
      this.uploadError = null;

      const formData = new FormData();
      formData.append('vault_id', this.layoutData.vault.id);
      formData.append('file', this.file);

      axios.post('/api/import', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        }
      })
      .then(response => {
        this.uploading = false;
        this.file = null;
        this.activeJob = {
          ...response.data.data,
          progress_percentage: 0,
          estimated_remaining_seconds: 0,
        };
        this.startPolling(this.activeJob.id);
        this.fetchRecentImports();
      })
      .catch(error => {
        this.uploading = false;
        if (error.response && error.response.data && error.response.data.error) {
          this.uploadError = error.response.data.error.message;
        } else {
          this.uploadError = this.$t('Failed to upload file. Please try again.');
        }
      });
    },

    startPolling(jobId) {
      if (this.pollInterval) {
        clearInterval(this.pollInterval);
      }

      this.pollInterval = setInterval(() => {
        axios.get(`/api/import/${jobId}`)
          .then(response => {
            const job = response.data.data;
            this.activeJob = job;

            if (!['pending', 'processing'].includes(job.status)) {
              clearInterval(this.pollInterval);
              this.pollInterval = null;
              setTimeout(() => {
                this.activeJob = null;
                this.fetchRecentImports();
              }, 5000);
            }
          })
          .catch(error => {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
          });
      }, 2000);
    },

    cancelImport(jobId) {
      axios.post(`/api/import/${jobId}/cancel`)
        .then(() => {
          if (this.activeJob && this.activeJob.id === jobId) {
            this.activeJob.status = 'cancelled';
          }
          this.fetchRecentImports();
        });
    },

    fetchRecentImports() {
      axios.get('/api/import', {
        params: {
          vault_id: this.layoutData.vault.id
        }
      })
      .then(response => {
        this.recentImports = response.data.data;
      });
    },

    statusClass(status) {
      switch (status) {
        case 'completed': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'failed': return 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400';
        case 'cancelled': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
        case 'processing': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        default: return 'bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-400';
      }
    },

    formatDate(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
  }
};
</script>
