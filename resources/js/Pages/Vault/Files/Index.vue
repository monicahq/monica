<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div>
            <!-- filters -->
            <div>
              <ul class="mb-4">
                <li class="border-s-2 ps-2" :class="{ 'border-orange-500': tab === 'index' }">
                  <InertiaLink :href="data.statistics.url.index">
                    {{ $t('All files') }}
                    <span class="text-sm text-gray-500">({{ data.statistics.statistics.all }})</span>
                  </InertiaLink>
                </li>
              </ul>

              <p class="mb-2 ps-2 text-sm text-gray-500">
                {{ $t('Or filter by type') }}
              </p>
              <ul>
                <li class="mb-2 border-s-2 ps-2" :class="{ 'border-orange-500': tab === 'documents' }">
                  <InertiaLink :href="data.statistics.url.documents">
                    {{ $t('Documents') }}
                    <span class="text-sm text-gray-500">({{ data.statistics.statistics.documents }})</span>
                  </InertiaLink>
                </li>
                <li class="mb-2 border-s-2 ps-2" :class="{ 'border-orange-500': tab === 'photos' }">
                  <InertiaLink :href="data.statistics.url.photos">
                    {{ $t('Photos') }}
                    <span class="text-sm text-gray-500">({{ data.statistics.statistics.photos }})</span>
                  </InertiaLink>
                </li>
                <li class="mb-2 border-s-2 ps-2" :class="{ 'border-orange-500': tab === 'avatars' }">
                  <InertiaLink :href="data.statistics.url.avatars">
                    {{ $t('Avatars') }}
                    <span class="text-sm text-gray-500">({{ data.statistics.statistics.avatars }})</span>
                  </InertiaLink>
                </li>
              </ul>
            </div>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <errors :errors="errors" />

            <!-- title + cta -->
            <div class="mb-6 flex items-center justify-between">
              <h3>
                <span class="me-1"> 📸 </span>
                {{ $t('All the files') }}
              </h3>
            </div>

            <!-- file list -->
            <ul
              v-if="data.files.length > 0"
              class="file-list mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <li
                v-for="file in data.files"
                :key="file.id"
                class="items-center justify-between border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800 sm:flex">
                <!-- left part -->
                <div class="mb-4 block items-center sm:mb-0 sm:flex">
                  <!-- created at -->
                  <p class="me-2 text-sm text-gray-400">
                    {{ file.created_at }}
                  </p>

                  <!-- file name -->
                  <p class="me-4 flex max-w-none sm:max-w-sm">
                    <span class="block truncate">{{ file.name }}</span>

                    <span class="ms-2">
                      <span class="rounded-xs border bg-blue-50 px-1 py-0 font-mono text-xs text-blue-500">
                        {{ file.size }}
                      </span>
                    </span>
                  </p>

                  <!-- avatar -->
                  <div v-if="file.object.type === 'contact'" class="flex items-center">
                    <avatar :data="file.object.avatar" :class="'me-2 h-4 w-4 rounded-full'" />
                    <InertiaLink :href="file.object.url.show" class="text-sm text-blue-500 hover:underline">
                      {{ file.object.name }}
                    </InertiaLink>
                  </div>
                </div>

                <!-- right part -->
                <ul class="text-sm">
                  <li class="me-4 inline">
                    <a :href="file.url.download" class="text-blue-500 hover:underline">{{ $t('Download') }}</a>
                  </li>
                  <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(file)">
                    {{ $t('Delete') }}
                  </li>
                </ul>
              </li>
            </ul>

            <!-- pagination -->
            <Pagination :items="paginator" />

            <!-- blank state -->
            <div
              v-if="data.files.length === 0"
              class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <p class="p-5 text-center">
                {{ $t('There are no files yet.') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import Avatar from '@/Shared/Avatar.vue';
import Pagination from '@/Components/Pagination.vue';
import Errors from '@/Shared/Form/Errors.vue';

export default {
  components: {
    InertiaLink: Link,
    Layout,
    Avatar,
    Pagination,
    Errors,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    paginator: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
    tab: {
      type: String,
      default: 'index',
    },
  },

  data() {
    return {
      localFiles: [],
      errors: [],
    };
  },

  created() {
    this.localFiles = this.data.files;
  },

  methods: {
    destroy(file) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        this.errors = [];
        axios
          .delete(file.url.destroy)
          .then(() => {
            this.flash(this.$t('The document has been deleted'), 'success');
            var id = this.localFiles.findIndex((x) => x.id === file.id);
            this.localFiles.splice(id, 1);
          })
          .catch((error) => {
            this.errors = error.response.data;
          });
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.file-list {
  li:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  li:last-child {
    border-bottom: 0;
  }

  li:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}

.special-grid {
  grid-template-columns: 200px 1fr;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}
</style>
