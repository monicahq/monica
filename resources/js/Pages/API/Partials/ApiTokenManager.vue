<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/inertia-vue3';
import JetActionMessage from '@/Components/Jetstream/ActionMessage.vue';
import JetActionSection from '@/Components/Jetstream/ActionSection.vue';
import JetButton from '@/Components/Button.vue';
import JetConfirmationModal from '@/Components/Jetstream/ConfirmationModal.vue';
import JetDangerButton from '@/Components/Jetstream/DangerButton.vue';
import JetDialogModal from '@/Components/Jetstream/DialogModal.vue';
import JetFormSection from '@/Components/Jetstream/FormSection.vue';
import JetInput from '@/Components/Input.vue';
import JetCheckbox from '@/Components/Checkbox.vue';
import JetInputError from '@/Components/InputError.vue';
import JetLabel from '@/Components/Label.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';

const props = defineProps({
  tokens: Array,
  availablePermissions: Array,
  defaultPermissions: Array,
});

const createApiTokenForm = useForm({
  name: '',
  permissions: props.defaultPermissions,
});

const updateApiTokenForm = useForm({
  permissions: [],
});

const deleteApiTokenForm = useForm();

const displayingToken = ref(false);
const managingPermissionsFor = ref(null);
const apiTokenBeingDeleted = ref(null);

const createApiToken = () => {
  createApiTokenForm.post(route('api-tokens.store'), {
    preserveScroll: true,
    onSuccess: () => {
      displayingToken.value = true;
      createApiTokenForm.reset();
    },
  });
};

const manageApiTokenPermissions = (token) => {
  updateApiTokenForm.permissions = token.abilities;
  managingPermissionsFor.value = token;
};

const updateApiToken = () => {
  updateApiTokenForm.put(route('api-tokens.update', managingPermissionsFor.value), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => (managingPermissionsFor.value = null),
  });
};

const confirmApiTokenDeletion = (token) => {
  apiTokenBeingDeleted.value = token;
};

const deleteApiToken = () => {
  deleteApiTokenForm.delete(route('api-tokens.destroy', apiTokenBeingDeleted.value), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => (apiTokenBeingDeleted.value = null),
  });
};
</script>

<template>
  <div>
    <!-- Generate API Token -->
    <JetFormSection @submitted="createApiToken">
      <template #title>
        {{ $t('Create API Token') }}
      </template>

      <template #description>
        {{ $t('API tokens allow third-party services to authenticate with our application on your behalf.') }}
      </template>

      <template #form>
        <!-- Token Name -->
        <div class="col-span-6 mb-4 sm:col-span-4">
          <JetLabel for="name" :value="'Token name (for your reference only)'" />
          <JetInput id="name" v-model="createApiTokenForm.name" type="text" class="mt-1 block w-full" autofocus />
          <JetInputError :message="createApiTokenForm.errors.name" class="mt-2" />
        </div>

        <!-- Token Permissions -->
        <div v-if="availablePermissions.length > 0" class="col-span-6">
          <JetLabel for="permissions" value="Permissions" />

          <div class="mt-2 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div v-for="permission in availablePermissions" :key="permission">
              <label class="flex items-center">
                <JetCheckbox v-model:checked="createApiTokenForm.permissions" :value="permission" />
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                  {{ permission }}
                </span>
              </label>
            </div>
          </div>
        </div>
      </template>

      <template #actions>
        <JetActionMessage :on="createApiTokenForm.recentlySuccessful" class="mr-3">
          {{ $t('Created.') }}
        </JetActionMessage>

        <JetButton :class="{ 'opacity-25': createApiTokenForm.processing }" :disabled="createApiTokenForm.processing">
          {{ $t('Create') }}
        </JetButton>
      </template>
    </JetFormSection>

    <div v-if="tokens.length > 0">
      <!-- Manage API Tokens -->
      <div class="mt-10 sm:mt-0">
        <JetActionSection>
          <template #title>
            {{ $t('Manage API Tokens') }}
          </template>

          <template #description>
            {{ $t('You may delete any of your existing tokens if they are no longer needed.') }}
          </template>

          <!-- API Token List -->
          <template #content>
            <div class="space-y-6">
              <div v-for="token in tokens" :key="token.id" class="flex items-center justify-between">
                <div>
                  {{ token.name }}
                </div>

                <div class="flex items-center">
                  <div v-if="token.last_used_ago" class="text-sm text-gray-400">
                    {{ $t('Last used :date', { date: token.last_used_ago }) }}
                  </div>

                  <button
                    v-if="availablePermissions.length > 0"
                    class="ml-6 cursor-pointer text-sm text-gray-400 underline"
                    @click="manageApiTokenPermissions(token)">
                    {{ $t('Permissions') }}
                  </button>

                  <button class="ml-6 cursor-pointer text-sm text-red-500" @click="confirmApiTokenDeletion(token)">
                    {{ $t('Delete') }}
                  </button>
                </div>
              </div>
            </div>
          </template>
        </JetActionSection>
      </div>
    </div>

    <!-- Token Value Modal -->
    <JetDialogModal :show="displayingToken" @close="displayingToken = false">
      <template #title>
        {{ $t('API Token') }}
      </template>

      <template #content>
        <div>
          {{ $t('Please copy your new API token. For your security, it won’t be shown again.') }}
        </div>

        <div
          v-if="$page.props.jetstream.flash.token"
          class="mt-4 rounded bg-gray-100 px-4 py-2 font-mono text-sm text-gray-500">
          {{ $page.props.jetstream.flash.token }}
        </div>
      </template>

      <template #footer>
        <JetSecondaryButton @click="displayingToken = false">
          {{ $t('Close') }}
        </JetSecondaryButton>
      </template>
    </JetDialogModal>

    <!-- API Token Permissions Modal -->
    <JetDialogModal :show="managingPermissionsFor" @close="managingPermissionsFor = null">
      <template #title>
        {{ $t('API Token Permissions') }}
      </template>

      <template #content>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div v-for="permission in availablePermissions" :key="permission">
            <label class="flex items-center">
              <JetCheckbox v-model:checked="updateApiTokenForm.permissions" :value="permission" />
              <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                {{ permission }}
              </span>
            </label>
          </div>
        </div>
      </template>

      <template #footer>
        <JetSecondaryButton @click="managingPermissionsFor = null">
          {{ $t('Cancel') }}
        </JetSecondaryButton>

        <JetButton
          class="ml-3"
          :class="{ 'opacity-25': updateApiTokenForm.processing }"
          :disabled="updateApiTokenForm.processing"
          @click="updateApiToken">
          {{ $t('Save') }}
        </JetButton>
      </template>
    </JetDialogModal>

    <!-- Delete Token Confirmation Modal -->
    <JetConfirmationModal :show="apiTokenBeingDeleted" @close="apiTokenBeingDeleted = null">
      <template #title>
        {{ $t('Delete API Token') }}
      </template>

      <template #content>
        {{ $t('Are you sure you would like to delete this API token?') }}
      </template>

      <template #footer>
        <JetSecondaryButton @click="apiTokenBeingDeleted = null">
          {{ $t('Cancel') }}
        </JetSecondaryButton>

        <JetDangerButton
          class="ml-3"
          :class="{ 'opacity-25': deleteApiTokenForm.processing }"
          :disabled="deleteApiTokenForm.processing"
          @click="deleteApiToken">
          {{ $t('Delete') }}
        </JetDangerButton>
      </template>
    </JetConfirmationModal>
  </div>
</template>
