<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b dark:sm:border-black">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <InertiaLink :href="data.url.back" class="text-blue-500 hover:underline">
                {{ $t('All the vaults') }}
              </InertiaLink>
            </li>
            <li class="relative me-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3 dark:text-slate-200"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline dark:text-slate-200">
              {{ $t('Add a vault') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative mt-16 sm:mt-24">
      <div class="mx-auto max-w-lg px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <form
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
          @submit.prevent="submit()">
          <div
            class="section-head border-b border-gray-200 bg-blue-50 p-3 dark:border-gray-700 dark:bg-blue-900 sm:p-5">
            <h1 class="mb-1 flex justify-center text-2xl font-medium">
              <span>{{ $t('Create a vault') }}</span>

              <help :url="$page.props.help_links.vault_create" :top="'9px'" :class="'ms-2'" />
            </h1>
            <p class="text-center text-sm">
              {{ $t('Vaults contain all your contacts data.') }}
            </p>
          </div>
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <text-input
              ref="name"
              v-model="form.name"
              :autofocus="true"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="true"
              :maxlength="255"
              :label="$t('Name')" />
            <text-area
              v-model="form.description"
              :label="$t('Description')"
              :maxlength="255"
              :textarea-class="'block w-full'" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-link :href="data.url.back" :text="$t('Cancel')" :class="'me-3'" />
            <pretty-button
              :href="'data.url.vault.create'"
              :text="$t('Add')"
              :state="loadingState"
              :icon="'check'"
              :class="'save'" />
          </div>
        </form>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Help from '@/Shared/Help.vue';

export default {
  components: {
    InertiaLink: Link,
    Layout,
    PrettyLink,
    PrettyButton,
    TextInput,
    TextArea,
    Help,
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
      form: {
        name: '',
        description: '',
      },
    };
  },

  mounted() {
    this.$nextTick().then(() => {
      this.$refs.name.focus();
    });
  },

  methods: {
    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          localStorage.success = this.$t('The vault has been created');
          this.$inertia.visit(response.data.data);
        })
        .catch(() => {
          this.loadingState = null;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>
