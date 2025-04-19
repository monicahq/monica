<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import Layout from '@/Layouts/Layout.vue';
import ContactCard from '@/Shared/ContactCard.vue';
import Uploadcare from '@/Components/Uploadcare.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const localSlice = ref(props.data.slice);

const form = useForm({
  uuid: null,
  name: null,
  original_url: null,
  cdn_url: null,
  mime_type: null,
  size: null,
});

const upload = () => {
  axios
    .put(props.data.slice.url.update_cover_image, form)
    .then((response) => {
      localSlice.value = response.data.data;
    })
    .catch(() => {});
};

const onSuccess = (file) => {
  form.uuid = file.uuid;
  form.name = file.name;
  form.original_url = file.originalUrl;
  form.cdn_url = file.cdnUrl;
  form.mime_type = file.mimeType;
  form.size = file.size;

  upload();
};

const destroyCoverImage = () => {
  axios.delete(props.data.slice.url.destroy_cover_image).then((response) => {
    localSlice.value = response.data.data;
  });
};

const destroy = () => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    form.delete(props.data.slice.url.destroy);
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
            <li class="relative me-2 inline">
              <Link :href="data.journal.url.show" class="text-blue-500 hover:underline">{{ data.journal.name }}</Link>
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
            <li class="relative me-2 inline">
              <Link :href="data.url.slices_index" class="text-blue-500 hover:underline">{{
                $t('Slices of life')
              }}</Link>
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
              {{ localSlice.name }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-18 relative">
      <div class="mx-auto max-w-4xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <div>
          <!-- header image -->
          <uploadcare
            v-if="data.uploadcare.publicKey && data.canUploadFile && !localSlice.cover_image"
            :public-key="data.uploadcare.publicKey"
            :secure-signature="data.uploadcare.signature"
            :secure-expire="data.uploadcare.expire"
            :tabs="'file'"
            :preview-step="false"
            @success="onSuccess"
            @error="onError">
            <div
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

              <p class="text-sm text-gray-500">{{ $t('Add a header image') }}</p>
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
              <span class="me-1">⚠️</span> {{ $t('You don’t have enough space left in your account.') }}
            </p>
          </div>

          <img :src="localSlice.cover_image" :alt="localSlice.uuid" class="mb-8 w-full rounded-lg" />
        </div>

        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="p-3 sm:p-0">
            <!-- slice name -->
            <h1 class="text-2xl">{{ localSlice.name }}</h1>

            <!-- slice description -->
            <p v-if="localSlice.description" class="mb-8 mt-2">{{ localSlice.description }}</p>

            <!-- number of posts -->
            <p class="mb-6 mt-8 flex items-center text-sm">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="me-0 h-4 pe-2">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
              </svg>

              <span>{{ $tChoice(':count post|:count posts', data.posts.length, { count: data.posts.length }) }}</span>
            </p>

            <!-- date range -->
            <p v-if="localSlice.date_range" class="mb-6 flex items-center text-sm">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="me-0 h-4 pe-2">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
              </svg>

              {{ localSlice.date_range }}
            </p>

            <!-- contacts -->
            <div v-if="data.contacts.length > 0">
              <p class="mb-2 text-sm font-bold">{{ $t('Contacts in this slice') }}</p>
              <ul class="mb-6">
                <li v-for="contact in data.contacts" :key="contact.id">
                  <contact-card :contact="contact" :avatar-classes="'h-5 w-5 rounded-full me-2'" :display-name="true" />
                </li>
              </ul>
            </div>

            <ul class="text-xs">
              <!-- destroy slice -->
              <li class="mb-2">
                <Link :href="data.slice.url.edit" class="cursor-pointer text-blue-500 hover:underline">{{
                  $t('Edit')
                }}</Link>
              </li>
              <!-- remove cover image -->
              <li v-if="localSlice.cover_image" class="mb-2">
                <span @click.prevent="destroyCoverImage()" class="cursor-pointer text-blue-500 hover:underline">
                  {{ $t('Remove cover image') }}
                </span>
              </li>
              <!-- destroy slice -->
              <li class="mb-2">
                <span @click.prevent="destroy()" class="cursor-pointer text-blue-500 hover:underline">
                  {{ $t('Delete the slice') }}
                </span>
              </li>
            </ul>
          </div>

          <!-- middle -->
          <div class="p-3 sm:p-0">
            <!-- list of posts -->
            <ul
              v-if="data.posts.length > 0"
              class="post-list mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <li
                v-for="post in data.posts"
                :key="post.id"
                class="flex items-center border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                <!-- written at -->
                <div class="me-4 rounded-lg border border-gray-200 p-2 text-center leading-tight">
                  <span class="block text-xs uppercase">{{ post.written_at_day }}</span>
                  <span class="text-xl">{{ post.written_at_day_number }}</span>
                </div>

                <!-- content -->
                <div>
                  <span
                    ><Link :href="post.url.show" class="text-blue-500 hover:underline">{{ post.title }}</Link></span
                  >
                  <p v-if="post.excerpt">{{ post.excerpt }}</p>
                </div>
              </li>
            </ul>

            <!-- blank state -->
            <div v-else class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <p class="p-5 text-center">
                {{
                  $t(
                    'A slice of life lets you group posts by something meaningful to you. Add a slice of life in a post to see it here.',
                  )
                }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<style lang="scss" scoped>
.post-list {
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
  grid-template-columns: 250px 1fr;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}
</style>
