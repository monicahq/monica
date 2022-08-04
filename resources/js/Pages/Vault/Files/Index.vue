<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div>
            <!-- filters -->
            <div>
              <ul class="mb-4">
                <li class="border-l-2 pl-2" :class="{ 'border-orange-500': tab === 'index' }">
                  <inertia-link :href="data.statistics.url.index">
                    {{ $t('vault.files_filter_all') }}
                    <span class="text-sm text-gray-500">({{ data.statistics.statistics.all }})</span>
                  </inertia-link>
                </li>
              </ul>

              <p class="mb-2 pl-2 text-sm text-gray-500">
                {{ $t('vault.files_filter_or') }}
              </p>
              <ul>
                <li class="mb-2 border-l-2 pl-2" :class="{ 'border-orange-500': tab === 'documents' }">
                  <inertia-link :href="data.statistics.url.documents">
                    {{ $t('vault.files_filter_documents') }}
                    <span class="text-sm text-gray-500">({{ data.statistics.statistics.documents }})</span>
                  </inertia-link>
                </li>
                <li class="mb-2 border-l-2 pl-2" :class="{ 'border-orange-500': tab === 'photos' }">
                  <inertia-link :href="data.statistics.url.photos">
                    {{ $t('vault.files_filter_photos') }}
                    <span class="text-sm text-gray-500">({{ data.statistics.statistics.photos }})</span>
                  </inertia-link>
                </li>
                <li class="mb-2 border-l-2 pl-2" :class="{ 'border-orange-500': tab === 'avatars' }">
                  <inertia-link :href="data.statistics.url.avatars">
                    {{ $t('vault.files_filter_avatars') }}
                    <span class="text-sm text-gray-500">({{ data.statistics.statistics.avatars }})</span>
                  </inertia-link>
                </li>
              </ul>
            </div>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <!-- title + cta -->
            <div class="mb-6 flex items-center justify-between">
              <h3>
                <span class="mr-1"> 📸 </span>
                {{ $t('vault.files_filter_title') }}
              </h3>
            </div>

            <!-- file list -->
            <ul v-if="data.files.length > 0" class="file-list mb-6 rounded-lg border border-gray-200 bg-white">
              <li
                v-for="file in data.files"
                :key="file.id"
                class="items-center justify-between border-b border-gray-200 px-5 py-2 hover:bg-slate-50 sm:flex">
                <!-- left part -->
                <div class="mb-4 block sm:mb-0 sm:flex">
                  <!-- created at -->
                  <p class="mr-2 text-sm text-gray-400">
                    {{ file.created_at }}
                  </p>

                  <!-- file name -->
                  <p class="mr-4 flex max-w-none sm:max-w-sm">
                    <span class="block truncate">{{ file.name }}</span>

                    <span class="ml-2">
                      <span class="rounded border bg-blue-50 px-1 py-0 font-mono text-xs text-blue-500">
                        {{ file.size }}
                      </span>
                    </span>
                  </p>

                  <!-- avatar -->
                  <div class="flex items-center">
                    <avatar :data="file.contact.avatar" :classes="'rounded-full mr-2 h-4 w-4'" />
                    <inertia-link :href="file.contact.url.show" class="text-sm text-blue-500 hover:underline">
                      {{ file.contact.name }}
                    </inertia-link>
                  </div>
                </div>

                <!-- right part -->
                <ul class="text-sm">
                  <li class="mr-4 inline">
                    <a :href="file.url.download" class="text-blue-500 hover:underline">{{ $t('app.download') }}</a>
                  </li>
                  <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(file)">
                    {{ $t('app.delete') }}
                  </li>
                </ul>
              </li>
            </ul>

            <!-- pagination -->
            <div v-if="!moduleMode" class="flex justify-between text-center">
              <inertia-link
                v-show="paginator.previousPageUrl"
                class="fl dib"
                :href="paginator.previousPageUrl"
                title="Previous">
                &larr; {{ $t('app.previous') }}
              </inertia-link>
              <inertia-link v-show="paginator.nextPageUrl" class="fr dib" :href="paginator.nextPageUrl" title="Next">
                {{ $t('app.next') }} &rarr;
              </inertia-link>
            </div>

            <!-- blank state -->
            <div v-if="data.files.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
              <p class="p-5 text-center">
                {{ $t('vault.files_filter_blank') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import Avatar from '@/Shared/Avatar';
import PrettyLink from '@/Shared/Form/PrettyLink';

export default {
  components: {
    Layout,
    Avatar,
    PrettyLink,
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
    };
  },

  created() {
    this.localFiles = this.data.files;
  },

  methods: {
    destroy(file) {
      if (confirm(this.$t('contact.documents_delete_confirm'))) {
        axios
          .delete(file.url.destroy)
          .then(() => {
            this.flash(this.$t('contact.documents_delete_success'), 'success');
            var id = this.localFiles.findIndex((x) => x.id === file.id);
            this.localFiles.splice(id, 1);
          })
          .catch((error) => {
            this.form.errors = error.response.data;
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
