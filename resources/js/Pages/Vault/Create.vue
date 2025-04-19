<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b sm:border-gray-300 dark:border-gray-700">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-center gap-1 text-sm">
          <div class="text-gray-600 dark:text-gray-400">
            {{ $t('You are here:') }}
          </div>
          <div class="inline">
            <Link :href="data.url.back" class="text-blue-500 hover:underline">
              {{ $t('All the vaults') }}
            </Link>
          </div>
          <div class="relative inline">
            <ChevronRight class="h-3 w-3" />
          </div>
          <div class="inline">
            {{ $t('Add a vault') }}
          </div>
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
            <InertiaLink
              :href="data.url.back"
              :text="$t('Cancel')"
              class="cursor-pointer inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1 font-semibold text-gray-700 hover:shadow-xs transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25" />

            <primary-button
              :href="'data.url.vault.create'"
              :text="$t('Create')"
              :state="loadingState"
              :icon="'check'" />
          </div>
        </form>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import PrimaryButton from '@/Shared/Form/PrimaryButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Help from '@/Shared/Help.vue';
import { ChevronRight } from 'lucide-vue-next';

export default {
  components: {
    InertiaLink: Link,
    Layout,
    PrimaryButton,
    TextInput,
    TextArea,
    Help,
    ChevronRight,
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
