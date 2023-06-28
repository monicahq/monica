<script setup>
import { ref, nextTick, watch, onMounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import JetInputError from '@/Components/InputError.vue';
import JetButton from '@/Components/Button.vue';
import WaitForKey from '@/Pages/Webauthn/Partials/WaitForKey.vue';
import { webAuthnNotSupportedMessage } from '@/methods.js';
import { startAuthentication, browserSupportsWebAuthn } from '@simplewebauthn/browser';

const props = defineProps({
  publicKey: Object,
  remember: Boolean,
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
    loginWaitForKey(props.publicKey);
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
  router.reload({ only: ['publicKey'] });
};

const stop = () => {
  processing.value = false;
};

const loginWaitForKey = (publicKey) => {
  processing.value = true;
  nextTick()
    .then(() => startAuthentication(publicKey))
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

      <JetButton class="ms-2" @click="start()" v-show="!processing">
        {{ $t('Retry') }}
      </JetButton>
    </div>
  </div>
</template>
