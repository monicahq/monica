<script setup>
import { ref, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import JetActionSection from '@/Components/Jetstream/ActionSection.vue';
import JetDialogModal from '@/Components/Jetstream/DialogModal.vue';
import JetDangerButton from '@/Components/Jetstream/DangerButton.vue';
import JetInput from '@/Components/Input.vue';
import JetInputError from '@/Components/InputError.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = useTemplateRef('passwordInput');

const form = useForm({
  password: '',
});

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true;

  nextTick().then(() => passwordInput.value.focus());
};

const deleteUser = () => {
  form.delete(route('current-user.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => nextTick().then(() => passwordInput.value.focus()),
    onFinish: () => form.reset(),
  });
};

const closeModal = () => {
  confirmingUserDeletion.value = false;

  form.reset();
};
</script>

<template>
  <JetActionSection :danger="true">
    <template #title>
      {{ $t('Delete Account') }}
    </template>

    <template #description>
      {{ $t('Permanently delete your account.') }}
    </template>

    <template #content>
      <div class="max-w-xl text-sm text-gray-600 dark:text-gray-200">
        <p class="mb-4 text-center text-xl font-semibold text-red-500">{{ $t('⚠️ Danger zone') }}</p>

        <p>
          {{
            $t(
              'Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.',
            )
          }}
        </p>

        <p class="pt-5">{{ $t('This will immediately:') }}</p>
        <ul class="mb-2 ms-5">
          <li>- {{ $t('Cancel all your active subscriptions') }},</li>
          <li>- {{ $t('Delete your account on https://customers.monicahq.com.') }}</li>
        </ul>

        <p class="font-semibold text-red-500">
          {{ $t('You WILL still have to delete your account on Monica or OfficeLife.') }}
        </p>
      </div>

      <div class="mt-5">
        <JetDangerButton @click="confirmUserDeletion">
          {{ $t('Delete Account') }}
        </JetDangerButton>
      </div>

      <!-- Delete Account Confirmation Modal -->
      <JetDialogModal :show="confirmingUserDeletion" @close="closeModal">
        <template #title>
          {{ $t('Delete Account') }}
        </template>

        <template #content>
          {{
            $t(
              'Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
            )
          }}

          <div class="mt-4">
            <JetInput
              ref="passwordInput"
              v-model="form.password"
              type="password"
              class="mt-1 block w-3/4"
              :placeholder="$t('Password')"
              @keyup.enter="deleteUser" />

            <JetInputError :message="form.errors.password" class="mt-2" />
          </div>
        </template>

        <template #footer>
          <JetSecondaryButton @click="closeModal">
            {{ $t('Cancel') }}
          </JetSecondaryButton>

          <JetDangerButton
            class="ms-3"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
            @click="deleteUser">
            {{ $t('Delete Account') }}
          </JetDangerButton>
        </template>
      </JetDialogModal>
    </template>
  </JetActionSection>
</template>
