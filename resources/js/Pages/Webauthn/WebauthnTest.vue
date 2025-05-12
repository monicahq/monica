<script setup>
import { nextTick, reactive } from 'vue';
import WaitForKey from '@/Pages/Webauthn/Partials/WaitForKey.vue';
import { startAuthentication } from '@simplewebauthn/browser';
import axios from 'axios';

const emit = defineEmits(['success']);

const form = reactive({
  error: '',
  hasErrors: false,
  started: false,
  processing: false,
});

const start = () => {
  form.started = true;
  form.error = '';
  form.hasErrors = false;
  form.processing = false;

  axios
    .post(route('webauthn.auth.options'))
    .then((response) => {
      if (response.data !== undefined) {
        loginWaitForKey(response.data.publicKey);
      } else {
        nextTick().then(() => loginWaitForKey(response.props.publicKey));
      }
    })
    .catch((e) => {
      form.processing = false;
      if (e.response.data.errors === undefined) {
        form.error = e.response.data.message;
      } else {
        form.error = e.response.data.errors[0];
      }
      form.hasErrors = true;
    });
};

const loginWaitForKey = (publicKey) => {
  form.processing = true;
  nextTick()
    .then(() => startAuthentication({ optionsJSON: publicKey }))
    .then((data) => webauthnLoginCallback(data))
    .catch((error) => {
      form.processing = false;
      form.error = error.message;
      form.hasErrors = true;
    });
};

const webauthnLoginCallback = (data) => {
  axios
    .post(route('webauthn.key.confirm'), data)
    .then(() => {
      form.started = false;
      form.processing = false;
      nextTick().then(() => emit('success'));
    })
    .catch((error) => {
      form.processing = false;
      form.error = error.response.data.message;
      form.hasErrors = true;
    });
};

defineExpose({ start: start });
</script>

<template>
  <div v-if="form.started">
    <WaitForKey class="mt-4" :error-message="form.error" :form="form" @retry="start()" />
  </div>
</template>
