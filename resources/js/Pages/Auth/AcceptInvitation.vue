<template>
  <div>
    <breeze-validation-errors class="mb-4" />

    <form @submit.prevent="submit">
      <h1 class="mb-3 text-center text-xl"><span class="mr-2"> 👋 </span> Welcome to Monica.</h1>
      <p class="mb-4 text-center">Please complete this form to finalize your account.</p>

      <div>
        <breeze-label for="first_name" value="First name" />
        <breeze-input
          id="first_name"
          v-model="form.first_name"
          type="text"
          class="mt-1 block w-full"
          required
          autofocus
          autocomplete="first_name" />
      </div>

      <div class="mt-4">
        <breeze-label for="last_name" value="Last name" />
        <breeze-input
          id="last_name"
          v-model="form.last_name"
          type="text"
          class="mt-1 block w-full"
          required
          autocomplete="last_name" />
      </div>

      <div class="mt-4">
        <breeze-label for="password" value="Password" />
        <breeze-input
          id="password"
          v-model="form.password"
          type="password"
          class="mt-1 block w-full"
          required
          autocomplete="new-password" />
      </div>

      <div class="mt-4">
        <breeze-label for="password_confirmation" value="Confirm Password" />
        <breeze-input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="mt-1 block w-full"
          required
          autocomplete="new-password" />
      </div>

      <div class="mt-4 flex items-center justify-end">
        <breeze-button class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
          Create account
        </breeze-button>
      </div>
    </form>
  </div>
</template>

<script>
import BreezeButton from '@/Components/Button.vue';
import BreezeGuestLayout from '@/Shared/Guest.vue';
import BreezeInput from '@/Components/Input.vue';
import BreezeLabel from '@/Components/Label.vue';
import BreezeValidationErrors from '@/Components/ValidationErrors.vue';

export default {
  components: {
    BreezeButton,
    BreezeInput,
    BreezeLabel,
    BreezeValidationErrors,
  },
  layout: BreezeGuestLayout,

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      form: {
        first_name: '',
        last_name: '',
        password: '',
        password_confirmation: '',
        invitation_code: '',
      },
    };
  },

  mounted() {
    this.form.invitation_code = this.data.invitation_code;
  },

  methods: {
    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          localStorage.success = 'Your account has been created';
          this.$inertia.visit(response.data.data);
        })
        .catch((error) => {
          this.loadingState = null;
        });
    },
  },
};
</script>
