<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import JetButton from '@/Components/Button.vue';
import JetGuestLayout from '@/Shared/Guest.vue';
import JetInput from '@/Components/Input.vue';
import JetLabel from '@/Components/Label.vue';
import JetValidationErrors from '@/Components/ValidationErrors.vue';

const props = defineProps({
  data: Object,
});

const form = useForm({
  first_name: '',
  last_name: '',
  password: '',
  password_confirmation: '',
  invitation_code: props.data.invitation_code,
});

const submit = () => {
  form.post(props.data.url.store, {
    onSuccess: (response) => {
      localStorage.success = trans('Your account has been created');
      router.visit(response.data.data);
    },
  });
};
</script>

<template>
  <JetGuestLayout>
    <JetValidationErrors class="mb-4" />

    <form @submit.prevent="submit">
      <h1 class="mb-3 text-center text-xl">
        <span class="me-2"> 👋 </span>
        {{ $t('Welcome to Monica.') }}
      </h1>
      <p class="mb-4 text-center">{{ $t('Please complete this form to finalize your account.') }}'</p>

      <div>
        <JetLabel for="first_name" ::value="$t('First name')" />
        <JetInput
          id="first_name"
          v-model="form.first_name"
          type="text"
          class="mt-1 block w-full"
          required
          autofocus
          autocomplete="first_name" />
      </div>

      <div class="mt-4">
        <JetLabel for="last_name" :value="$t('Last name')" />
        <JetInput
          id="last_name"
          v-model="form.last_name"
          type="text"
          class="mt-1 block w-full"
          required
          autocomplete="last_name" />
      </div>

      <div class="mt-4">
        <JetLabel for="password" :value="$t('Password')" />
        <JetInput
          id="password"
          v-model="form.password"
          type="password"
          class="mt-1 block w-full"
          required
          autocomplete="new-password" />
      </div>

      <div class="mt-4">
        <JetLabel for="password_confirmation" :value="$t('Confirm Password')" />
        <JetInput
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="mt-1 block w-full"
          required
          autocomplete="new-password" />
      </div>

      <div class="mt-4 flex items-center justify-end">
        <JetButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
          {{ $t('Create account') }}
        </JetButton>
      </div>
    </form>
  </JetGuestLayout>
</template>
