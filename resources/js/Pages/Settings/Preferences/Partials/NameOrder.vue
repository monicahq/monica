<script setup>
import { nextTick, ref, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { flash } from '@/methods';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Help from '@/Shared/Help.vue';

const props = defineProps({
  data: Object,
});

const nameOrder = useTemplateRef('nameOrder');
const loadingState = ref('');
const editMode = ref(false);
const localNameOrder = ref(props.data.name_order);
const localNameExample = ref(props.data.name_example);
const disableNameOrder = ref(true);
const form = useForm({
  nameOrder: props.data.name_order,
  choice: '',
  errors: [],
});

const enableEditMode = () => {
  editMode.value = true;
};
const focusNameOrder = () => {
  disableNameOrder.value = false;

  nextTick().then(() => nameOrder.value.focus());
};

const helpDocumentation = () => {
  let msg = trans(
    'Please read our <link>documentation</link> to know more about this feature, and which variables you have access to.',
  );
  let link = 'https://docs.monicahq.com/user-and-account-settings/manage-preferences#customize-contact-names';

  return msg
    .replace(
      '<link>',
      `<a href="${link}" lang="en" target="_blank" rel="noopener noreferrer" class="text-blue-500 hover:underline">`,
    )
    .replace('</link>', '</a>');
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, form)
    .then((response) => {
      flash(trans('Changes saved'), 'success');
      localNameOrder.value = form.nameOrder;
      localNameExample.value = response.data.data.name_example;
      form.choice = form.nameOrder;
      editMode.value = false;
      loadingState.value = null;
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
        <span class="me-1"> 👉 </span>
        <span class="me-2">
          {{ $t('Customize how contacts should be displayed') }}
        </span>

        <help :url="$page.props.help_links.settings_preferences_contact_names" :top="'5px'" />
      </h3>
      <pretty-button v-if="!editMode" :text="$t('Edit')" @click="enableEditMode" />
    </div>

    <!-- help text -->
    <div class="mb-6 flex rounded-xs border bg-slate-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-slate-900">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 grow pe-2"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>

      <div>
        <p>
          {{
            $t(
              'You can customize how contacts should be displayed according to your own taste/culture. Perhaps you would want to use James Bond instead of Bond James. Here, you can define it at will.',
            )
          }}
        </p>
      </div>
    </div>

    <!-- normal mode -->
    <div v-if="!editMode" class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="border-b border-gray-200 px-5 py-2 dark:border-gray-700">
        <span class="mb-2 block">{{ $t('Current way of displaying contact names:') }}</span>
        <span class="mb-2 block rounded-xs bg-slate-100 px-5 py-2 text-sm dark:bg-slate-900">{{ localNameOrder }}</span>
      </p>
      <p class="example bg-orange-50 px-5 py-2 text-sm font-medium dark:bg-orange-900">
        <span class="font-light">{{ $t('Contacts will be shown as follow:') }}</span> {{ localNameExample }}
      </p>
    </div>

    <!-- edit mode -->
    <form
      v-if="editMode"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 px-5 py-2 dark:border-gray-700">
        <errors :errors="form.errors" />

        <div class="mb-2 flex items-center">
          <input
            id="first_name_last_name"
            v-model="form.nameOrder"
            value="%first_name% %last_name%"
            name="name-order"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
          <label
            for="first_name_last_name"
            class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('First name Last name') }}
            <span class="ms-4 font-normal text-gray-500"> James Bond </span>
          </label>
        </div>
        <div class="mb-2 flex items-center">
          <input
            id="last_name_first_name"
            v-model="form.nameOrder"
            value="%last_name% %first_name%"
            name="name-order"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
          <label
            for="last_name_first_name"
            class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('Last name First name') }}

            <span class="ms-4 font-normal text-gray-500"> Bond James </span>
          </label>
        </div>
        <div class="mb-2 flex items-center">
          <input
            id="first_name_last_name_nickname"
            v-model="form.nameOrder"
            value="%first_name% %last_name% (%nickname%)"
            name="name-order"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
          <label
            for="first_name_last_name_nickname"
            class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('First name Last name (nickname)') }}
            <span class="ms-4 font-normal text-gray-500"> James Bond (007) </span>
          </label>
        </div>
        <div class="mb-2 flex items-center">
          <input
            id="nickname"
            v-model="form.nameOrder"
            value="%nickname%"
            name="name-order"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
          <label for="nickname" class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('nickname') }}
            <span class="ms-4 font-normal text-gray-500"> 007 </span>
          </label>
        </div>
        <div class="mb-2 flex items-center">
          <input
            id="custom"
            name="name-order"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700"
            @click="focusNameOrder" />
          <label for="custom" class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('Custom name order') }}
          </label>
        </div>
        <div class="ms-8">
          <text-input
            ref="nameOrder"
            v-model="form.nameOrder"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :class="'mb-2 block'"
            :disabled="disableNameOrder"
            :autocomplete="false"
            :maxlength="255" />

          <p class="mb-4 text-sm">
            <span class="mr-1" v-html="helpDocumentation()"></span>
          </p>
        </div>
      </div>

      <!-- actions -->
      <div class="flex justify-between p-5">
        <pretty-link :text="$t('Cancel')" :class="'me-3'" @click="editMode = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
      </div>
    </form>
  </div>
</template>

<style lang="scss" scoped>
.example {
  border-bottom-left-radius: 9px;
  border-bottom-right-radius: 9px;
}
</style>
