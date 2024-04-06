<template>
  <div class="relative">
    <button ref="toggleButton" type="button" @click.prevent="toggle">
      <span class="sr-only">Open Avatar Upload</span>
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-3 w-3 text-gray-300 hover:text-gray-600 dark:text-gray-400"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
      </svg>
    </button>
    <div
      ref="dropdown"
      class="z-10 absolute hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-auto dark:bg-gray-700 dark:divide-gray-600">
      <div class="py-2">
        <div class="block px-4 py-2">Photo Suggestions</div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 px-4 py-2">
          <photo-suggestion v-model="photo" :photos="photos" @select="select" />
          <div v-if="!photos">{{ $t('No suggestions found') }}</div>
        </div>
      </div>
    </div>
    <avatar :data="data.avatar" :class="'mx-auto mb-6 sm:w-1/2'" :img-classes="'rounded sm:w-72'" />
  </div>
</template>

<script>
import Avatar from '@/Shared/Avatar.vue';
import PhotoSuggestion from '@/Shared/Form/AvatarSuggestion.vue';
import { flash } from '@/methods';
import { trans } from 'laravel-vue-i18n';
import { router } from '@inertiajs/vue3';
import uploadcare from 'uploadcare-widget';

export default {
  components: {
    Avatar,
    PhotoSuggestion,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      photo: {},
      photos: [],
      form: {
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

  mounted() {
    document.addEventListener('click', (event) => {
      if (!this.$refs.dropdown.contains(event.target) && !this.$refs.toggleButton.contains(event.target)) {
        this.$refs.dropdown.classList.add('hidden');
      }
    });

    this.suggest();
  },

  methods: {
    toggle() {
      this.$refs.dropdown.classList.toggle('hidden');
    },
    select(photo) {
      this.toggle();
      const file = uploadcare.fileFrom('url', photo.src);
      uploadcare
        .openDialog([file], {
          publicKey: this.data.uploadcare.publicKey,
          secureSignature: this.data.uploadcare.secureSignature,
          secureExpire: this.data.uploadcare.secureExpire,
          tabs: 'url',
          imagesOnly: true,
          crop: 'free',
          previewStep: true,
          systemDialog: true,
        })
        .done((filePromise) => {
          filePromise.done((file) => {
            this.update({
              uuid: file.uuid,
              name: file.name,
              original_url: file.originalUrl,
              cdn_url: file.cdnUrl,
              mime_type: file.mimeType,
              size: file.size,
            });
          });
          filePromise.fail(() => {
            flash(trans('Avatar upload failed'), 'error');
          });
        });
    },
    suggest() {
      axios
        .get(this.data.url.suggest)
        .then((response) => {
          this.photos = response.data.data.map((url) => ({ src: url }));
        })
        .catch(() => {
          this.form.errors = error.response.data;
        });
    },
    update(form) {
      axios
        .put(this.data.url.update, form)
        .then((response) => {
          router.visit(response.data.data);
          flash(trans('Avatar has been added'), 'success');
        })
        .catch(() => {
          flash(trans('Something went wrong'), 'error');
        });
    },
  },
};
</script>
