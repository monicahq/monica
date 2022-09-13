<script setup>
import { ref, nextTick, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/inertia-vue3';
import { trans } from 'laravel-vue-i18n';
import JetActionSection from '@/Components/Jetstream/ActionSection.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';
import JetConfirmsPassword from '@/Components/Jetstream/ConfirmsPassword.vue';
import JetButton from '@/Components/Button.vue';
import RegisterKey from '@/Pages/Webauthn/Partials/RegisterKey.vue';
import DeleteKeyModal from '@/Pages/Webauthn/Partials/DeleteKeyModal.vue';
import UpdateKey from '@/Pages/Webauthn/Partials/UpdateKey.vue';
import WebAuthn from '@/webauthn.js';

const props = defineProps({
  webauthnKeys: Array,
  publicKey: Object,
});

const isSupported = ref(true);
const errorMessage = ref('');

const register = ref(false);
const registerForm = useForm({
  name: '',
});
const keyBeingDeleted = ref(null);
const keyBeingUpdated = ref(null);

const nameUpdate = computed(() =>
  keyBeingUpdated.value > 0 ? props.webauthnKeys.find((key) => key.id === keyBeingUpdated.value).name : '',
);

onMounted(() => {
  errorMessage.value = '';

  if (!webauthn.webAuthnSupport()) {
    isSupported.value = false;
    errorMessage.value = notSupportedMessage();
  }

  if (props.publicKey) {
    showRegisterModal();
    registerWaitForKey(props.publicKey);
  }
});

const webauthn = new WebAuthn((name, message) => {
  errorMessage.value = _errorMessage(name, message);
});

const _errorMessage = (name, message) => {
  switch (name) {
    case 'InvalidStateError':
      return trans('This key is already registered. It’s not necessary to register it again.');
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

const showRegisterModal = () => {
  errorMessage.value = '';
  register.value = true;
};

const start = () => {
  errorMessage.value = '';
  registerForm.clearErrors();
};

const registerWaitForKey = (publicKey) => {
  nextTick().then(() =>
    webauthn.register(publicKey, (data) => {
      webauthnRegisterCallback(data);
    }),
  );
};

const webauthnRegisterCallback = (data) => {
  registerForm
    .transform((form) => ({
      ...form,
      ...data,
    }))
    .post(route('webauthn.store'), {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        register.value = false;
        registerForm.reset();
      },
      onError: (error) => {
        errorMessage.value = error.email ? error.email : error.data.errors.webauthn;
      },
    });
};
</script>

<template>
  <JetActionSection>
    <template #title>
      {{ $t('Security keys') }}
    </template>

    <template #description>
      {{ $t('Add additional security to your account using a security key.') }}
    </template>

    <template #content>
      <h3 v-if="keyBeingUpdated > 0" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ $t('Update a key.') }}
      </h3>
      <h3 v-else-if="!register" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ $t('Use a security key (Webauthn, or FIDO) to increase your account security.') }}
      </h3>
      <h3 v-else class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ $t('Register a new key.') }}
      </h3>

      <div v-if="!isSupported">
        {{ notSupportedMessage() }}
      </div>

      <div v-else-if="register">
        <RegisterKey
          :errorMessage="errorMessage"
          :form="registerForm"
          :name="registerForm.name"
          @update:name="registerForm.name = $event"
          @start="start"
          @stop="register = false"
          @register="registerWaitForKey" />
      </div>

      <div v-else-if="keyBeingUpdated > 0">
        <UpdateKey :keyid="keyBeingUpdated" :name-update="nameUpdate" @close="keyBeingUpdated = null" />
      </div>

      <div v-else class="mt-5 space-y-6">
        <div v-if="webauthnKeys.length === 0" class="dark:text-gray-400">
          {{ $t('No keys registered yet') }}
        </div>
        <div v-else v-for="key in webauthnKeys" :key="key.id" class="mb-2 flex items-center">
          <div class="text-gray-500">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              stroke="currentColor"
              class="h-8 w-8"
              viewBox="-50 0 700 600">
              <g transform="matrix(42.857142857142854,0,0,42.857142857142854,0,0)">
                <g>
                  <polyline points="5.62 7.38 11.5 1.5 13.5 3.5"></polyline>
                  <line x1="9.25" y1="3.75" x2="11" y2="5.5"></line>
                  <circle cx="3.5" cy="9.5" r="3"></circle>
                </g>
              </g>
            </svg>
          </div>

          <div class="ml-3 w-48">
            <div class="text-sm text-gray-600 dark:text-gray-400">
              {{ key.name }}
            </div>

            <div class="text-xs text-gray-500">
              <span>
                {{ $t('Last active :date', { date: key.last_active }) }}
              </span>
            </div>
          </div>

          <div class="ml-3 text-sm">
            <JetSecondaryButton
              class="pointer text-indigo-400 hover:text-indigo-600"
              href=""
              @click.prevent="keyBeingUpdated = key.id">
              {{ $t('Update') }}
            </JetSecondaryButton>
            <JetConfirmsPassword @confirmed="keyBeingDeleted = key.id">
              <JetSecondaryButton class="pointer ml-2 text-indigo-400 hover:text-indigo-600" href="">
                {{ $t('Delete') }}
              </JetSecondaryButton>
            </JetConfirmsPassword>
          </div>
        </div>

        <div class="mt-5 flex items-center">
          <JetConfirmsPassword @confirmed="showRegisterModal">
            <JetButton type="button">
              {{ $t('Register a new key') }}
            </JetButton>
          </JetConfirmsPassword>
        </div>
      </div>

      <DeleteKeyModal :keyid="keyBeingDeleted" @close="keyBeingDeleted = null" />
    </template>
  </JetActionSection>
</template>
