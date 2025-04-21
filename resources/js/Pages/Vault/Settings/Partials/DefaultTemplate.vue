<template>
  <div class="mb-12">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0">
        <span class="me-1"> 📐 </span>
        {{ $t('Default template to display contacts') }}
      </h3>
    </div>

    <div class="mb-6 rounded-xs border border-gray-200 text-sm dark:border-gray-700">
      <!-- help text -->
      <div class="flex rounded-t border-b border-gray-200 bg-slate-50 px-3 py-2 dark:border-gray-700 dark:bg-slate-900">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-6 grow pe-2"
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
            {{
              $t(
                'A template defines how contacts should be displayed. You can have as many templates as you want - they are defined in your Account settings. However, you might want to define a default template so all your contacts in this vault have this template by default.',
              )
            }}
          </p>
        </div>
      </div>

      <!-- list of templates -->
      <ul v-if="localTemplates.length > 0" class="rounded-b bg-white dark:bg-gray-900">
        <li
          v-for="template in localTemplates"
          :key="template.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <div class="flex items-center justify-between px-5 py-2">
            <span>{{ template.name }}</span>

            <!-- actions -->
            <ul class="text-sm">
              <li v-if="template.is_default">
                {{ $t('Current default') }}
              </li>
              <li v-else class="inline cursor-pointer text-blue-500 hover:underline" @click="update(template)">
                {{ $t('Set as default') }}
              </li>
            </ul>
          </div>
        </li>
      </ul>

      <!-- blank state -->
      <div v-if="localTemplates.length === 0">
        <p class="p-5 text-center">
          {{ $t('There are no templates in the account. Go to the account settings to create one.') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      localTemplates: [],
      form: {
        template_id: 0,
        errors: [],
      },
    };
  },

  mounted() {
    this.localTemplates = this.data.templates;
  },

  methods: {
    update(template) {
      this.form.template_id = template.id;

      axios
        .put(this.data.url.template_update, this.form)
        .then(() => {
          this.flash(this.$t('The vault has been updated'), 'success');

          // mark the previous default template as not default
          this.localTemplates.forEach((row) => {
            if (row.is_default === true) {
              row.is_default = false;
            }
          });

          // mark the new default template as default
          this.localTemplates[this.localTemplates.findIndex((x) => x.id === this.form.template_id)].is_default = true;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
