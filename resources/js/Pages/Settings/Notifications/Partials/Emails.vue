<template>
  <div class="mb-8">
    <div class="mb-3 flex items-center justify-between">
      <span class="dark:text-gray-200">
        {{ $t('You haven’t received a notification in this channel yet.') }}
      </span>

      <pretty-button
        v-if="!addEmailModalShown"
        :text="$t('Add an email address')"
        :icon="'plus'"
        @click="showAddEmailModal" />
    </div>

    <!-- add modal -->
    <form
      v-if="addEmailModalShown"
      class="item-list mb-6 rounded-lg border border-b border-gray-200 bg-gray-50 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:bg-slate-900 dark:hover:bg-slate-800"
      @submit.prevent="store()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <!-- content -->
        <text-input
          ref="content"
          v-model="form.content"
          :label="$t('Which email address should we send the notification to?')"
          :type="'email'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :class="'mb-4'"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="addEmailModalShown = false" />

        <!-- label -->
        <text-input
          v-model="form.label"
          :label="$t('Give this email address a name')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :class="'mb-4'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="addEmailModalShown = false" />

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
        <p class="flex">
          <span class="me-2">⚠️</span>
          {{
            $t(
              'We’ll send an email to this email address that you will need to confirm before we can send notifications to this address.',
            )
          }}
        </p>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="addEmailModalShown = false" />
        <pretty-button :text="$t('Add')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- list of emails -->
    <ul
      v-if="localEmails.length > 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <li
        v-for="email in localEmails"
        :key="email.id"
        class="item-list flex items-center justify-between border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
        <div class="flex items-center">
          <a-tooltip v-if="email.verified_at" placement="topLeft" :title="$t('Verified')" arrow-point-at-center>
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

          <!-- email address + label -->
          <div>
            <span class="mb-0 block">{{ email.content }}</span>
            <ul class="bulleted-list me-2 text-sm text-gray-500">
              <li v-if="email.label" class="me-1 inline">
                {{ email.label }}
              </li>
              <li class="inline">
                {{ $t('Sent at :time', { time: email.preferred_time }) }}
              </li>
            </ul>
          </div>
        </div>

        <!-- actions when the email has been verified -->
        <ul v-if="email.verified_at" class="text-sm">
          <!-- activate/deactivate -->
          <li
            v-if="email.active"
            class="me-4 inline cursor-pointer text-blue-500 hover:underline"
            @click="toggle(email)">
            {{ $t('Deactivate') }}
          </li>
          <li
            v-if="!email.active"
            class="me-4 inline cursor-pointer text-blue-500 hover:underline"
            @click="toggle(email)">
            {{ $t('Activate') }}
          </li>

          <!-- link to send a test email, if not already sent -->
          <li
            v-if="testEmailSentId !== email.id"
            class="me-4 inline cursor-pointer text-blue-500 hover:underline"
            @click="sendTest(email)">
            {{ $t('Send test') }}
          </li>

          <!-- text saying that the email has been sent -->
          <li v-if="testEmailSentId === email.id" class="me-4 inline">
            {{ $t('Test email sent!') }}
          </li>

          <!-- view log -->
          <li class="me-4 inline cursor-pointer text-blue-500 hover:underline">
            <InertiaLink :href="email.url.logs" class="text-blue-500 hover:underline">
              {{ $t('View log') }}
            </InertiaLink>
          </li>

          <!-- delete email -->
          <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(email)">
            {{ $t('Delete') }}
          </li>
        </ul>

        <!-- actions when the email has NOT been verified -->
        <ul v-else class="text-sm">
          <li class="me-4 inline">
            {{ $t('Verification email sent') }}
          </li>

          <!-- delete email -->
          <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(email)">
            {{ $t('Delete') }}
          </li>
        </ul>
      </li>
    </ul>

    <!-- blank state -->
    <div v-else class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="p-5 text-center">
        {{ $t('Add an email to be notified when a reminder occurs.') }}
      </p>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import { Tooltip as ATooltip } from 'ant-design-vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';

export default {
  components: {
    InertiaLink: Link,
    ATooltip,
    PrettyButton,
    PrettySpan,
    TextInput,
    Errors,
    Dropdown,
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

  computed: {
    hours() {
      let result = [];
      for (let i = 0; i < 24; i++) {
        let name = i.toString().padStart(2, '0');
        result.push({ id: i, name: name });
      }
      return result;
    },
    minutes() {
      let result = [];
      for (let i = 0; i < 60; i += 5) {
        let name = i.toString().padStart(2, '0');
        result.push({ id: i, name: name });
      }
      return result;
    },
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

      this.$nextTick().then(() => {
        this.$refs.content.focus();
      });
    },

    sendTest(channel) {
      axios
        .post(channel.url.send_test)
        .then(() => {
          this.flash(this.$t('The test email has been sent'), 'success');
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
          this.flash(this.$t('The channel has been updated'), 'success');
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
          this.flash(this.$t('The email has been added'), 'success');
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
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(channel.url.destroy)
          .then(() => {
            this.flash(this.$t('The email address has been deleted'), 'success');
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
</style>
