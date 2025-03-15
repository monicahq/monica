<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import JetButton from '@/Components/Button.vue';
import JetGuestLayout from '@/Shared/Guest.vue';

const props = defineProps({
  status: String,
});

const form = useForm({});

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');

const submit = () => {
  form.post(route('verification.send'));
};
</script>

<template>
  <JetGuestLayout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
      {{
        $t(
          'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn’t receive the email, we will gladly send you another.',
        )
      }}
    </div>

    <div v-if="verificationLinkSent" class="mb-4 text-sm font-medium text-green-600">
      {{ $t('A new verification link has been sent to the email address you provided during registration.') }}
    </div>

    <form @submit.prevent="submit">
      <div class="mt-4 flex items-center justify-between">
        <JetButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
          {{ $t('Resend Verification Email') }}
        </JetButton>

        <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
          {{ $t('Log Out') }}
        </Link>
      </div>
    </form>
  </JetGuestLayout>
</template>
