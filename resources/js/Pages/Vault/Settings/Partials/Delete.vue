<template>
  <div>
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0">
        <span class="mr-1"> 🗑 </span>
        {{ $t('vault.settings_delete_title') }}
      </h3>
    </div>

    <!-- help text -->
    <div class="mb-6 rounded border text-sm">
      <div class="mb-2 flex rounded-t border-b border-gray-200 bg-slate-50 px-3 py-2">
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
          <p>
            {{ $t('vault.settings_delete_description') }}
          </p>
        </div>
      </div>

      <p class="mb-1 px-5 py-2 text-center">
        <pretty-link
          :text="$t('vault.settings_delete_cta')"
          :classes="'mr-3 text-red-600 border-red-600'"
          @click="destroy" />
      </p>
    </div>
  </div>
</template>

<script>
import PrettyLink from '@/Shared/Form/PrettyLink';

export default {
  components: {
    PrettyLink,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      form: {
        errors: [],
      },
    };
  },

  methods: {
    destroy() {
      if (confirm(this.$t('vault.settings_delete_cta_confirmation'))) {
        axios
          .delete(this.data.url.destroy)
          .then((response) => {
            localStorage.success = this.$t('vault.settings_delete_destroy_success');
            this.$inertia.visit(response.data.data);
          })
          .catch((error) => {
            this.form.errors = error.response.data;
          });
      }
    },
  },
};
</script>

<style lang="scss" scoped></style>
