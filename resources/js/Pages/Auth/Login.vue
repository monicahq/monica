<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { size } from 'lodash';
import JetCheckbox from '@/Components/Checkbox.vue';
import JetValidationErrors from '@/Components/ValidationErrors.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import WebauthnLogin from '@/Pages/Webauthn/WebauthnLogin.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';

const props = defineProps({
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

watch(
  () => props.publicKey,
  (value) => {
    publicKeyRef.value = value;
  },
);

const providersExists = computed(() => size(props.providers) > 0);

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

    <!-- beta mode-->
    <div class="mb-4 rounded-lg border bg-amber-50 p-6 dark:bg-amber-950">
      <p class="mb-2 text-center font-bold">
        <span class="me-2">🚧</span> {{ $t('Chandler is in beta.') }}
        <span class="ms-2">🚧</span>
      </p>
      <p class="mb-2">{{ $t('Compared to Monica:') }}</p>
      <ul class="list mb-2 ps-3">
        <li class="list-disc">
          {{ $t('it misses some of the features, the most important ones being the API and gift management,') }}
        </li>
        <li class="list-disc">{{ $t("you can't import any data from your current Monica account(yet),") }}</li>
        <li class="list-disc">{{ $t("you can't even use your current username or password to sign in,") }}</li>
        <li class="list-disc">{{ $t("however, there are many, many new features that didn't exist before.") }}</li>
      </ul>
      <p>{{ $t("We hope you'll like it.") }}</p>
    </div>

    <div
      class="mt-6 mb-12 flex w-full flex-col overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:max-w-4xl sm:rounded-lg md:flex-row">
      <img :src="wallpaperUrl" class="w-full sm:invisible sm:w-10/12 md:visible" :alt="$t('Wallpaper')" />
      <div class="w-full">
        <div class="border-b border-gray-200 px-6 pb-6 pt-8 dark:border-gray-700">
          <h1 class="mb-4 text-center text-xl text-gray-800 dark:text-gray-200">
            <span class="me-2"> 👋 </span>
            {{ $t('Sign in to your account') }}
          </h1>

          <JetValidationErrors class="mb-2" />

          <div v-if="status" class="mb-2 text-sm font-medium text-green-600 dark:text-green-400">
            {{ status }}
          </div>

          <div v-if="publicKey && webauthn">
            <div class="mb-4 text-center text-lg text-gray-900 dark:text-gray-100">
              {{ userName }}
            </div>
            <div class="mb-4 max-w-xl text-gray-600 dark:text-gray-400">
              {{ $t('Connect with your security key') }}
            </div>

            <WebauthnLogin :remember="true" :public-key="publicKeyRef" />

            <JetSecondaryButton class="me-2 mt-4" @click.prevent="webauthn = false">
              {{ $t('Use your password') }}
            </JetSecondaryButton>
          </div>

          <form v-else @submit.prevent="submit" class="dark:text-gray-800">
            <div class="mb-3">
              <TextInput
                v-model="form.email"
                :label="$t('Email')"
                :type="'email'"
                :autofocus="true"
                :input-class="'block w-full'"
                :required="true"
                autocomplete="username"
                :maxlength="255" />
            </div>

            <div class="mb-3">
              <TextInput
                v-model="form.password"
                :label="$t('Password')"
                :type="'password'"
                :input-class="'block w-full'"
                :required="true"
                autocomplete="current-password"
                :maxlength="255" />
            </div>

            <div class="mb-3 block">
              <label class="flex items-center">
                <JetCheckbox v-model:checked="form.remember" name="remember" />
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                  {{ $t('Remember me') }}
                </span>
              </label>
            </div>

            <div class="flex items-center justify-end">
              <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="text-sm text-blue-500 hover:underline">
                {{ $t('Forgot your password?') }}
              </Link>

              <PrettyButton :text="$t('Log in')" :state="loadingState" :class="'save ms-4'" />
            </div>

            <div class="mt-3 block">
              <p v-if="providersExists" class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t('Login with:') }}
              </p>
              <div class="flex flex-wrap">
                <JetSecondaryButton
                  v-for="(provider, id) in providers"
                  :key="id"
                  class="mb-2 me-2 inline w-32 align-middle"
                  :href="route('login.provider', { driver: id })"
                  @click.prevent="open(id)">
                  <img :src="provider.logo" :alt="provider.name" class="relative me-2 h-4 w-4 align-middle" />
                  <span class="align-middle">
                    {{ provider.name }}
                  </span>
                </JetSecondaryButton>
              </div>
            </div>

            <div v-if="publicKeyRef" class="mt-3 block">
              <JetSecondaryButton class="me-2" @click.prevent="reload">
                {{ $t('Use your security key') }}
              </JetSecondaryButton>
            </div>
          </form>
        </div>

        <div class="px-6 py-6 text-sm dark:text-gray-50">
          {{ $t('New to Monica?') }}
          <Link :href="route('register')" class="text-blue-500 hover:underline">
            {{ $t('Create an account') }}
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
