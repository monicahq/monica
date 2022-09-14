<template>
  <div>
    <breeze-validation-errors class="mb-4" />

    <p class="mb-2 text-lg font-bold">Sign up for an account</p>
    <p class="mb-8 text-sm text-gray-500">Your name here will be used to add yourself as a contact.</p>

    <form @submit.prevent="submit">
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
        <breeze-label for="email" value="Email" />
        <breeze-input
          id="email"
          v-model="form.email"
          type="email"
          class="mt-1 block w-full"
          required
          autocomplete="username" />
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

      <div class="mt-4 mb-8">
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
        <inertia-link :href="route('login')" class="mr-4 text-sm text-blue-500 hover:underline dark:text-gray-400">
          Already registered?
        </inertia-link>

        <PrettyButton :text="'Register'" :state="loadingState" :classes="'save'" />
      </div>
    </form>
  </div>
</template>

<script>
import BreezeGuestLayout from '@/Shared/Guest.vue';
import BreezeInput from '@/Components/Input.vue';
import BreezeLabel from '@/Components/Label.vue';
import BreezeValidationErrors from '@/Components/ValidationErrors.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';

export default {
  components: {
    BreezeInput,
    BreezeLabel,
    BreezeValidationErrors,
    PrettyButton,
  },
  layout: BreezeGuestLayout,

  data() {
    return {
      form: this.$inertia.form({
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        password_confirmation: '',
        terms: false,
      }),
    };
  },

  methods: {
    submit() {
      this.form.post(this.route('register'), {
        onFinish: () => this.form.reset('password', 'password_confirmation'),
      });
    },
  },
};
</script>
