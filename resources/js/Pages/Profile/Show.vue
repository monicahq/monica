<script setup>
import { computed } from 'vue';
import size from 'lodash/size';
import Layout from '@/Layouts/Layout.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import UpdateProviders from '@/Pages/Profile/Partials/UpdateProviders.vue';
import WebauthnKeys from '@/Pages/Webauthn/WebauthnKeys.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
  confirmsTwoFactorAuthentication: Boolean,
  sessions: Array,
  providers: Object,
  userTokens: Array,
  locales: Array,
  webauthnKeys: Array,
  layoutData: Object,
});

const providersExists = computed(() => size(props.providers) > 0);
</script>

<template>
  <Layout :layout-data="layoutData">
    <template #header>
      <Breadcrumb
        :items="[
          {
            name: $t('Settings'),
            url: route('settings.index'),
          },
          {
            name: $t('Account and security'),
          },
        ]" />
    </template>

    <div class="relative">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="mb-16" v-if="$page.props.jetstream.canUpdateProfileInformation">
          <UpdateProfileInformationForm :user="$page.props.auth.user" />
        </div>

        <div class="mb-16" v-if="$page.props.jetstream.canUpdatePassword">
          <UpdatePasswordForm class="mt-10 sm:mt-0" />
        </div>

        <div class="mb-16" v-if="providersExists">
          <UpdateProviders :providers="providers" :user-tokens="userTokens" />
        </div>

        <div class="mb-16" v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
          <TwoFactorAuthenticationForm :requires-confirmation="confirmsTwoFactorAuthentication" class="mt-10 sm:mt-0" />
        </div>

        <div class="mb-16">
          <WebauthnKeys :webauthn-keys="webauthnKeys" />
        </div>

        <div class="mb-16">
          <LogoutOtherBrowserSessionsForm :sessions="sessions" class="mt-10 sm:mt-0" />
        </div>

        <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
          <DeleteUserForm class="mt-10 sm:mt-0" />
        </template>
      </div>
    </div>
  </Layout>
</template>
