<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import size from 'lodash/size';
import JetCheckbox from '@/Components/Checkbox.vue';
import JetValidationErrors from '@/Components/ValidationErrors.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import PrimaryButton from '@/Shared/Form/PrimaryButton.vue';
import WebauthnLogin from '@/Pages/Webauthn/WebauthnLogin.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';

const props = defineProps({
  isSignupEnabled: Boolean,
  canResetPassword: Boolean,
  status: String,
  wallpaperUrl: String,
  providers: Object,
  publicKey: Object,
  userName: String,
});

const webauthn = ref(true);
const publicKeyRef = ref(props.publicKey);

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const providerForm = useForm({});
const errors = ref({
  email: null,
  password: null
});

// Email validation regex
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

watch(
  () => props.publicKey,
  (value) => {
    publicKeyRef.value = value;
  },
);

const providersExists = computed(() => size(props.providers) > 0);

// Validation logic
const validateForm = () => {
  errors.value.email = form.email === '' ? 'Email is required' : 
                       !emailRegex.test(form.email) ? 'Please enter a valid email' : null;

  errors.value.password = form.password === '' ? 'Password is required' : null;

  return !errors.value.email && !errors.value.password; // Returns true if no errors
};

const submit = () => {
  if (!validateForm()) return; // Prevent submission if validation fails

  form
    .transform((data) => ({
      ...data,
      remember: form.remember ? 'on' : '',
    }))
    .post(route('login'), {
      onFinish: () => form.reset('password'),
    });
};

const open = (provider) => {
  providerForm
    .transform(() => ({
      redirect: location.href,
      remember: form.remember ? 'on' : '',
    }))
    .get(route('login.provider', { driver: provider }), {
      preserveScroll: true,
      onFinish: () => {
        providerForm.reset();
      },
    });
};

const reload = () => {
  publicKeyRef.value = null;
  webauthn.value = true;
  router.reload({ only: ['publicKey'] });
};
</script>

<template>
  <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 dark:bg-gray-900 sm:justify-center sm:pt-0">
    <div class="mb-2">
      <AuthenticationCardLogo />
    </div>

    <div class="mt-6 mb-12 flex w-full flex-col overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:max-w-4xl sm:rounded-lg md:flex-row">
      <img :src="wallpaperUrl" class="w-full sm:invisible sm:w-10/12 md:visible" :alt="$t('Wallpaper')" />
      <div class="w-full">
        <div :class="{ 'border-b': isSignupEnabled }" class="border-gray-200 px-6 pb-6 pt-8 dark:border-gray-700">
          <h1 class="mb-4 text-center text-xl text-gray-800 dark:text-gray-200">
            <span class="me-2"> 👋 </span>
            {{ $t('Sign in to your account') }}
          </h1>

          <JetValidationErrors class="mb-2" />

          <div v-if="status" class="mb-2 text-sm font-medium text-green-600 dark:text-green-400">
            {{ status }}
          </div>

          <form @submit.prevent="submit" class="dark:text-gray-800">
            <!-- Email Input Field -->
            <div class="mb-3">
              <TextInput
                v-model="form.email"
                :label="$t('Email')"
                :type="'email'"
                :autofocus="true"
                :input-class="'block w-full'"
                :required="true"
                autocomplete="username"
                :maxlength="255"
              />
              <div v-if="errors.email" class="text-sm text-red-500">{{ errors.email }}</div> <!-- Display email error -->
            </div>

            <!-- Password Input Field -->
            <div class="mb-3">
              <TextInput
                v-model="form.password"
                :label="$t('Password')"
                :type="'password'"
                :input-class="'block w-full'"
                :required="true"
                autocomplete="current-password"
                :maxlength="255"
              />
              <div v-if="errors.password" class="text-sm text-red-500">{{ errors.password }}</div> <!-- Display password error -->
            </div>

            <!-- Remember Me -->
            <div class="mb-3 block">
              <label class="flex items-center">
                <JetCheckbox v-model:checked="form.remember" name="remember" />
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                  {{ $t('Remember me') }}
                </span>
              </label>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end">
              <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="text-sm text-blue-500 hover:underline">
                {{ $t('Forgot your password?') }}
              </Link>

              <primary-button :text="$t('Log in')" :class="'save ms-4'" />
            </div>
          </form>
        </div>

        <div v-if="isSignupEnabled" class="px-6 py-6 text-sm dark:text-gray-50">
          {{ $t('New to Monica?') }}
          <Link :href="route('register')" class="text-blue-500 hover:underline">
            {{ $t('Create an account') }}
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
