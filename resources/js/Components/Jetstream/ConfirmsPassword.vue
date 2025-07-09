<script setup>
import { ref, reactive, nextTick, computed, useTemplateRef } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import Button from '@/Components/Button.vue';
import DialogModal from './DialogModal.vue';
import Input from '@/Components/Input.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from './SecondaryButton.vue';
import WebauthnTest from '@/Pages/Webauthn/WebauthnTest.vue';

const emit = defineEmits(['confirmed']);

defineProps({
  title: {
    type: String,
    default: trans('Confirm access'),
  },
  content: {
    type: String,
    default: trans('For your security, please confirm the access to continue.'),
  },
  button: {
    type: String,
    default: trans('Confirm'),
  },
});

const confirmingPassword = ref(false);

const form = reactive({
  password: '',
  error: '',
  processing: false,
});

const passwordInput = ref(null);
const webauthn = useTemplateRef('webauthn');
const webauthnEnabled = computed(() => usePage().props.hasKey === true);

const startConfirmingPassword = () => {
  axios.get(route('password.confirmation')).then((response) => {
    if (response.data.confirmed) {
      emit('confirmed');
    } else {
      confirmingPassword.value = true;

      nextTick().then(() => passwordInput.value.focus());
    }
  });
};

const confirmPassword = () => {
  form.processing = true;

  axios
    .post(route('password.confirm'), {
      password: form.password,
    })
    .then(() => {
      form.processing = false;

      confirm();
    })
    .catch((error) => {
      form.processing = false;
      form.error = error.response.data.errors.password[0];
      nextTick().then(() => passwordInput.value.focus());
    });
};

const confirm = () => {
  closeModal();
  nextTick().then(() => emit('confirmed'));
};

const closeModal = () => {
  confirmingPassword.value = false;
  form.password = '';
  form.error = '';
};
</script>

<template>
  <span>
    <span @click="startConfirmingPassword">
      <slot />
    </span>

    <DialogModal :show="confirmingPassword" @close="closeModal">
      <template #title>
        {{ title }}
      </template>

      <template #content>
        {{ content }}

        <div v-if="webauthnEnabled" class="mt-4">
          <p>
            {{ $t('When you are ready, authenticate using the button below:') }}
          </p>

          <Button class="mt-2 block" @click.prevent="webauthn.start()">
            {{ $t('Confirm your passkey or security key') }}
          </Button>

          <WebauthnTest ref="webauthn" @success="confirm()" />
        </div>

        <fieldset v-if="webauthnEnabled" class="mt-5 border-t border-gray-300 dark:border-gray-700">
          <legend class="mx-auto px-4 text-l italic text-gray-600 dark:text-gray-200">
            {{ $t('Or') }}
          </legend>
        </fieldset>

        <div class="mt-4">
          {{ $t('Authenticate using your password:') }}
          <Input
            ref="passwordInput"
            v-model="form.password"
            type="password"
            class="mt-1 block w-3/4"
            :placeholder="$t('Password')"
            :autocomplete="'current-password'"
            @keyup.enter="confirmPassword" />

          <InputError :message="form.error" class="mt-2" />
        </div>
      </template>

      <template #footer>
        <SecondaryButton @click="closeModal">
          {{ $t('Cancel') }}
        </SecondaryButton>

        <Button
          class="ms-3"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
          @click="confirmPassword">
          {{ button }}
        </Button>
      </template>
    </DialogModal>
  </span>
</template>
