<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import JetActionSection from '@/Components/Jetstream/ActionSection.vue';
import JetButton from '@/Components/Button.vue';
import JetConfirmsPassword from '@/Components/Jetstream/ConfirmsPassword.vue';
import JetDangerButton from '@/Components/Jetstream/DangerButton.vue';
import JetInput from '@/Components/Input.vue';
import JetInputError from '@/Components/InputError.vue';
import JetLabel from '@/Components/Label.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';
import JetConfirmationModal from '@/Components/Jetstream/ConfirmationModal.vue';

const props = defineProps({
  requiresConfirmation: Boolean,
});

const enabling = ref(false);
const confirming = ref(false);
const disabling = ref(false);
const qrCode = ref(null);
const setupKey = ref(null);
const recoveryCodes = ref([]);
const confirmDisabling = ref(false);

const confirmationForm = useForm({
  code: '',
});

const twoFactorEnabled = computed(() => !enabling.value && usePage().props.auth.user?.two_factor_enabled);

watch(twoFactorEnabled, () => {
  if (!twoFactorEnabled.value) {
    confirmationForm.reset();
    confirmationForm.clearErrors();
  }
});

const enableTwoFactorAuthentication = () => {
  enabling.value = true;

  router.post(
    route('two-factor.enable'),
    {},
    {
      preserveScroll: true,
      onSuccess: () => Promise.all([showQrCode(), showSetupKey(), showRecoveryCodes()]),
      onFinish: () => {
        enabling.value = false;
        confirming.value = props.requiresConfirmation;
      },
    },
  );
};

const showQrCode = () => {
  return axios.get(route('two-factor.qr-code')).then((response) => {
    qrCode.value = response.data.svg;
  });
};

const showSetupKey = () => {
  return axios.get(route('two-factor.secret-key')).then((response) => {
    setupKey.value = response.data.secretKey;
  });
};

const showRecoveryCodes = () => {
  return axios.get(route('two-factor.recovery-codes')).then((response) => {
    recoveryCodes.value = response.data;
  });
};

const confirmTwoFactorAuthentication = () => {
  confirmationForm.post(route('two-factor.confirm'), {
    errorBag: 'confirmTwoFactorAuthentication',
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      confirming.value = false;
      qrCode.value = null;
      setupKey.value = null;
    },
  });
};

const regenerateRecoveryCodes = () => {
  axios.post(route('two-factor.recovery-codes')).then(() => showRecoveryCodes());
};

const disableTwoFactorAuthentication = () => {
  disabling.value = true;

  router.delete(route('two-factor.disable'), {
    preserveScroll: true,
    onSuccess: () => {
      disabling.value = false;
      confirming.value = false;
    },
    onFinish: () => {
      confirmDisabling.value = false;
    },
  });
};
</script>

<template>
  <JetActionSection>
    <template #title>
      {{ $t('Two Factor Authentication') }}
    </template>

    <template #description>
      {{ $t('Add additional security to your account using two factor authentication.') }}
    </template>

    <template #content>
      <h3 v-if="twoFactorEnabled && !confirming" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ $t('You have enabled two factor authentication.') }}
      </h3>

      <h3 v-else-if="twoFactorEnabled && confirming" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ $t('Finish enabling two factor authentication.') }}
      </h3>

      <h3 v-else class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ $t('You have not enabled two factor authentication.') }}
      </h3>

      <div class="mt-3 max-w-xl text-sm text-gray-600 dark:text-gray-400">
        <p>
          {{
            $t(
              'When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone’s Authenticator application.',
            )
          }}
        </p>
      </div>

      <div v-if="twoFactorEnabled">
        <div v-if="qrCode">
          <div class="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400">
            <p v-if="confirming" class="font-semibold">
              {{
                $t(
                  "To finish enabling two factor authentication, scan the following QR code using your phone's authenticator application or enter the setup key and provide the generated OTP code.",
                )
              }}
            </p>

            <p v-else>
              {{
                $t(
                  'Two factor authentication is now enabled. Scan the following QR code using your phone’s authenticator application or enter the setup key.',
                )
              }}
            </p>
          </div>

          <div class="mt-4" v-html="qrCode" />

          <div class="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400" v-if="setupKey">
            <p class="font-semibold">{{ $t('Setup Key:') }} <span v-html="setupKey"></span></p>
          </div>

          <div v-if="confirming" class="mt-4">
            <JetLabel for="code" :value="$t('Code')" />

            <JetInput
              id="code"
              v-model="confirmationForm.code"
              type="text"
              name="code"
              class="mt-1 block w-1/2"
              inputmode="numeric"
              autofocus
              autocomplete="one-time-code"
              @keyup.enter="confirmTwoFactorAuthentication" />

            <JetInputError :message="confirmationForm.errors.code" class="mt-2" />
          </div>
        </div>

        <div v-if="recoveryCodes.length > 0 && !confirming">
          <div class="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400">
            <p class="font-semibold">
              {{
                $t(
                  'Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.',
                )
              }}
            </p>
          </div>

          <div class="mt-4 grid max-w-xl gap-1 rounded-lg bg-gray-100 px-4 py-4 font-mono text-sm dark:bg-gray-950">
            <div v-for="code in recoveryCodes" :key="code">
              {{ code }}
            </div>
          </div>
        </div>
      </div>

      <div class="mt-5">
        <div v-if="!twoFactorEnabled">
          <JetConfirmsPassword @confirmed="enableTwoFactorAuthentication">
            <JetButton type="button" :class="{ 'opacity-25': enabling }" :disabled="enabling">
              {{ $t('Enable') }}
            </JetButton>
          </JetConfirmsPassword>
        </div>

        <div v-else>
          <JetConfirmsPassword @confirmed="confirmTwoFactorAuthentication">
            <JetButton
              v-if="confirming"
              type="button"
              class="me-3"
              :class="{ 'opacity-25': enabling }"
              :disabled="enabling">
              {{ $t('Confirm') }}
            </JetButton>
          </JetConfirmsPassword>

          <JetConfirmsPassword @confirmed="regenerateRecoveryCodes">
            <JetSecondaryButton v-if="recoveryCodes.length > 0 && !confirming" class="me-3">
              {{ $t('Regenerate Recovery Codes') }}
            </JetSecondaryButton>
          </JetConfirmsPassword>

          <JetConfirmsPassword @confirmed="showRecoveryCodes">
            <JetSecondaryButton v-if="recoveryCodes.length === 0 && !confirming" class="me-3">
              {{ $t('Show Recovery Codes') }}
            </JetSecondaryButton>
          </JetConfirmsPassword>

          <JetConfirmsPassword @confirmed="disableTwoFactorAuthentication">
            <JetSecondaryButton v-if="confirming" :class="{ 'opacity-25': disabling }" :disabled="disabling">
              {{ $t('Cancel') }}
            </JetSecondaryButton>
          </JetConfirmsPassword>

          <JetConfirmsPassword @confirmed="confirmDisabling = true">
            <JetDangerButton v-if="!confirming">
              {{ $t('Disable') }}
            </JetDangerButton>
          </JetConfirmsPassword>

          <JetConfirmationModal :show="confirmDisabling" @close="confirmDisabling = false">
            <template #title>
              {{ $t('Are you sure you want to disable two factor authentication?') }}
            </template>

            <template #content>
              {{
                $t(
                  'Once two factor authentication is disabled, you will no longer be prompted for a secure, random token during authentication.',
                )
              }}
            </template>

            <template #footer>
              <JetSecondaryButton
                @click="confirmDisabling = false"
                :class="{ 'opacity-25': disabling }"
                :disabled="disabling">
                {{ $t('Cancel') }}
              </JetSecondaryButton>
              <JetButton
                class="ms-2"
                :class="{ 'opacity-25': disabling }"
                :disabled="disabling"
                @click="disableTwoFactorAuthentication">
                {{ $t('Confirm') }}
              </JetButton>
            </template>
          </JetConfirmationModal>
        </div>
      </div>
    </template>
  </JetActionSection>
</template>
