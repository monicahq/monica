<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import { flash } from '@/methods';
import { trans } from 'laravel-vue-i18n';
import { debounce } from 'lodash';
import { Tooltip as ATooltip } from 'ant-design-vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';

const props = defineProps({
  data: Object,
});

const loadingState = ref('');
const localTelegram = ref(props.data.telegram.data);
const envVariableSet = ref(props.data.telegram.telegram_env_variable_set);
const setupTelegramModalShown = ref(false);
const notificationSent = ref(false);
const form = useForm({
  content: '',
  label: '',
  hours: 9,
  minutes: 0,
  errors: [],
});

const hours = computed(() => {
  let result = [];
  for (let i = 0; i < 24; i++) {
    let name = i.toString().padStart(2, '0');
    result.push({ id: i, name: name });
  }
  return result;
});

const minutes = computed(() => {
  let result = [];
  for (let i = 0; i < 60; i += 5) {
    let name = i.toString().padStart(2, '0');
    result.push({ id: i, name: name });
  }
  return result;
});

watch(
  () => props.data,
  () => {
    localTelegram.value = props.data.telegram.data;
    if (localTelegram.value && localTelegram.value.active) {
      refresh.cancel();
    }
  },
);

onMounted(() => {
  if (localTelegram.value && !localTelegram.value.active) {
    refresh();
  }
});

const showSetupTelegramModal = () => {
  form.label = '';
  setupTelegramModalShown.value = true;
};

const sendTest = () => {
  axios
    .post(localTelegram.value.url.send_test)
    .then(() => {
      flash(trans('The notification has been sent'), 'success');
      notificationSent.value = true;
    })
    .catch((error) => {
      form.errors = error.response.data;
    });
};

const store = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.store_telegram, form.data())
    .then((response) => {
      flash(trans('The channel has been added'), 'success');
      localTelegram.value = response.data.data;
      loadingState.value = null;
      setupTelegramModalShown.value = false;
      refresh();
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const refresh = debounce(() => {
  router.reload({ only: ['data'] });
  refresh();
}, 2000);

const destroy = () => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(localTelegram.value.url.destroy)
      .then(() => {
        flash(trans('The Telegram channel has been deleted'), 'success');
        localTelegram.value = null;
        refresh.cancel();
      })
      .catch((error) => {
        loadingState.value = null;
        form.errors = error.response.data;
        refresh.cancel();
      });
  }
};
</script>

<template>
  <div>
    <div class="mb-3 flex items-center justify-between">
      <span class="dark:text-gray-200">
        {{ $t('Via Telegram') }}
      </span>

      <pretty-button
        v-if="!setupTelegramModalShown && !localTelegram && envVariableSet"
        :text="$t('Setup Telegram')"
        :icon="'plus'"
        @click="showSetupTelegramModal" />
    </div>

    <!-- add modal -->
    <form
      v-if="setupTelegramModalShown"
      class="item-list mb-6 rounded-lg border border-b border-gray-200 bg-gray-50 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:bg-slate-900 dark:hover:bg-slate-800"
      @submit.prevent="store()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <!-- preferred time -->
        <p class="mb-2 block text-sm">
          {{ $t('At which time should we send the notification, when the reminder occurs?') }}
        </p>
        <div class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
          <span class="me-2">
            {{ $t('At') }}
          </span>

          <Dropdown v-model="form.hours" dropdown-class="me-1" :required="required" :data="hours" />

          <span class="me-2">:</span>

          <Dropdown v-model="form.minutes" dropdown-class="me-1" :required="required" :data="minutes" />
        </div>
      </div>

      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <p class="mb-4 font-semibold"><span class="me-1">👋</span> {{ $t('What happens now?') }}</p>
        <ol class="ms-4 list-decimal">
          <li class="mb-2">
            {{
              $t(
                'Once you click the Setup button below, you’ll have to open Telegram with the button we’ll provide you with. This will locate the Monica Telegram bot for you.',
              )
            }}
          </li>
          <li class="mb-2">
            {{ $t('Type anything in the conversation with the Monica bot. It can be `start` for instance.') }}
          </li>
          <li>
            {{
              $t(
                'Wait a few seconds for Monica (the application) to recognize you. We’ll send you a fake notification to see if it works.',
              )
            }}
          </li>
        </ol>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="setupTelegramModalShown = false" />
        <pretty-button :text="$t('Setup')" :state="loadingState" :icon="'check'" :class="'save'" />
      </div>
    </form>

    <!-- case if env variables are not set -->
    <div
      v-if="!envVariableSet"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="p-5 text-center">
        {{ $t('You have not setup Telegram in your environment variables yet.') }}
      </p>
    </div>

    <div v-if="envVariableSet">
      <div v-if="localTelegram">
        <div
          class="flex items-center justify-between rounded-lg border border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <div class="flex items-center">
            <a-tooltip v-if="localTelegram.active" placement="topLeft" title="Verified" arrow-point-at-center>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="me-2 inline h-4 w-4 text-green-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
              </svg>
            </a-tooltip>

            <!-- telegram user id -->
            <div>
              <span v-if="localTelegram.active" class="mb-0 block">{{ $t('Your account is linked') }}</span>
            </div>
          </div>

          <!-- actions when Telegram is not active -->
          <div v-if="!localTelegram.active">
            <p class="mb-4 font-semibold"><span class="me-1">👋</span> {{ $t('What happens now?') }}</p>
            <ol class="ms-4 list-decimal">
              <li class="mb-2">
                <a
                  :href="localTelegram.url.open"
                  target="_blank"
                  class="text-blue-500 hover:underline"
                  rel="noopener noreferrer">
                  {{ $t('Open Telegram to validate your identity') }}
                </a>
              </li>
              <li class="mb-2">
                {{ $t('Type anything in the conversation with the Monica bot. It can be `start` for instance.') }}
              </li>
              <li>
                {{
                  $t(
                    'Wait a few seconds for Monica (the application) to recognize you. We’ll send you a fake notification to see if it works.',
                  )
                }}
              </li>
            </ol>

            <ul class="mt-4 text-sm">
              <!-- delete email -->
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy">
                {{ $t('Delete') }}
              </li>
            </ul>
          </div>

          <ul v-else class="text-sm">
            <!-- link to send a test notification, if not already sent -->
            <li
              v-if="!notificationSent"
              class="me-4 inline cursor-pointer text-blue-500 hover:underline"
              @click="sendTest">
              {{ $t('Send test') }}
            </li>
            <li v-if="notificationSent" class="me-4 inline">
              {{ $t('Notification sent') }}
            </li>

            <!-- view log -->
            <li class="me-4 inline cursor-pointer text-blue-500 hover:underline">
              <Link :href="localTelegram.url.logs" class="text-blue-500 hover:underline">
                {{ $t('View log') }}
              </Link>
            </li>

            <!-- delete email -->
            <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy">
              {{ $t('Delete') }}
            </li>
          </ul>
        </div>
      </div>

      <!-- blank state -->
      <div v-else class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <p class="p-5 text-center">
          {{ $t('You haven’t setup Telegram yet.') }}
        </p>
      </div>
    </div>
  </div>
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
</style>
