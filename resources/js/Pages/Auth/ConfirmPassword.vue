<template>
  <div>
    <div class="mb-4 text-sm text-gray-600">
      This is a secure area of the application. Please confirm your password before continuing.
    </div>

    <breeze-validation-errors class="mb-4" />

    <form @submit.prevent="submit">
      <div>
        <breeze-label for="password" value="Password" />
        <breeze-input
          id="password"
          v-model="form.password"
          type="password"
          class="mt-1 block w-full"
          required
          autocomplete="current-password"
          autofocus />
      </div>

      <div class="mt-4 flex justify-end">
        <breeze-button class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
          Confirm
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

  data() {
    return {
      form: this.$inertia.form({
        password: '',
      }),
    };
  },

  methods: {
    submit() {
      this.form.post(this.route('password.confirm'), {
        onFinish: () => this.form.reset(),
      });
    },
  },
};
</script>
