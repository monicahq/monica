<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import JetButton from '@/Components/Button.vue';
import JetCheckbox from '@/Components/Checkbox.vue';
import JetValidationErrors from '@/Components/ValidationErrors.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import PrimaryButton from '@/Shared/Form/PrimaryButton.vue';
import WebauthnLogin from '@/Pages/Webauthn/WebauthnLogin.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Beta from './Beta.vue';
import ExternalProviders from './ExternalProviders.vue';
import { platformAuthenticatorIsAvailable } from '@simplewebauthn/browser';

const props = defineProps({
  isSignupEnabled: Boolean,
  canResetPassword: Boolean,
  status: String,
  wallpaperUrl: String,
  providers: Object,
  publicKey: Object,
  userless: Boolean,
  autologin: Boolean,
  beta: Boolean,
});
const webauthn = ref(false);
const publicKeyRef = ref(null);
const platformAuthenticatorAvailable = ref(false);
const useSecurityKey = computed(
  () => (publicKeyRef.value !== null && props.autologin && platformAuthenticatorAvailable.value) || webauthn.value,
);

watch(
  () => props.publicKey,
  (value) => {
    publicKeyRef.value = value;
  },
);

onMounted(() => {
  publicKeyRef.value = props.publicKey;
  platformAuthenticatorIsAvailable().then((available) => {
    platformAuthenticatorAvailable.value = available;
  });
});

const form = useForm({
  email: '',
  password: '',
  remember: true,
});

watch(
  () => props.publicKey,
  (value) => {
    publicKeyRef.value = value;
  },
);

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      remember: form.remember ? 'on' : '',
    }))
    .post(route('login'), {
      onFinish: () => form.reset('password'),
    });
};

const useWebauthn = () => {
  form.reset();
  webauthn.value = true;
};
</script>

<template>
  <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 dark:bg-gray-900 sm:justify-center sm:pt-0">
    <div class="mb-2">
      <AuthenticationCardLogo />
    </div>

    <Beta :beta="beta" />

    <h1 class="mt-4 text-center text-xl text-gray-800 dark:text-gray-200">
      <span class="me-2"> 👋 </span>
      {{ $t('Sign in to your account') }}
    </h1>

    <div
      :class="[
        'mt-6 mb-4 flex w-full flex-col overflow-hidden bg-white shadow-md/20 dark:shadow-md/80 dark:bg-gray-800 sm:max-w-4xl',
        'rounded-lg md:flex-row border border-gray-100 dark:border-gray-600',
      ]">
      <img :src="wallpaperUrl" class="w-full hidden sm:w-10/12 md:block object-cover" :alt="$t('Wallpaper')" />
      <div class="w-full">
        <div class="px-6 pb-6 pt-8">
          <JetValidationErrors class="mb-2" />

          <div v-if="status" class="mb-2 text-sm font-medium text-green-600 dark:text-green-400">
            {{ status }}
          </div>

          <form @submit.prevent="submit" class="dark:text-gray-800">
            <div class="mb-3">
              <TextInput
                v-model="form.email"
                :label="$t('Email')"
                :type="'email'"
                :input-class="'block w-full'"
                :required="true"
                :autofocus="true"
                :autocomplete="'username webauthn'"
                :maxlength="255" />
            </div>

            <div class="mb-3 relative">
              <label class="mb-2 text-sm dark:text-gray-100" :for="'password'">
                {{ $t('Password') }}
              </label>

              <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="text-sm text-blue-500 hover:underline absolute right-0">
                {{ $t('Forgot your password?') }}
              </Link>

              <TextInput
                :id="'password'"
                v-model="form.password"
                :type="'password'"
                :input-class="'block w-full'"
                :required="true"
                :autocomplete="'current-password'"
                :maxlength="255" />
            </div>

            <div class="mb-3 flex justify-between">
              <div>
                <JetCheckbox id="remember" v-model:checked="form.remember" name="remember" />
                <label for="remember" class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                  {{ $t('Remember me') }}
                </label>
              </div>

              <PrimaryButton :text="$t('Log in')" :class="'save ms-4'" />
            </div>
          </form>
        </div>

        <div class="px-6 block" v-if="userless || publicKeyRef">
          <div class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
            <JetButton v-if="!useSecurityKey" class="block" @click.prevent="useWebauthn">
              {{ $t('Sign in with a passkey') }}
            </JetButton>
          </div>

          <WebauthnLogin v-if="useSecurityKey" :public-key="publicKeyRef" :remember="true" :autofill="true" />
        </div>

        <div v-if="isSignupEnabled" class="px-6 py-6 text-l dark:text-gray-50">
          {{ $t('New to Monica?') }}
          <Link :href="route('register')" class="text-blue-500 text-l hover:underline">
            {{ $t('Create an account') }}
          </Link>
        </div>
      </div>
    </div>

    <ExternalProviders class="mt-3 block px-6" :providers="providers" :remember="form.remember" />
  </div>
</template>
