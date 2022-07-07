<style lang="scss" scoped></style>

<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-slate-200">{{ $t('app.breadcrumb_location') }}</li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings" class="text-blue-500 hover:underline">{{
                $t('app.breadcrumb_settings')
              }}</inertia-link>
            </li>
            <li class="relative mr-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">Notification channels</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-3 mt-8 sm:mt-0">
          <h3 class="mb-4 sm:mb-0"><span class="mr-1">🛰️</span> Configure how we should notify you</h3>
        </div>

        <!-- help text -->
        <div class="mb-6 flex rounded border bg-slate-50 px-3 py-2 text-sm">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 grow pr-2"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>

          <div>
            <p>You can be notified through different channels: emails, a Telegram message, on Facebook. You decide.</p>
          </div>
        </div>

        <!-- Email notifications -->
        <emails :data="data" />
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Errors from '@/Shared/Form/Errors';
import Emails from '@/Pages/Settings/Notifications/Partials/Emails';

export default {
  components: {
    Layout,
    PrettyButton,
    PrettySpan,
    TextInput,
    Errors,
    Emails,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      loadingState: '',
      localEmails: [],
      testEmailSentId: 0,
      form: {
        name: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localEmails = this.data.emails;
  },

  methods: {},
};
</script>
