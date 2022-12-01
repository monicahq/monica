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
              d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
          </svg>
        </span>

        <span class="font-semibold">
          {{ $t('contact.documents_title') }}
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
        <pretty-button :text="$t('contact.documents_cta')" :icon="'plus'" :classes="'sm:w-fit w-full'" />
      </uploadcare>
    </div>

    <!-- not enough space in storage -->
    <div
      v-if="!data.canUploadFile"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="bg-gray-100 p-3 text-center">
        <span class="mr-1">⚠️</span> {{ $t('contact.documents_not_enough_storage') }}
      </p>
    </div>

    <!-- documents -->
    <div v-if="localDocuments.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="document in localDocuments"
          :key="document.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
          <!-- document -->
          <div class="flex items-center justify-between px-3 py-2">
            <span class="flex items-center">
              <span class="max-w-sm truncate">{{ document.name }}</span>

              <span class="ml-2 rounded border bg-blue-50 px-1 py-0 font-mono text-xs text-blue-500">
                {{ document.size }}
              </span>
            </span>

            <!-- actions -->
            <ul class="text-sm">
              <li class="mr-4 inline">
                <a :href="document.url.download" class="text-blue-500 hover:underline">{{ $t('app.download') }}</a>
              </li>
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(document)">
                {{ $t('app.delete') }}
              </li>
            </ul>
          </div>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-if="localDocuments.length == 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_document.svg" :alt="$t('Documents')" class="mx-auto mt-4 h-16 w-16" />
      <p class="px-5 pb-5 pt-2 text-center">
        {{ $t('contact.documents_blank') }}
      </p>
    </div>

    <!-- uploadcare api key not set -->
    <div
      v-if="!data.uploadcarePublicKey"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="p-5 text-center">
        {{ $t('contact.documents_key_missing') }}
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
      localDocuments: [],
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

  created() {
    this.localDocuments = this.data.documents;
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
          this.localDocuments.unshift(response.data.data);
          this.flash(this.$t('contact.documents_new_success'), 'success');
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    destroy(document) {
      if (confirm(this.$t('contact.documents_delete_confirm'))) {
        axios
          .delete(document.url.destroy)
          .then(() => {
            this.flash(this.$t('contact.documents_delete_success'), 'success');
            var id = this.localDocuments.findIndex((x) => x.id === document.id);
            this.localDocuments.splice(id, 1);
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
