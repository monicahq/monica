<script setup>
import { ref, nextTick, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import { trans } from 'laravel-vue-i18n';
import JetInputError from '@/Components/InputError.vue';
import JetButton from '@/Components/Button.vue';
import WaitForKey from '@/Pages/Webauthn/Partials/WaitForKey.vue';
import WebAuthn from '@/webauthn.js';

const props = defineProps({
  publicKey: Object,
  remember: Boolean,
});

const isSupported = ref(true);
const errorMessage = ref('');
const processing = ref(false);

const authForm = useForm();

watch(
  () => props.publicKey,
  (value) => {
    errorMessage.value = '';
    nextTick().then(() => loginWaitForKey(value));
  },
);

onMounted(() => {
  errorMessage.value = '';

  if (!webauthn.webAuthnSupport()) {
    isSupported.value = false;
    errorMessage.value = notSupportedMessage();
  }

  if (props.publicKey) {
    loginWaitForKey(props.publicKey);
  }
});

const webauthn = new WebAuthn((name, message) => {
  errorMessage.value = _errorMessage(name, message);
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

const notSupportedMessage = () => {
  switch (webauthn.notSupportedMessage()) {
    case 'not_supported':
      return trans('Your browser doesn’t currently support WebAuthn.');
    case 'not_secured':
      return trans('WebAuthn only supports secure connections. Please load this page with https scheme.');
    default:
      return '';
  }
};

const start = () => {
  errorMessage.value = '';
  Inertia.reload({ only: ['publicKey'] });
};

const stop = () => {
  processing.value = false;
};

const loginWaitForKey = (publicKey) => {
  processing.value = true;
  nextTick().then(() =>
    webauthn.sign(publicKey, (data) => {
      webauthnLoginCallback(data);
    }),
  );
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
      onSuccess: () => {
        stop();
      },
      onError: (error) => {
        errorMessage.value = error.message ? error.message : error.data.errors[0];
        stop();
      },
    });
};
</script>

<template>
  <div>
    <div v-if="!isSupported">
      {{ notSupportedMessage() }}
    </div>
    <div v-else>
      <WaitForKey :error-message="errorMessage" :form="authForm" @retry="start()" />

      <JetInputError :message="authForm.errors.data" class="mt-2" />

      <JetButton class="ml-2" @click="start()" v-show="!processing">
        {{ $t('Retry') }}
      </JetButton>
    </div>
  </div>
</template>
