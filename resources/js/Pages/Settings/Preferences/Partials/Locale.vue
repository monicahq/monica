<script setup>
import { onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/inertia-vue3';
import { loadLanguageAsync, getActiveLanguage, trans } from 'laravel-vue-i18n';
import { flash } from '@/methods.js';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Help from '@/Shared/Help.vue';

const props = defineProps({
  data: Object,
});

const loadingState = ref('');
const editMode = ref(false);
const localLocaleI18n = ref('');
const form = useForm({
  locale: '',
  errors: [],
});

onMounted(() => {
  localLocaleI18n.value = props.data.locale_i18n;
  form.locale = props.data.locale;
});

const enableEditMode = () => {
  editMode.value = true;
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, form.data())
    .then((response) => {
      flash(trans('Changes saved'), 'success');
      localLocaleI18n.value = response.data.data.locale_i18n;
      editMode.value = false;
      loadingState.value = null;

      if (getActiveLanguage() !== form.locale) {
        loadLanguageAsync(response.data.data.locale);
      }
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};
</script>

<template>
  <div class="mb-16">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 flex font-semibold sm:mb-0">
        <span class="mr-1"> 🗓 </span>
        <span class="mr-2">
          {{ $t('Language of the application') }}
        </span>

        <help :url="$page.props.help_links.settings_preferences_language" :top="'5px'" />
      </h3>
      <pretty-button v-if="!editMode" :text="$t('Edit')" @click="enableEditMode" />
    </div>

    <!-- normal mode -->
    <div v-if="!editMode" class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="px-5 py-2">
        <span class="mb-2 block">{{ $t('Current language:') }}</span>
        <span class="mb-2 block rounded bg-slate-100 px-5 py-2 text-sm dark:bg-slate-900">{{ localLocaleI18n }}</span>
      </p>
    </div>

    <!-- edit mode -->
    <form
      v-if="editMode"
      class="bg-form mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 px-5 py-2 dark:border-gray-700">
        <errors :errors="form.errors" />

        <select
          v-model="form.locale"
          name="locale"
          class="rounded-md border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-900 sm:text-sm">
          <option v-for="(value, key) in props.data.languages" :key="key" :value="key">
            {{ value }}
          </option>
        </select>
      </div>

      <!-- actions -->
      <div class="flex justify-between p-5">
        <pretty-link :text="$t('Cancel')" :classes="'mr-3'" @click="editMode = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :classes="'save'" />
      </div>
    </form>
  </div>
</template>

<style lang="scss" scoped>
select {
  padding-left: 8px;
  padding-right: 30px;
  background-position: right 3px center;
}
</style>
