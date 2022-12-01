<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative mr-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-sidebar relative inline h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </span>

        <span class="font-semibold">
          {{ $t('contact.photos_title') }}
        </span>
      </div>
      <uploadcare
        v-if="data.uploadcarePublicKey && data.canUploadFile"
        :public-key="data.uploadcarePublicKey"
        :tabs="'file'"
        :multiple="false"
        :preview-step="false"
        @success="onSuccess"
        @error="onError">
        <pretty-button :text="$t('contact.photos_cta')" :icon="'plus'" :classes="'sm:w-fit w-full'" />
      </uploadcare>
    </div>

    <!-- not enough space in storage -->
    <div
      v-if="!data.canUploadFile"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="bg-gray-100 p-3 text-center">
        <span class="mr-1">⚠️</span> {{ $t('contact.photos_not_enough_storage') }}
      </p>
    </div>

    <!-- photos -->
    <div v-if="localPhotos.length > 0">
      <div class="mb-4 grid grid-cols-3 gap-4">
        <div
          v-for="photo in localPhotos"
          :key="photo.id"
          class="rounded-md border border-gray-200 p-2 shadow-sm hover:bg-slate-50 hover:shadow-lg dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
          <inertia-link :href="photo.url.show"><img :src="photo.url.display" :alt="photo.name" /></inertia-link>
        </div>
      </div>

      <!-- view all button -->
      <div class="text-center">
        <inertia-link
          :href="data.url.index"
          class="rounded border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500 dark:border-gray-700">
          {{ $t('app.view_all') }}
        </inertia-link>
      </div>
    </div>

    <!-- blank state -->
    <div
      v-if="localPhotos.length == 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_photo.svg" :alt="$t('Photos')" class="mx-auto mt-4 h-16 w-16" />
      <p class="px-5 pb-5 pt-2 text-center">
        {{ $t('contact.photos_blank') }}
      </p>
    </div>

    <!-- uploadcare api key not set -->
    <div
      v-if="!data.uploadcarePublicKey"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="p-5 text-center">
        {{ $t('contact.photos_key_missing') }}
      </p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import Uploadcare from '@/Components/Uploadcare.vue';

export default {
  components: {
    PrettyButton,
    Uploadcare,
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
          this.flash(this.$t('contact.photos_new_success'), 'success');
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    destroy(photo) {
      if (confirm(this.$t('contact.photos_delete_confirm'))) {
        axios
          .delete(photo.url.destroy)
          .then(() => {
            this.flash(this.$t('contact.photos_delete_success'), 'success');
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

.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}

select {
  padding-left: 8px;
  padding-right: 20px;
  background-position: right 3px center;
}
</style>
