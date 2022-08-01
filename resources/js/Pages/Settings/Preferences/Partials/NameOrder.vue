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
      <h3 class="mb-4 flex font-semibold sm:mb-0">
        <span class="mr-1">👉</span>
        <span class="mr-2">{{ $t('settings.user_preferences_name_order_title') }}</span>

        <help :url="$page.props.help_links.settings_preferences_contact_names" :top="'5px'" />
      </h3>
      <pretty-button v-if="!editMode" :text="$t('app.edit')" @click="enableEditMode" />
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
        <p>
          {{ $t('settings.user_preferences_name_order_description') }}
        </p>
      </div>
    </div>

    <!-- normal mode -->
    <div v-if="!editMode" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="border-b border-gray-200 px-5 py-2">
        <span class="mb-2 block">{{ $t('settings.user_preferences_name_order_current') }}</span>
        <span class="mb-2 block rounded bg-slate-100 px-5 py-2 text-sm">{{ localNameOrder }}</span>
      </p>
      <p class="example bg-orange-50 px-5 py-2 text-sm font-medium">
        <span class="font-light">{{ $t('settings.user_preferences_name_order_example') }}</span> {{ localNameExample }}
      </p>
    </div>

    <!-- edit mode -->
    <form v-if="editMode" class="bg-form mb-6 rounded-lg border border-gray-200" @submit.prevent="submit()">
      <div class="border-b border-gray-200 px-5 py-2">
        <errors :errors="form.errors" />

        <div class="mb-2 flex items-center">
          <input
            id="first_name_last_name"
            v-model="form.nameOrder"
            value="%first_name% %last_name%"
            name="name-order"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500" />
          <label for="first_name_last_name" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
            {{ $t('settings.user_preferences_name_order_first_name_last_name') }}
            <span class="ml-4 font-normal text-gray-500"> James Bond </span>
          </label>
        </div>
        <div class="mb-2 flex items-center">
          <input
            id="last_name_first_name"
            v-model="form.nameOrder"
            value="%last_name% %first_name%"
            name="name-order"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500" />
          <label for="last_name_first_name" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
            {{ $t('settings.user_preferences_name_order_last_name_first_name') }}

            <span class="ml-4 font-normal text-gray-500"> Bond James </span>
          </label>
        </div>
        <div class="mb-2 flex items-center">
          <input
            id="first_name_last_name_nickname"
            v-model="form.nameOrder"
            value="%first_name% %last_name% (%nickname%)"
            name="name-order"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500" />
          <label
            for="first_name_last_name_nickname"
            class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
            {{ $t('settings.user_preferences_name_order_first_name_last_name_nickname') }}
            <span class="ml-4 font-normal text-gray-500"> James Bond (007) </span>
          </label>
        </div>
        <div class="mb-2 flex items-center">
          <input
            id="nickname"
            v-model="form.nameOrder"
            value="%nickname%"
            name="name-order"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500" />
          <label for="nickname" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
            {{ $t('settings.user_preferences_name_order_nickname') }}
            <span class="ml-4 font-normal text-gray-500"> 007 </span>
          </label>
        </div>
        <div class="mb-2 flex items-center">
          <input
            id="custom"
            name="name-order"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500"
            @click="focusNameOrder" />
          <label for="custom" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
            {{ $t('settings.user_preferences_name_order_custom') }}
          </label>
        </div>
        <div class="ml-8">
          <text-input
            :ref="'nameOrder'"
            v-model="form.nameOrder"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :div-outer-class="'block mb-2'"
            :disabled="disableNameOrder"
            :autocomplete="false"
            :maxlength="255" />

          <p class="mb-4 text-sm">
            Please read
            <a
              href="https://www.notion.so/monicahq/Customize-your-account-8e015b7488c143abab9eb8a6e2fbca77#b3fd57def37445f4a9cf234e373c52ca"
              target="_blank"
              class="text-blue-500 hover:underline"
              >our documentation</a
            >
            to know more about this feature, and which variables you have access to.
          </p>
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
import TextInput from '@/Shared/Form/TextInput';
import Errors from '@/Shared/Form/Errors';
import Help from '@/Shared/Help';

export default {
  components: {
    PrettyButton,
    PrettyLink,
    TextInput,
    Errors,
    Help,
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
      localNameOrder: '',
      localNameExample: '',
      disableNameOrder: true,
      form: {
        nameOrder: '',
        choice: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localNameOrder = this.data.name_order;
    this.localNameExample = this.data.name_example;
    this.form.nameOrder = this.data.name_order;
  },

  methods: {
    enableEditMode() {
      this.editMode = true;
    },

    setNameOrder() {
      this.disableNameOrder = true;
      this.form.nameOrder = this.form.choice;
    },

    focusNameOrder() {
      this.disableNameOrder = false;

      this.$nextTick(() => {
        this.$refs.nameOrder.focus();
      });
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('app.notification_flash_changes_saved'), 'success');
          this.localNameOrder = this.form.nameOrder;
          this.localNameExample = response.data.data.name_example;
          this.choice = this.form.nameOrder;
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
