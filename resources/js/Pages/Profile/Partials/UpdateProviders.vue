<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import JetButton from '@/Components/Button.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';
import JetActionSection from '@/Components/Jetstream/ActionSection.vue';
import JetInputError from '@/Components/InputError.vue';

const form = useForm({});
const providerForm = useForm({});
const errors = computed(() => usePage().props.errors);

const deleteProvider = (provider) => {
  form.delete(route('provider.delete', { driver: provider }), {
    errorBag: 'deleteProvider',
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
};

const open = (provider) => {
  providerForm
    .transform(() => ({
      redirect: location.href,
    }))
    .get(route('login.provider', { driver: provider }), {
      preserveScroll: true,
      onFinish: () => {
        providerForm.reset();
      },
    });
};

defineProps({
  providers: Object,
  userTokens: Array,
});
</script>

<template>
  <JetActionSection id="socialite">
    <template #title>
      {{ $t('External connections') }}
    </template>

    <template #description>
      {{ $t('Manage accounts you have linked to your Customers account.') }}
    </template>

    <template #content>
      <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
        {{ $t('You can add more account to log in to our service with one click.') }}
      </div>

      <div class="mt-5 space-y-6">
        <div v-for="(provider, id) in providers" :key="id" class="flex items-center">
          <img :src="provider.logo" :alt="provider.name" class="relative top-0.5 me-2 h-[15px] w-[15px]" />
          <span class="me-3 text-sm text-gray-600 dark:text-gray-400">
            {{ provider.name }}
          </span>

          <template v-if="userTokens.findIndex((driver) => driver.driver === id) > -1">
            <span class="text-sm text-green-600 dark:text-green-400">
              {{ $t('Connected') }}
            </span>
            <span v-if="provider.email" class="text-sm"> ({{ provider.email }}) </span>
            <JetSecondaryButton class="ms-3" @click.prevent="deleteProvider(id)">
              {{ $t('Disconnect') }}
            </JetSecondaryButton>

            <JetInputError :message="form.errors[id]" class="mt-4" />
          </template>

          <template v-else>
            <JetButton class="ms-3" @click.prevent="open(id)">
              {{ $t('Connect') }}
            </JetButton>

            <JetInputError v-if="errors[id]" :message="errors[id]" class="mt-4" />
          </template>
        </div>
      </div>
    </template>
  </JetActionSection>
</template>
