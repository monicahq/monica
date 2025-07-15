<script setup>
import { onMounted, nextTick, useTemplateRef } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Errors from '@/Shared/Form/Errors.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const form = useForm({
  name: '',
  description: '',
});

const nameField = useTemplateRef('nameField');

onMounted(() => {
  nextTick().then(() => nameField.value.focus());
});

const submit = () => {
  form.post(props.data.url.store, {
    onFinish: () => {},
  });
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
              <Link :href="data.url.back" class="text-blue-500 hover:underline">
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
              {{ $t('Create a journal') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-lg px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <form
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5 dark:border-gray-700 dark:bg-blue-900">
            <h1 class="text-center text-2xl font-medium">
              {{ $t('Create a journal') }}
            </h1>
          </div>
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              v-model="form.name"
              ref="nameField"
              :autofocus="true"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="true"
              :maxlength="255"
              :label="$t('Name')" />

            <text-area
              v-model="form.description"
              :label="$t('Description')"
              :maxlength="255"
              :textarea-class="'block w-full'" />
          </div>

          <!-- actions -->
          <div class="flex justify-between p-5">
            <pretty-link :href="data.url.back" :text="$t('Cancel')" :class="'me-3'" />
            <pretty-button :text="$t('Add')" :state="loadingState" :icon="'check'" :class="'save'" />
          </div>
        </form>
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
