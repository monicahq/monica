<style lang="scss" scoped>
pre {
  background-color: #1f2937;
  color: #c9ef78;
}

.example {
  border-bottom-left-radius: 9px;
  border-bottom-right-radius: 9px;
}
</style>

<template>
  <div class="mb-16">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 font-semibold sm:mb-0">
        <span class="mr-1">💵</span> {{ $t('settings.user_preferences_number_format_title') }}
      </h3>
      <pretty-button v-if="!editMode" :text="$t('app.edit')" @click="enableEditMode" />
    </div>

    <!-- normal mode -->
    <div v-if="!editMode" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="px-5 py-2">
        <span class="mb-2 block">{{ $t('settings.user_preferences_number_format_description') }}</span>
        <span class="mb-2 block rounded bg-slate-100 px-5 py-2 text-sm">{{ localNumberFormat }}</span>
      </p>
    </div>

    <!-- edit mode -->
    <form v-if="editMode" class="bg-form mb-6 rounded-lg border border-gray-200" @submit.prevent="submit()">
      <div class="border-b border-gray-200 px-5 py-2">
        <errors :errors="form.errors" />

        <div v-for="numberFormat in data.numbers" :key="numberFormat.id" class="mb-2 flex items-center">
          <input
            :id="'input' + numberFormat.id"
            v-model="form.numberFormat"
            :value="numberFormat.format"
            name="date-format"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500" />
          <label :for="'input' + numberFormat.id" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
            {{ numberFormat.value }}
          </label>
        </div>
      </div>

      <!-- actions -->
      <div class="flex justify-between p-5">
        <pretty-link :text="$t('app.cancel')" :classes="'mr-3'" @click="editMode = false" />
        <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'check'" :classes="'save'" />
      </div>
    </form>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettyLink from '@/Shared/Form/PrettyLink';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    PrettyButton,
    PrettyLink,
    Errors,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      loadingState: '',
      editMode: false,
      localNumberFormat: '',
      form: {
        numberFormat: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localNumberFormat = this.data.number_format;
    this.form.numberFormat = this.data.number_format;
  },

  methods: {
    enableEditMode() {
      this.editMode = true;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('app.notification_flash_changes_saved'), 'success');
          this.localNumberFormat = this.form.numberFormat;
          this.editMode = false;
          this.loadingState = null;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>
