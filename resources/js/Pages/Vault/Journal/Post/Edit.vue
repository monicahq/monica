<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { watch, ref } from 'vue';
import { debounce } from 'lodash';
import { trans } from 'laravel-vue-i18n';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import Layout from '@/Layouts/Layout.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Tags from '@/Pages/Vault/Journal/Post/Partials/Tags.vue';
import SlicesOfLife from '@/Pages/Vault/Journal/Post/Partials/SlicesOfLife.vue';
import PostMetrics from '@/Pages/Vault/Journal/Post/Partials/PostMetrics.vue';
import Uploadcare from '@/Components/Uploadcare.vue';
import ContactSelector from '@/Shared/Form/ContactSelector.vue';
import JetConfirmationModal from '@/Components/Jetstream/ConfirmationModal.vue';
import JetDangerButton from '@/Components/Jetstream/DangerButton.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const form = useForm({
  title: props.data.title,
  contacts: props.data.contacts,
  date: props.data.editable_date,
  sections: props.data.sections.map((section) => ({
    id: section.id,
    label: section.label,
    content: section.content,
  })),
  uuid: null,
  name: null,
  original_url: null,
  cdn_url: null,
  mime_type: null,
  size: null,
});

const saveInProgress = ref(false);
const statistics = ref(props.data.statistics);
const deletePhotoModalShown = ref(false);
const photoToDelete = ref(null);
const processPhotoDeletion = ref(false);
const localPhotos = ref(props.data.photos);
const masks = ref({
  modelValue: 'YYYY-MM-DD',
});

watch(
  () => _.cloneDeep(form.sections),
  () => {
    debouncedWatch(form.sections);
  },
);

watch(
  () => form.title,
  () => {
    debouncedWatch(form.title);
  },
);

watch(
  () => form.date,
  () => {
    debouncedWatch(form.date);
  },
);

watch(
  () => _.cloneDeep(form.contacts),
  () => {
    debouncedWatch(form.contacts);
  },
);

const debouncedWatch = debounce(() => {
  update();
}, 500);

const onSuccess = (file) => {
  form.uuid = file.uuid;
  form.name = file.name;
  form.original_url = file.originalUrl;
  form.cdn_url = file.cdnUrl;
  form.mime_type = file.mimeType;
  form.size = file.size;

  upload();
};

const showDeletePhotoModal = (file) => {
  photoToDelete.value = file;
  deletePhotoModalShown.value = true;
};

const upload = () => {
  saveInProgress.value = true;

  axios
    .post(props.data.url.upload_photo, form)
    .then((response) => {
      saveInProgress.value = false;
      localPhotos.value.push(response.data.data);
    })
    .catch((error) => {
      form.errors = error.response.data;
    });
};

const destroyPhoto = () => {
  processPhotoDeletion.value = true;

  axios
    .delete(photoToDelete.value.url.destroy)
    .then(() => {
      processPhotoDeletion.value = false;
      var id = localPhotos.value.findIndex((x) => x.id === photoToDelete.value.id);
      localPhotos.value.splice(id, 1);
      deletePhotoModalShown.value = false;
    })
    .catch((error) => {
      form.errors = error.response.data;
    });
};

const update = () => {
  saveInProgress.value = true;

  axios
    .put(props.data.url.update, form)
    .then((response) => {
      setTimeout(() => (saveInProgress.value = false), 350);
      statistics.value = response.data.data;
    })
    .catch(() => {});
};

const destroy = () => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    form.delete(props.data.url.destroy, {
      onFinish: () => {},
    });
  }
};
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
              <Link :href="layoutData.vault.url.journals" class="text-blue-500 hover:underline">
                {{ $t('Journals') }}
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
              <Link :href="data.url.back" class="text-blue-500 hover:underline">
                {{ data.journal.name }}
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
            <li class="me-2 inline">
              <Link :href="data.url.show" class="text-blue-500 hover:underline">
                {{ data.title }}
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
            <li class="relative inline">
              {{ $t('Edit a post') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div>
            <!-- photos -->
            <div>
              <!-- list of existing photos -->
              <ul
                v-if="localPhotos.length > 0"
                class="mb-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <li
                  v-for="photo in localPhotos"
                  :key="photo.id"
                  class="item-list flex items-center justify-between border-b border-gray-200 p-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                  <div class="flex">
                    <img :src="photo.url.show" class="me-4" width="75" height="75" />

                    <ul>
                      <li class="mb-2 text-sm">{{ photo.name }}</li>
                      <li class="font-mono text-xs">{{ photo.size }}</li>
                    </ul>
                  </div>

                  <span
                    class="inline cursor-pointer text-red-500 hover:text-red-900"
                    @click="showDeletePhotoModal(photo)">
                    {{ $t('Delete') }}
                  </span>
                </li>
              </ul>

              <!-- upload component -->
              <uploadcare
                v-if="data.uploadcare.publicKey && data.canUploadFile"
                :public-key="data.uploadcare.publicKey"
                :secure-signature="data.uploadcare.signature"
                :secure-expire="data.uploadcare.expire"
                :tabs="'file'"
                :preview-step="false"
                @success="onSuccess"
                @error="onError">
                <!-- case when there are no photos yet -->
                <div
                  v-if="localPhotos.length === 0"
                  class="mb-6 flex cursor-pointer flex-col items-center rounded-lg border border-gray-200 bg-white p-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-slate-800">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="mb-2 h-8 w-8 text-gray-500">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                  </svg>

                  <p class="text-sm text-gray-500">{{ $t('Add photos') }}</p>
                </div>

                <!-- case when there are photos -->
                <div v-else class="mb-6 flex items-center">
                  <p
                    class="inline-block cursor-pointer rounded-lg border bg-slate-200 dark:bg-slate-700 px-1 py-1 text-xs hover:bg-slate-300 dark:hover:bg-slate-800">
                    {{ $t('+ add another photo') }}
                  </p>
                </div>
              </uploadcare>

              <!-- uploadcare api key not set -->
              <div
                v-if="!data.uploadcare.publicKey"
                class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <p class="p-5 text-center">
                  {{ $t('The keys to manage uploads have not been set in this Monica instance.') }}
                </p>
              </div>

              <!-- not enough storage -->
              <div
                v-if="!data.canUploadFile"
                class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <p class="bg-gray-100 p-3 text-center">
                  <span class="me-1">⚠️</span>
                  {{ $t('You don’t have enough space left in your account.') }}
                </p>
              </div>
            </div>

            <!-- post body -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
              <div class="border-gray-200 p-5 dark:border-gray-700">
                <!-- title -->
                <text-input
                  ref="newTitle"
                  v-model="form.title"
                  :label="$t('Title')"
                  :type="'text'"
                  :input-class="'block w-full mb-6'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createNoteModalShown = false" />

                <div v-for="section in form.sections" :key="section.id" class="mb-8">
                  <text-area
                    v-model="section.content"
                    :label="section.label"
                    :rows="10"
                    :required="true"
                    :maxlength="65535"
                    :markdown="true"
                    :textarea-class="'block w-full'" />
                </div>
              </div>
            </div>
          </div>

          <!-- right -->
          <div>
            <!-- Publish action -->
            <div class="mb-2 rounded-lg border border-gray-200 text-center dark:border-gray-700 dark:bg-gray-900">
              <div class="rounded-b-lg bg-gray-50 p-5 dark:bg-gray-900">
                <pretty-link :href="data.url.show" :text="$t('Close')" :icon="'exit'" />
              </div>
            </div>

            <!-- auto save -->
            <div class="mb-6 text-sm">
              <div v-if="!saveInProgress" class="flex items-center justify-center">
                <svg
                  class="me-2 h-4 w-4 text-green-700"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>

                <span>{{ $t('Auto saved a few seconds ago') }}</span>
              </div>

              <div v-if="saveInProgress" class="flex items-center justify-center">
                <div class="saving-spinner me-3">
                  <div class="dot"></div>
                  <div class="dot"></div>
                  <div class="dot"></div>
                </div>

                <span>{{ $t('Saving in progress') }}</span>
              </div>
            </div>

            <!-- written at -->
            <p class="mb-2 flex items-center font-bold">
              <span>{{ $t('Written on') }}</span>
            </p>
            <DatePicker
              v-model.string="form.date"
              :masks="masks"
              :locale="$page.props.auth.user?.locale_ietf"
              class="mb-6 inline-block">
              <template #default="{ inputValue, inputEvents }">
                <input
                  class="rounded-xs border bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-900"
                  :value="inputValue"
                  v-on="inputEvents" />
              </template>
            </DatePicker>

            <!-- contacts -->
            <p class="mb-2 mt-6 flex items-center font-bold">
              <span>{{ $t('Contacts in this post') }}</span>
            </p>
            <contact-selector
              v-model="form.contacts"
              :search-url="layoutData.vault.url.search_contacts_only"
              :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
              :display-most-consulted-contacts="true"
              :add-multiple-contacts="true"
              :required="true"
              :class="'mb-8 flex-1 border-gray-200 dark:border-gray-700'" />

            <!-- slices of life -->
            <slices-of-life :data="data" />

            <!-- post metrics -->
            <post-metrics :data="data" />

            <!-- tags -->
            <tags :data="data" />

            <!-- stats -->
            <p class="mb-2 font-bold">{{ $t('Statistics') }}</p>
            <ul class="mb-6 text-sm">
              <li class="mb-2 flex items-center">
                <svg
                  class="me-2 h-4 w-4"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                </svg>

                <span>{{
                  $tChoice(':count word|:count words', statistics.word_count, { count: statistics.word_count })
                }}</span>
              </li>
              <li class="mb-2 flex items-center">
                <svg
                  class="me-2 h-4 w-4"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>

                <span>{{
                  $tChoice(':count min read', statistics.time_to_read_in_minute, {
                    count: statistics.time_to_read_in_minute,
                  })
                }}</span>
              </li>
              <li class="flex items-center">
                <svg
                  class="me-2 h-4 w-4"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>

                <span>{{
                  $tChoice('Read :count time|Read :count times', statistics.view_count, {
                    count: statistics.view_count,
                  })
                }}</span>
              </li>
            </ul>

            <!-- delete -->
            <div @click="destroy" class="cursor-pointer text-red-500 hover:text-red-900">{{ $t('Delete') }}</div>
          </div>
        </div>
      </div>
    </main>

    <!-- delete photo confirmation modal -->
    <JetConfirmationModal :show="deletePhotoModalShown" @close="deletePhotoModalShown = false">
      <template #title>
        {{ $t('Delete the photo') }}
      </template>

      <template #content>
        {{ $t('Are you sure? This action cannot be undone.') }}
      </template>

      <template #footer>
        <JetSecondaryButton @click="deletePhotoModalShown = false">
          {{ $t('Cancel') }}
        </JetSecondaryButton>

        <JetDangerButton
          class="ms-3"
          :class="{ 'opacity-25': processPhotoDeletion }"
          :disabled="processPhotoDeletion"
          @click="destroyPhoto(file)">
          {{ $t('Delete') }}
        </JetDangerButton>
      </template>
    </JetConfirmationModal>
  </layout>
</template>

<style lang="scss" scoped>
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

.special-grid {
  grid-template-columns: 1fr 300px;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}

.saving-spinner,
.saving-spinner * {
  box-sizing: border-box;
}

.saving-spinner {
  width: calc(15px * 4);
  height: 9px;
  position: relative;
}

.saving-spinner .dot {
  height: 10px;
  width: 10px;
  background-color: #88d772;
  position: absolute;
  margin: 0 auto;
  border-radius: 2px;
  transform: translateY(0) rotate(45deg) scale(0);
  animation: saving-spinner-animation 2500ms linear infinite;
}

[dir='ltr'] .saving-spinner .dot {
  left: calc(15px * 4);
}

[dir='rtl'] .saving-spinner .dot {
  right: calc(15px * 4);
}

.saving-spinner .dot:nth-child(1) {
  animation-delay: calc(2500ms * 1 / -1.5);
}

.saving-spinner .dot:nth-child(2) {
  animation-delay: calc(2500ms * 2 / -1.5);
}

.saving-spinner .dot:nth-child(3) {
  animation-delay: calc(2500ms * 3 / -1.5);
}

@keyframes saving-spinner-animation {
  0% {
    transform: translateX(0) rotate(45deg) scale(0);
  }
  50% {
    transform: translateX(-233%) rotate(45deg) scale(1);
  }
  100% {
    transform: translateX(-466%) rotate(45deg) scale(0);
  }
}

.ant-calendar-picker {
  -tw-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --tw-shadow-colored: 0 1px 2px 0 var(--tw-shadow-color);
  box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
  --tw-border-opacity: 1;
  border-color: rgb(209 213 219 / var(--tw-border-opacity));
  border-radius: 0.375rem;
  padding-top: 0.5rem;
  padding-right: 0.75rem;
  padding-bottom: 0.5rem;
  padding-left: 0.75rem;
  font-size: 1rem;
  line-height: 1.5rem;
  border-width: 1px;
  appearance: none;
  background-color: #fff;
}
</style>
