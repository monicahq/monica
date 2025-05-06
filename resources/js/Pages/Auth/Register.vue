<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import JetGuestLayout from '@/Shared/Guest.vue';
import JetInput from '@/Components/Input.vue';
import JetLabel from '@/Components/Label.vue';
import JetValidationErrors from '@/Components/ValidationErrors.vue';
import JetCheckbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Shared/Form/PrimaryButton.vue';
import Beta from './Beta.vue';
import ExternalProviders from './ExternalProviders.vue';

defineProps({
  providers: Object,
  beta: Boolean,
});

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false,
});

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};

const terms = () => {
  return `<a target="_blank" href="${route(
    'terms.show',
  )}" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">${trans(
    'Terms of Service',
  )}</a>`;
};
const policy = () => {
  return `<a target="_blank" href="${route(
    'policy.show',
  )}" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">${trans(
    'Privacy Policy',
  )}</a>`;
};
</script>

<template>
  <JetGuestLayout>
    <JetValidationErrors class="mb-4" />

    <p class="mb-2 text-lg font-bold">{{ $t('Sign up for an account') }}</p>
    <p class="mb-8 text-sm text-gray-500">{{ $t('Your name here will be used to add yourself as a contact.') }}</p>

    <form @submit.prevent="submit">
      <div>
        <JetLabel for="first_name" :value="$t('First name')" />
        <JetInput
          id="first_name"
          v-model="form.first_name"
          type="text"
          class="mt-1 block w-full"
          required
          autofocus
          :autocomplete="'first_name'" />
      </div>

      <div class="mt-4">
        <JetLabel for="last_name" :value="$t('Last name')" />
        <JetInput
          id="last_name"
          v-model="form.last_name"
          type="text"
          class="mt-1 block w-full"
          required
          :autocomplete="'last_name'" />
      </div>

      <div class="mt-4">
        <JetLabel for="email" :value="$t('Email')" />
        <JetInput
          id="email"
          v-model="form.email"
          type="email"
          class="mt-1 block w-full"
          required
          autocomplete="username" />
      </div>

      <div class="mt-4">
        <JetLabel for="password" :value="$t('Password')" />
        <JetInput
          id="password"
          v-model="form.password"
          type="password"
          class="mt-1 mb-1 block w-full"
          required
          autocomplete="new-password" />
        <p class="text-xs text-gray-600">{{ $t('The password should be at least 8 characters long.') }}</p>
      </div>

      <div class="mb-8 mt-4">
        <JetLabel for="password_confirmation" :value="$t('Confirm Password')" />
        <JetInput
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="mt-1 block w-full"
          required
          autocomplete="new-password" />
      </div>

      <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="mb-4 mt-4">
        <JetLabel for="terms">
          <div class="flex">
            <JetCheckbox id="terms" v-model:checked="form.terms" name="terms" />

            <div
              class="ms-2"
              v-html="$t('I agree to the :terms and :policy', { terms: terms(), policy: policy() })"></div>
          </div>
        </JetLabel>
      </div>

      <Beta :beta="beta" />

      <div class="mt-4 flex items-center justify-end">
        <Link :href="route('login')" class="me-4 text-sm text-blue-500 hover:underline">
          {{ $t('Already registered?') }}
        </Link>

        <PrimaryButton :text="$t('Register')" :class="'save'" />
      </div>
    </form>

    <ExternalProviders class="mt-5" :providers="providers" :remember="true" />
  </JetGuestLayout>
</template>
