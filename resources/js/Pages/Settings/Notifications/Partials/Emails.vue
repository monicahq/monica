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

<template>
  <div>
    <div class="mb-3 flex items-center justify-between">
      <span>Via email</span>

      <pretty-button v-if="!addEmailModalShown" :text="'Add an email'" :icon="'plus'" @click="showAddEmailModal" />
    </div>

    <!-- add modal -->
    <form
      v-if="addEmailModalShown"
      class="item-list mb-6 rounded-lg border border-b border-gray-200 bg-white hover:bg-slate-50"
      @submit.prevent="store()">
      <div class="border-b border-gray-200 p-5">
        <errors :errors="form.errors" />

        <text-input
          :ref="'content'"
          v-model="form.content"
          :label="'Which email address should we send the notification to?'"
          :type="'email'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :div-outer-class="'mb-4'"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="addEmailModalShown = false" />

        <text-input
          v-model="form.label"
          :label="'Give this email address a name'"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="addEmailModalShown = false" />
      </div>

      <div class="border-b border-gray-200 p-5">
        <p>
          <span class="mr-1">⚠️</span> We'll send an email to this email address that you will need to confirm before we
          can send notifications to this address.
        </p>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="'Cancel'" :classes="'mr-3'" @click.prevent="addEmailModalShown = false" />
        <pretty-button :text="'Add'" :state="loadingState" :icon="'plus'" :classes="'save'" />
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
              <li v-if="email.label" class="mr-1 inline">{{ email.label }}</li>
              <li class="inline">Sent at 9:00pm</li>
            </ul>
          </div>
        </div>

        <!-- actions when the email has been verified -->
        <ul v-if="email.verified_at" class="text-sm">
          <!-- activate/deactivate -->
          <li
            v-if="email.active"
            class="mr-4 inline cursor-pointer text-sky-500 hover:text-blue-900"
            @click="toggle(email)">
            Deactivate
          </li>
          <li
            v-if="!email.active"
            class="mr-4 inline cursor-pointer text-sky-500 hover:text-blue-900"
            @click="toggle(email)">
            Activate
          </li>

          <!-- link to send a test email, if not already sent -->
          <li
            v-if="testEmailSentId != email.id"
            class="mr-4 inline cursor-pointer text-sky-500 hover:text-blue-900"
            @click="sendTest(email)">
            Send test
          </li>

          <!-- text saying that the email has been sent -->
          <li v-if="testEmailSentId == email.id" class="mr-4 inline">Test email sent!</li>

          <!-- view log -->
          <li class="mr-4 inline cursor-pointer text-sky-500 hover:text-blue-900">View log</li>

          <!-- delete email -->
          <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(email)">Delete</li>
        </ul>

        <!-- actions when the email has NOT been verified -->
        <ul v-else class="text-sm">
          <!-- view log -->
          <li class="mr-4 inline">Verification email sent</li>

          <!-- delete email -->
          <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(email)">Delete</li>
        </ul>
      </li>
    </ul>

    <!-- blank state -->
    <div v-else class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">Add an email to be notified when a reminder occurs.</p>
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
      localEmails: [],
      addEmailModalShown: false,
      testEmailSentId: 0,
      form: {
        content: '',
        label: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localEmails = this.data.emails;
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
        .then((response) => {
          this.flash('The test email has been sent', 'success');
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
          this.flash('The channel has been updated', 'success');
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
          this.flash('The email has been added', 'success');
          this.localEmails.unshift(response.data.data);
          this.loadingState = null;
          this.addEmailModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(addressType) {
      this.loadingState = 'loading';

      axios
        .put(addressType.url.update, this.form)
        .then((response) => {
          this.flash('The address type has been updated', 'success');
          this.localAddressTypes[this.localAddressTypes.findIndex((x) => x.id === addressType.id)] = response.data.data;
          this.loadingState = null;
          this.renameAddressTypeModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(channel) {
      if (confirm('Are you sure? You can always add the email back later on if you want.')) {
        axios
          .delete(channel.url.destroy)
          .then((response) => {
            this.flash('The email address has been deleted', 'success');
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
