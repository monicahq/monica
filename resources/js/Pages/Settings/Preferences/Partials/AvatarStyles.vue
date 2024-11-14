<template>
  <div class="mb-16">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 flex font-semibold sm:mb-0">
        <span class="me-1"> 🎨 </span>
        <span class="me-2">
          {{ $t('Avatar Style') }}
        </span>
        <!-- TODO: add documentation info -->
        <!-- <help :url="$page.props.help_links.settings_preferences_help" :top="'5px'" /> -->
      </h3>
      <pretty-button v-if="!editMode" :text="$t('Edit')" @click="enableEditMode" />
    </div>

    <!-- normal mode -->
    <div v-if="!editMode" class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="px-5 py-2">
        <span class="mb-2 block">{{ $t('Current avatar style:') }}</span>
        <span class="mb-2 block rounded bg-slate-100 px-5 py-2 text-sm dark:bg-slate-900">{{
          form.avatarStyle ?? $t('Default')
        }}</span>
      </p>
    </div>

    <!-- edit mode -->
    <form
      v-if="editMode"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 px-5 py-2 dark:border-gray-700">
        <errors :errors="form.errors" />

        <!-- TODO: add dropdown that matches existing styles -->
        <select
          v-model="form.avatarStyle"
          name="timezone"
          class="rounded-md border-gray-300 bg-white px-3 py-2 pe-5 ps-2 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 ltr:bg-[right_3px_center] rtl:bg-[left_3px_center] dark:bg-gray-900 sm:text-sm">
          <option :value="null">Default</option>
          <option v-for="style in avatarStyles" :key="style" :value="style">{{ style }}</option>
        </select>

        <!-- TODO: add svg below dropdown showing preview of style -->
        <div class="mt-5 mb-2">
          <avatar :data="avatarData" :class="'h-16 w-16 rounded-full ring-2 ring-white dark:ring-gray-900'" />
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
import Avatar from '@/Shared/Avatar.vue';

export default {
  components: {
    PrettyButton,
    PrettyLink,
    Avatar,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      avatarStyles: [
        'adventurer',
        'adventurer-neutral',
        'avataaars',
        'avataaars-neutral', // TODO: add all current styles
      ], // Dicebear does not have an endpoint that lists all styles
      loadingState: '',
      editMode: false,
      form: {
        avatarStyle: null,
        errors: [],
      },
    };
  },

  computed: {
    avatarData() {
      const dicebearAvatar = {
        type: 'dicebear',
        content: `https://api.dicebear.com/9.x/${this.form.avatarStyle}/svg?seed=${this.$page.props.auth.user?.name}`,
      };
      return this.form.avatarStyle ? dicebearAvatar : this.data.default_avatar;
    },
  },

  mounted() {
    this.form.avatarStyle = this.data.style;
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

          this.avatarStyle = response.data.data.style;
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

<style scoped></style>
