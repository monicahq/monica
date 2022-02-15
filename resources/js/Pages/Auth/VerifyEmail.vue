<template>
  <div>
    <div class="mb-4 text-sm text-gray-600">
      Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just
      emailed to you? If you didn't receive the email, we will gladly send you another.
    </div>

    <div v-if="verificationLinkSent" class="mb-4 text-sm font-medium text-green-600">
      A new verification link has been sent to the email address you provided during registration.
    </div>

    <form @submit.prevent="submit">
      <div class="mt-4 flex items-center justify-between">
        <breeze-button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
          Resend Verification Email
        </breeze-button>

        <inertia-link
          :href="route('logout')"
          method="post"
          as="button"
          class="text-sm text-gray-600 underline hover:text-gray-900">
          Log Out
        </inertia-link>
      </div>
    </form>
  </div>
</template>

<script>
import BreezeButton from '@/Components/Button.vue';
import BreezeGuestLayout from '@/Shared/Guest.vue';

export default {
  components: {
    BreezeButton,
  },
  layout: BreezeGuestLayout,

  props: {
    status: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      form: this.$inertia.form(),
    };
  },

  computed: {
    verificationLinkSent() {
      return this.status === 'verification-link-sent';
    },
  },

  methods: {
    submit() {
      this.form.post(this.route('verification.send'));
    },
  },
};
</script>
