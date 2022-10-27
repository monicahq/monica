<template>
  <div class="mb-12">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0">
        <span class="mr-1"> ⚡ </span>
        {{ $t('vault.settings_tags_title') }}
      </h3>
      <pretty-button
        v-if="!createTagModalShown"
        :text="$t('vault.settings_tags_cta')"
        :icon="'plus'"
        @click="showLabelModal" />
    </div>

    <!-- modal to create a new label -->
    <form
      v-if="createTagModalShown"
      class="bg-form mb-6 rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <text-input
          :ref="'newTag'"
          v-model="form.name"
          :label="$t('vault.settings_tags_create_name')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          :div-outer-class="'mb-4'"
          @esc-key-pressed="createTagModalShown = false" />
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="createTagModalShown = false" />
        <pretty-button
          :text="$t('vault.settings_tags_create_cta')"
          :state="loadingState"
          :icon="'plus'"
          :classes="'save'" />
      </div>
    </form>

    <!-- list of tags -->
    <ul
      v-if="localTags.length > 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <li
        v-for="tag in localTags"
        :key="tag.id"
        class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800 hover:dark:bg-slate-900">
        <!-- detail of the tag -->
        <div v-if="editTagModalShownId != tag.id" class="flex items-center justify-between px-5 py-2">
          <span class="flex items-center text-base">
            <span class="mr-2">{{ tag.name }}</span>
            <span v-if="tag.count > 0" class="text-xs text-gray-500"
              >({{ $t('vault.settings_tags_count', { count: tag.count }) }})</span
            >
          </span>

          <!-- actions -->
          <ul class="text-sm">
            <li class="mr-4 inline cursor-pointer text-blue-500 hover:underline" @click="updateTagModal(tag)">
              {{ $t('app.edit') }}
            </li>
            <li
              class="inline cursor-pointer text-red-500 hover:text-red-900 hover:dark:text-red-100"
              @click="destroy(tag)">
              {{ $t('app.delete') }}
            </li>
          </ul>
        </div>

        <!-- edit a tag modal -->
        <form
          v-if="editTagModalShownId == tag.id"
          class="item-list bg-form border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800 hover:dark:bg-slate-900"
          @submit.prevent="update(tag)">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              :ref="'rename' + tag.id"
              v-model="form.name"
              :label="$t('vault.settings_tags_create_name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              :div-outer-class="'mb-4'"
              @esc-key-pressed="editTagModalShownId = 0" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click.prevent="editTagModalShownId = 0" />
            <pretty-button :text="$t('app.rename')" :state="loadingState" :icon="'check'" :classes="'save'" />
          </div>
        </form>
      </li>
    </ul>

    <!-- blank state -->
    <div
      v-if="localTags.length == 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="p-5 text-center">
        {{ $t('vault.settings_tags_blank') }}
      </p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';

export default {
  components: {
    PrettyButton,
    PrettySpan,
    TextInput,
    Errors,
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
      createTagModalShown: false,
      editTagModalShownId: 0,
      localTags: [],
      form: {
        name: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localTags = this.data.tags;
  },

  methods: {
    showLabelModal() {
      this.form.name = '';
      this.createTagModalShown = true;

      this.$nextTick(() => {
        this.$refs.newTag.focus();
      });
    },

    updateTagModal(tag) {
      this.form.name = tag.name;
      this.editTagModalShownId = tag.id;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.tag_store, this.form)
        .then((response) => {
          this.flash(this.$t('vault.settings_tags_create_success'), 'success');
          this.localTags.unshift(response.data.data);
          this.loadingState = null;
          this.createTagModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(tag) {
      this.loadingState = 'loading';

      axios
        .put(tag.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('vault.settings_tags_update_success'), 'success');
          this.localTags[this.localTags.findIndex((x) => x.id === tag.id)] = response.data.data;
          this.loadingState = null;
          this.editTagModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(tag) {
      if (confirm(this.$t('vault.settings_tags_destroy_confirmation'))) {
        axios
          .delete(tag.url.destroy)
          .then(() => {
            this.flash(this.$t('vault.settings_tags_destroy_success'), 'success');
            var id = this.localTags.findIndex((x) => x.id === tag.id);
            this.localTags.splice(id, 1);
          })
          .catch((error) => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
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
