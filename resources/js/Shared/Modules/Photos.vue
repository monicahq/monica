<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <FileImage class="h-4 w-4 text-gray-600" />

        <span class="font-semibold">
          {{ $t('Photos') }}
        </span>
      </div>
      <uploadcare
        v-if="data.uploadcare.publicKey && data.canUploadFile"
        :public-key="data.uploadcare.publicKey"
        :secure-signature="data.uploadcare.signature"
        :secure-expire="data.uploadcare.expire"
        :tabs="'file'"
        :preview-step="false"
        @success="onSuccess"
        @error="onError">
        <pretty-button :text="$t('Add a photo')" :icon="'plus'" :class="'w-full sm:w-fit'" />
      </uploadcare>
    </div>

    <!-- not enough space in storage -->
    <div
      v-if="!data.canUploadFile"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="bg-gray-100 p-3 text-center">
        <span class="me-1">⚠️</span> {{ $t('You don’t have enough space left in your account.') }}
      </p>
    </div>

    <!-- photos -->
    <div v-if="localPhotos.length > 0">
      <div class="mb-4 grid grid-cols-3 gap-4">
        <div
          v-for="photo in localPhotos"
          :key="photo.id"
          class="rounded-md border border-gray-200 p-2 shadow-xs hover:bg-slate-50 hover:shadow-lg dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <InertiaLink :href="photo.url.show">
            <img :src="photo.url.display" :alt="photo.name" />
          </InertiaLink>
        </div>
      </div>

      <!-- view all button -->
      <div class="text-center">
        <InertiaLink
          :href="data.url.index"
          class="rounded-xs border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500 dark:border-gray-700">
          {{ $t('View all') }}
        </InertiaLink>
      </div>
    </div>

    <!-- blank state -->
    <div
      v-if="localPhotos.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_photo.svg" :alt="$t('Photos')" class="mx-auto mt-4 h-16 w-16" />
      <p class="px-5 pb-5 pt-2 text-center">
        {{ $t('There are no photos yet.') }}
      </p>
    </div>

    <!-- uploadcare api key not set -->
    <div
      v-if="!data.uploadcare.publicKey"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="p-5 text-center">
        {{ $t('The keys to manage uploads have not been set in this Monica instance.') }}
      </p>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import Uploadcare from '@/Components/Uploadcare.vue';
import { FileImage } from 'lucide-vue-next';

export default {
  components: {
    InertiaLink: Link,
    PrettyButton,
    Uploadcare,
    FileImage,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      localPhotos: [],
      form: {
        searchTerm: null,
        uuid: null,
        name: null,
        original_url: null,
        cdn_url: null,
        mime_type: null,
        size: null,
        errors: [],
      },
    };
  },

  created() {
    this.localPhotos = this.data.photos;
  },

  methods: {
    onSuccess(file) {
      this.form.uuid = file.uuid;
      this.form.name = file.name;
      this.form.original_url = file.originalUrl;
      this.form.cdn_url = file.cdnUrl;
      this.form.mime_type = file.mimeType;
      this.form.size = file.size;

      this.upload();
    },

    upload() {
      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.localPhotos.unshift(response.data.data);
          this.flash(this.$t('The photo has been added'), 'success');
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    destroy(photo) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(photo.url.destroy)
          .then(() => {
            this.flash(this.$t('The photo has been deleted'), 'success');
            var id = this.localPhotos.findIndex((x) => x.id === photo.id);
            this.localPhotos.splice(id, 1);
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
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}
</style>
