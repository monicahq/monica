<script setup>
import { useForm } from '@inertiajs/inertia-vue3';
import JetButton from '@/Components/Button.vue';
import JetGuestLayout from '@/Shared/Guest.vue';
import JetInput from '@/Components/Input.vue';
import JetLabel from '@/Components/Label.vue';
import JetValidationErrors from '@/Components/ValidationErrors.vue';

const form = useForm({
  password: '',
});

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => this.form.reset(),
  });
};
</script>

<template>
  <JetGuestLayout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
      This is a secure area of the application. Please confirm your password before continuing.
    </div>

    <JetValidationErrors class="mb-4" />

    <form @submit.prevent="submit">
      <div>
        <JetLabel for="password" value="Password" />
        <JetInput
          id="password"
          v-model="form.password"
          type="password"
          class="mt-1 block w-full"
          required
          autocomplete="current-password"
          autofocus />
      </div>

      <div class="mt-4 flex justify-end">
        <JetButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
          Confirm
        </JetButton>
      </div>
    </form>
  </JetGuestLayout>
</template>
