<template>
  <div class="mb-16">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 flex font-semibold sm:mb-0">
        <span class="me-1"> 🗺️ </span>
        <span class="me-2">
          {{ $t('What should we use to display maps?') }}
        </span>

        <help :url="$page.props.help_links.settings_preferences_maps" :top="'5px'" />
      </h3>
      <pretty-button v-if="!editMode" :text="$t('Edit')" @click="enableEditMode" />
    </div>

    <!-- normal mode -->
    <div v-if="!editMode" class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="px-5 py-2">
        <span class="mb-2 block">{{ $t('Current site used to display maps:') }}</span>
        <span class="mb-2 block rounded-xs bg-slate-100 px-5 py-2 text-sm dark:bg-slate-900">{{ currentMap }}</span>
      </p>
    </div>

    <!-- edit mode -->
    <form
      v-if="editMode"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 px-5 py-2 dark:border-gray-700">
        <errors :errors="form.errors" />

        <div v-for="mapType in data.types" :key="mapType.id" class="relative mb-2 flex">
          <input
            :id="'input' + mapType.id"
            v-model="form.value"
            :value="mapType.value"
            name="date-format"
            type="radio"
            class="relative me-3 h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
          <div>
            <label
              :for="'input' + mapType.id"
              class="block cursor-pointer font-medium text-gray-700 dark:text-gray-300">
              {{ mapType.type }}
            </label>
            <p class="text-sm text-gray-700 dark:text-gray-300">
              {{ mapType.description }}
            </p>
          </div>
        </div>
      </div>

      <!-- actions -->
      <div class="flex justify-between p-5">
        <pretty-link :text="$t('Cancel')" :class="'me-3'" @click="editMode = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
      </div>
    </form>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Help from '@/Shared/Help.vue';

export default {
  components: {
    PrettyButton,
    PrettyLink,
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
      currentMap: '',
      form: {
        value: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.currentMap = this.data.default_map_site_i18n;
    this.form.value = this.data.default_map_site;
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
          this.flash(this.$t('Changes saved'), 'success');
          this.currentMap = response.data.data.default_map_site_i18n;
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

<style lang="scss" scoped>
input[type='radio'] {
  top: 6px;
}
</style>
