<script setup>
import { ref, nextTick, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import JetInputError from '@/Components/InputError.vue';
import WaitForKey from '@/Pages/Webauthn/Partials/WaitForKey.vue';
import { webAuthnNotSupportedMessage } from '@/methods.js';
import {
  startAuthentication,
  browserSupportsWebAuthn,
  browserSupportsWebAuthnAutofill,
  platformAuthenticatorIsAvailable,
} from '@simplewebauthn/browser';

const props = defineProps({
  publicKey: Object,
  remember: Boolean,
  autofill: Boolean,
});

const isSupported = ref(true);
const errorMessage = ref('');
const processing = ref(false);

const authForm = useForm({});

watch(
  () => props.publicKey,
  (value) => {
    errorMessage.value = '';
    loginWaitForKey(value);
  },
);

onMounted(() => {
  if (!browserSupportsWebAuthn()) {
    isSupported.value = false;
    errorMessage.value = webAuthnNotSupportedMessage();
  }

  if (props.publicKey) {
    platformAuthenticatorIsAvailable().then((available) => {
      if (available) {
        loginWaitForKey(props.publicKey);
      } else {
        errorMessage.value = trans('This browser does not support autofill.');
      }
    });
  }
});

const _errorMessage = (name, message) => {
  switch (name) {
    case 'InvalidStateError':
      return trans('Unexpected error on login.');
    case 'NotAllowedError':
      return trans('The operation either timed out or was not allowed.');
    default:
      return message;
  }
};

const start = () => {
  errorMessage.value = '';
  nextTick(() => {
    loginWaitForKey(props.publicKey);
  });
};

const stop = () => {
  processing.value = false;
};

const loginWaitForKey = (publicKey) => {
  processing.value = true;
  browserSupportsWebAuthnAutofill()
    .then((available) =>
      startAuthentication({ optionsJSON: publicKey, useBrowserAutofill: props.autofill && available }),
    )
    .then((data) => webauthnLoginCallback(data))
    .catch((error) => {
      errorMessage.value = _errorMessage(error.name, error.message);
    });
};

const webauthnLoginCallback = (data) => {
  authForm
    .transform(() => ({
      ...data,
      remember: props.remember ? 'on' : '',
    }))
    .post(route('webauthn.auth'), {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => stop(),
      onError: (error) => {
        errorMessage.value = error.message ?? error.data.errors[0];
        stop();
      },
    });
};
</script>

<template>
  <div>
    <div v-if="!isSupported">
      {{ webAuthnNotSupportedMessage() }}
    </div>
    <div v-else>
      <WaitForKey :error-message="errorMessage" :form="authForm" @retry="start()" />

      <JetInputError :message="authForm.errors.data" class="mt-2" />
    </div>
  </div>
</template>
