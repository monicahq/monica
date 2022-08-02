<template>
  <div>
    <div class="mb-3 flex items-center justify-between">
      <span class="dark:text-gray-200">
        {{ $t('settings.notification_channels_telegram_title') }}
      </span>

      <pretty-button
        v-if="!setupTelegramModalShown && !localTelegram && envVariableSet"
        :text="$t('settings.notification_channels_telegram_cta')"
        :icon="'plus'"
        @click="showSetupTelegramModal" />
    </div>

    <!-- add modal -->
    <form
      v-if="setupTelegramModalShown"
      class="item-list bg-form mb-6 rounded-lg border border-b border-gray-200 hover:bg-slate-50"
      @submit.prevent="store()">
      <div class="border-b border-gray-200 p-5">
        <errors :errors="form.errors" />

        <!-- preferred time -->
        <p class="mb-2 block text-sm">
          {{ $t('settings.notification_channels_email_at') }}
        </p>
        <div class="flex items-center text-sm font-medium text-gray-700">
          <span class="mr-2">
            {{ $t('settings.notification_channels_email_at_word') }}
          </span>

          <select
            v-model="form.hours"
            class="mr-1 rounded-md border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm"
            :required="required">
            <option v-for="n in 24" :key="n" :value="n - 1">
              {{ String(n - 1).padStart(2, '0') }}
            </option>
          </select>

          <span class="mr-2"> h: </span>

          <select
            v-model="form.minutes"
            class="mr-1 rounded-md border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm"
            :required="required">
            <option v-for="n in 12" :key="n" :value="(n - 1) * 5">
              {{ String((n - 1) * 5).padStart(2, '0') }}
            </option>
          </select>

          <span>m</span>
        </div>
      </div>

      <div class="border-b border-gray-200 p-5">
        <p class="mb-4 font-semibold"><span class="mr-1">👋</span> What happens now?</p>
        <ol class="ml-4 list-decimal">
          <li class="mb-2">
            Once you click the Setup button below, you'll have to open Telegram with the button we'll provide you with.
            This will locate the Monica Telegram bot for you.
          </li>
          <li class="mb-2">Type anything in the conversation with the Monica bot. It can be "start" for instance.</li>
          <li>
            Wait a few seconds for Monica (the application) to recognize you. We'll send you a fake notification to see
            if it works.
          </li>
        </ol>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click.prevent="setupTelegramModalShown = false" />
        <pretty-button :text="$t('app.add')" :state="loadingState" :icon="'plus'" :classes="'save'" />
      </div>
    </form>

    <!-- case if env variables are not set -->
    <div v-if="!envVariableSet" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">
        {{ $t('settings.notification_channels_telegram_not_set') }}
      </p>
    </div>

    <div v-if="envVariableSet">
      <div v-if="localTelegram">
        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-5 py-2 hover:bg-slate-50">
          <div class="flex items-center">
            <a-tooltip v-if="localTelegram.active" placement="topLeft" title="Verified" arrow-point-at-center>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="mr-2 inline h-4 w-4 text-green-600"
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
              <span v-if="localTelegram.active" class="mb-0 block">{{
                $t('settings.notification_channels_telegram_linked')
              }}</span>
            </div>
          </div>

          <!-- actions when Telegram is not active -->
          <ul v-if="!localTelegram.active" class="text-sm">
            <li class="mr-4 inline">
              <a :href="localTelegram.url.open" target="_blank" class="text-blue-500 hover:underline"
                >Open Telegram to validate your identity</a
              >
            </li>

            <!-- delete email -->
            <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy">
              {{ $t('app.delete') }}
            </li>
          </ul>

          <ul v-if="localTelegram.active" class="text-sm">
            <!-- link to send a test notification, if not already sent -->
            <li
              v-if="!notificationSent"
              class="mr-4 inline cursor-pointer text-blue-500 hover:underline"
              @click="sendTest">
              {{ $t('settings.notification_channels_email_send_test') }}
            </li>
            <li v-if="notificationSent" class="mr-4 inline">
              {{ $t('settings.notification_channels_telegram_test_notification_sent') }}
            </li>

            <!-- view log -->
            <li class="mr-4 inline cursor-pointer text-blue-500 hover:underline">
              <inertia-link :href="localTelegram.url.logs" class="text-blue-500 hover:underline">
                {{ $t('settings.notification_channels_email_log') }}
              </inertia-link>
            </li>

            <!-- delete email -->
            <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy">
              {{ $t('app.delete') }}
            </li>
          </ul>
        </div>
      </div>

      <!-- blank state -->
      <div v-else class="mb-6 rounded-lg border border-gray-200 bg-white">
        <p class="p-5 text-center">
          {{ $t('settings.notification_channels_telegram_blank') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    PrettyButton,
    PrettySpan,
    TextInput,
    Errors,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      loadingState: '',
      localTelegram: null,
      envVariableSet: null,
      setupTelegramModalShown: false,
      testEmailSentId: 0,
      notificationSent: false,
      form: {
        content: '',
        label: '',
        minutes: '',
        hours: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localTelegram = this.data.telegram.data;
    this.envVariableSet = this.data.telegram.telegram_env_variable_set;
    this.form.hours = '09';
    this.form.minutes = '00';
  },

  methods: {
    showSetupTelegramModal() {
      this.form.label = '';
      this.setupTelegramModalShown = true;
    },

    sendTest() {
      axios
        .post(this.localTelegram.url.send_test)
        .then(() => {
          this.flash(this.$t('settings.notification_channels_test_success_telegram'), 'success');
          this.notificationSent = true;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    toggle(channel) {
      axios
        .put(channel.url.toggle)
        .then((response) => {
          this.flash(this.$t('settings.notification_channels_success_channel'), 'success');
          this.localTelegram[this.localTelegram.findIndex((x) => x.id === channel.id)] = response.data.data;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    store() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store_telegram, this.form)
        .then((response) => {
          this.flash(this.$t('settings.notification_channels_email_added'), 'success');
          this.localTelegram = response.data.data;
          this.loadingState = null;
          this.setupTelegramModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy() {
      if (confirm(this.$t('settings.notification_channels_telegram_delete_confirm'))) {
        axios
          .delete(this.localTelegram.url.destroy)
          .then(() => {
            this.flash(this.$t('settings.notification_channels_telegram_destroy_success'), 'success');
            this.localTelegram = null;
          })
          .catch((error) => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },
  },
};
</script>

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

select {
  padding-left: 8px;
  padding-right: 20px;
  background-position: right 3px center;
}
</style>
