<script setup>
import { useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import JetActionMessage from '@/Components/Jetstream/ActionMessage.vue';
import JetButton from '@/Components/Button.vue';
import JetFormSection from '@/Components/Jetstream/FormSection.vue';
import JetInput from '@/Components/Input.vue';
import JetInputError from '@/Components/InputError.vue';
import JetLabel from '@/Components/Label.vue';

const passwordInput = useTemplateRef('passwordInput');
const currentPasswordInput = useTemplateRef('currentPasswordInput');

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  form.put(route('user-password.update'), {
    errorBag: 'updatePassword',
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: () => {
      if (form.errors.password) {
        form.reset('password', 'password_confirmation');
        passwordInput.value.focus();
      }

      if (form.errors.current_password) {
        form.reset('current_password');
        currentPasswordInput.value.focus();
      }
    },
  });
};
</script>

<template>
  <JetFormSection @submitted="updatePassword">
    <template #title>
      {{ $t('Update Password') }}
    </template>

    <template #description>
      {{ $t('Ensure your account is using a long, random password to stay secure.') }}
    </template>

    <template #form>
      <div class="col-span-6 sm:col-span-4">
        <JetLabel for="current_password" :value="$t('Current Password')" />
        <JetInput
          id="current_password"
          ref="currentPasswordInput"
          v-model="form.current_password"
          type="password"
          class="mb-4 mt-1 block w-full"
          autocomplete="current-password" />
        <JetInputError :message="form.errors.current_password" class="mt-2" />
      </div>

      <div class="col-span-6 sm:col-span-4">
        <JetLabel for="password" :value="$t('New Password')" />
        <JetInput
          id="password"
          ref="passwordInput"
          v-model="form.password"
          type="password"
          class="mb-4 mt-1 block w-full"
          autocomplete="new-password" />
        <JetInputError :message="form.errors.password" class="mt-2" />
      </div>

      <div class="col-span-6 sm:col-span-4">
        <JetLabel for="password_confirmation" :value="$t('Confirm Password')" />
        <JetInput
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="mb-4 mt-1 block w-full"
          autocomplete="new-password" />
        <JetInputError :message="form.errors.password_confirmation" class="mt-2" />
      </div>
    </template>

    <template #actions>
      <JetActionMessage :on="form.recentlySuccessful" class="me-3">
        {{ $t('Saved.') }}
      </JetActionMessage>

      <JetButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
        {{ $t('Save') }}
      </JetButton>
    </template>
  </JetFormSection>
</template>
