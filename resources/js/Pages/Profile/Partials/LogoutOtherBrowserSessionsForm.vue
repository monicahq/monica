<script setup>
import { nextTick, ref, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import JetActionMessage from '@/Components/Jetstream/ActionMessage.vue';
import JetActionSection from '@/Components/Jetstream/ActionSection.vue';
import JetButton from '@/Components/Button.vue';
import JetDialogModal from '@/Components/Jetstream/DialogModal.vue';
import JetInput from '@/Components/Input.vue';
import JetInputError from '@/Components/InputError.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';
import { Computer, TabletSmartphone } from 'lucide-vue-next';

defineProps({
  sessions: Array,
});

const confirmingLogout = ref(false);
const passwordInput = useTemplateRef('passwordInput');

const form = useForm({
  password: '',
});

const confirmLogout = () => {
  confirmingLogout.value = true;

  nextTick().then(() => passwordInput.value.focus());
};

const logoutOtherBrowserSessions = () => {
  form.delete(route('other-browser-sessions.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => nextTick().then(() => passwordInput.value.focus()),
    onFinish: () => form.reset(),
  });
};

const closeModal = () => {
  confirmingLogout.value = false;

  form.reset();
};
</script>

<template>
  <JetActionSection>
    <template #title>
      {{ $t('Browser Sessions') }}
    </template>

    <template #description>
      {{ $t('Manage and log out your active sessions on other browsers and devices.') }}
    </template>

    <template #content>
      <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
        {{
          $t(
            'If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.',
          )
        }}
      </div>

      <!-- Other Browser Sessions -->
      <div v-if="sessions.length > 0" class="mt-5 space-y-6">
        <div v-for="(session, i) in sessions" :key="i" class="flex items-center">
          <div>
            <Computer v-if="session.agent.is_desktop" class="h-8 w-8 text-gray-500" />
            <TabletSmartphone v-else class="h-8 w-8 text-gray-500" />
          </div>

          <div class="ms-3">
            <div class="text-sm text-gray-600 dark:text-gray-400">
              {{ session.agent.platform ? session.agent.platform : $t('Unknown') }} -
              {{ session.agent.browser ? session.agent.browser : $t('Unknown') }}
            </div>

            <div>
              <div class="text-xs text-gray-500">
                {{ session.ip_address }},

                <span v-if="session.is_current_device" class="font-semibold text-green-500">
                  {{ $t('This device') }}
                </span>
                <span v-else>
                  {{ $t('Last active :date', { date: session.last_active }) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-5 flex items-center">
        <JetButton @click="confirmLogout">
          {{ $t('Log Out Other Browser Sessions') }}
        </JetButton>

        <JetActionMessage :on="form.recentlySuccessful" class="ms-3">
          {{ $t('Done.') }}
        </JetActionMessage>
      </div>

      <!-- Log Out Other Devices Confirmation Modal -->
      <JetDialogModal :show="confirmingLogout" @close="closeModal">
        <template #title>
          {{ $t('Log Out Other Browser Sessions') }}
        </template>

        <template #content>
          {{
            $t(
              'Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.',
            )
          }}

          <div class="mt-4">
            <JetInput
              ref="passwordInput"
              v-model="form.password"
              type="password"
              class="mt-1 block w-3/4"
              :placeholder="$t('Password')"
              @keyup.enter="logoutOtherBrowserSessions" />

            <JetInputError :message="form.errors.password" class="mt-2" />
          </div>
        </template>

        <template #footer>
          <JetSecondaryButton @click="closeModal">
            {{ $t('Cancel') }}
          </JetSecondaryButton>

          <JetButton
            class="ms-3"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
            @click="logoutOtherBrowserSessions">
            {{ $t('Log Out Other Browser Sessions') }}
          </JetButton>
        </template>
      </JetDialogModal>
    </template>
  </JetActionSection>
</template>
