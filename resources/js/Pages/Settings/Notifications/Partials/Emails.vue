<template>
  <div class="mb-8">
    <div class="mb-3 flex items-center justify-between">
      <span class="dark:text-gray-200">
        {{ $t('settings.notification_channels_email_title') }}
      </span>

      <pretty-button
        v-if="!addEmailModalShown"
        :text="$t('settings.notification_channels_email_cta')"
        :icon="'plus'"
        @click="showAddEmailModal" />
    </div>

    <!-- add modal -->
    <form
      v-if="addEmailModalShown"
      class="item-list bg-form mb-6 rounded-lg border border-b border-gray-200 hover:bg-slate-50"
      @submit.prevent="store()">
      <div class="border-b border-gray-200 p-5">
        <errors :errors="form.errors" />

        <!-- content -->
        <text-input
          :ref="'content'"
          v-model="form.content"
          :label="$t('settings.notification_channels_email_field')"
          :type="'email'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :div-outer-class="'mb-4'"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="addEmailModalShown = false" />

        <!-- label -->
        <text-input
          v-model="form.label"
          :label="$t('settings.notification_channels_email_name')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :div-outer-class="'mb-4'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="addEmailModalShown = false" />

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
        <p class="flex"><span class="mr-2">⚠️</span> {{ $t('settings.notification_channels_email_help') }}</p>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click.prevent="addEmailModalShown = false" />
        <pretty-button :text="$t('app.add')" :state="loadingState" :icon="'plus'" :classes="'save'" />
      </div>
    </form>

    <!-- list of emails -->
    <ul v-if="localEmails.length > 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <li
        v-for="email in localEmails"
        :key="email.id"
        class="item-list flex items-center justify-between border-b border-gray-200 px-5 py-2 hover:bg-slate-50">
        <div class="flex items-center">
          <a-tooltip v-if="email.verified_at" placement="topLeft" title="Verified" arrow-point-at-center>
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

          <!-- email address + label -->
          <div>
            <span class="mb-0 block">{{ email.content }}</span>
            <ul class="bulleted-list mr-2 text-sm text-gray-500">
              <li v-if="email.label" class="mr-1 inline">
                {{ email.label }}
              </li>
              <li class="inline">
                {{ $t('settings.notification_channels_email_sent_at', { time: email.preferred_time }) }}
              </li>
            </ul>
          </div>
        </div>

        <!-- actions when the email has been verified -->
        <ul v-if="email.verified_at" class="text-sm">
          <!-- activate/deactivate -->
          <li
            v-if="email.active"
            class="mr-4 inline cursor-pointer text-blue-500 hover:underline"
            @click="toggle(email)">
            {{ $t('settings.notification_channels_email_deactivate') }}
          </li>
          <li
            v-if="!email.active"
            class="mr-4 inline cursor-pointer text-blue-500 hover:underline"
            @click="toggle(email)">
            {{ $t('settings.notification_channels_email_activate') }}
          </li>

          <!-- link to send a test email, if not already sent -->
          <li
            v-if="testEmailSentId != email.id"
            class="mr-4 inline cursor-pointer text-blue-500 hover:underline"
            @click="sendTest(email)">
            {{ $t('settings.notification_channels_email_send_test') }}
          </li>

          <!-- text saying that the email has been sent -->
          <li v-if="testEmailSentId == email.id" class="mr-4 inline">
            {{ $t('settings.notification_channels_email_send_success') }}
          </li>

          <!-- view log -->
          <li class="mr-4 inline cursor-pointer text-blue-500 hover:underline">
            <inertia-link :href="email.url.logs" class="text-blue-500 hover:underline">
              {{ $t('settings.notification_channels_email_log') }}
            </inertia-link>
          </li>

          <!-- delete email -->
          <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(email)">
            {{ $t('app.delete') }}
          </li>
        </ul>

        <!-- actions when the email has NOT been verified -->
        <ul v-else class="text-sm">
          <li class="mr-4 inline">
            {{ $t('settings.notification_channels_verif_email_sent') }}
          </li>

          <!-- delete email -->
          <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(email)">
            {{ $t('app.delete') }}
          </li>
        </ul>
      </li>
    </ul>

    <!-- blank state -->
    <div v-else class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">
        {{ $t('settings.notification_channels_blank') }}
      </p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';

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
      localEmails: [],
      addEmailModalShown: false,
      testEmailSentId: 0,
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
    this.localEmails = this.data.emails;
    this.form.hours = '09';
    this.form.minutes = '00';
  },

  methods: {
    showAddEmailModal() {
      this.form.label = '';
      this.form.content = '';
      this.addEmailModalShown = true;

      this.$nextTick(() => {
        this.$refs.content.focus();
      });
    },

    sendTest(channel) {
      axios
        .post(channel.url.send_test)
        .then(() => {
          this.flash(this.$t('settings.notification_channels_success_email'), 'success');
          this.testEmailSentId = channel.id;
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
          this.localEmails[this.localEmails.findIndex((x) => x.id === channel.id)] = response.data.data;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    store() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('settings.notification_channels_email_added'), 'success');
          this.localEmails.unshift(response.data.data);
          this.loadingState = null;
          this.addEmailModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(channel) {
      if (confirm(this.$t('settings.notification_channels_email_destroy_confirm'))) {
        axios
          .delete(channel.url.destroy)
          .then(() => {
            this.flash(this.$t('settings.notification_channels_email_destroy_success'), 'success');
            var id = this.localEmails.findIndex((x) => x.id === channel.id);
            this.localEmails.splice(id, 1);
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
