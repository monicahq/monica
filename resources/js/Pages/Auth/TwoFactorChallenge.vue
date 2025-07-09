<script setup>
import { nextTick, ref, useTemplateRef } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Layouts/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import JetButton from '@/Components/Button.vue';
import JetInput from '@/Components/Input.vue';
import JetLabel from '@/Components/Label.vue';
import JetValidationErrors from '@/Components/ValidationErrors.vue';
import WebauthnLogin from '@/Pages/Webauthn/WebauthnLogin.vue';

const recovery = ref(false);

const form = useForm({
  code: '',
  recovery_code: '',
});

const recoveryCodeInput = useTemplateRef('recoveryCodeInput');
const codeInput = useTemplateRef('codeInput');

const toggleRecovery = async () => {
  recovery.value ^= true;

  await nextTick();

  if (recovery.value) {
    recoveryCodeInput.value.focus();
    form.code = '';
  } else {
    codeInput.value.focus();
    form.recovery_code = '';
  }
};

const submit = () => {
  form.post(route('two-factor.login'));
};

defineProps({
  twoFactor: Boolean,
  remember: Boolean,
  publicKey: Object,
});
</script>

<template>
  <Head :title="$t('Two-factor Confirmation')" />

  <AuthenticationCard>
    <template #logo>
      <AuthenticationCardLogo />
    </template>

    <div v-if="publicKey !== null">
      <h1 class="mb-4 max-w-xl text-gray-600 dark:text-gray-400">
        {{ $t('Please confirm access to your account by validating your security key.') }}
      </h1>

      <WebauthnLogin :remember="remember" :public-key="publicKey" :autofill="false" />
    </div>

    <div v-if="twoFactor">
      <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        <template v-if="!recovery">
          {{
            $t(
              'Please confirm access to your account by entering the authentication code provided by your authenticator application.',
            )
          }}
        </template>

        <template v-else>
          {{ $t('Please confirm access to your account by entering one of your emergency recovery codes.') }}
        </template>
      </div>

      <JetValidationErrors class="mb-4" />

      <form @submit.prevent="submit">
        <div v-if="!recovery">
          <JetLabel for="code" :value="$t('Code')" />
          <JetInput
            id="code"
            ref="codeInput"
            v-model="form.code"
            type="text"
            inputmode="numeric"
            class="mt-1 block w-full"
            autofocus
            :autocomplete="'one-time-code'" />
        </div>

        <div v-else>
          <JetLabel for="recovery_code" :value="$t('Recovery Code')" />
          <JetInput
            id="recovery_code"
            ref="recoveryCodeInput"
            v-model="form.recovery_code"
            type="text"
            class="mt-1 block w-full"
            :autocomplete="'one-time-code'" />
        </div>

        <div class="mt-4 flex items-center justify-end">
          <button
            type="button"
            class="cursor-pointer text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400"
            @click.prevent="toggleRecovery">
            <template v-if="!recovery">
              {{ $t('Use a recovery code') }}
            </template>

            <template v-else>
              {{ $t('Use an authentication code') }}
            </template>
          </button>

          <JetButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
            {{ $t('Log in') }}
          </JetButton>
        </div>
      </form>
    </div>
  </AuthenticationCard>
</template>
