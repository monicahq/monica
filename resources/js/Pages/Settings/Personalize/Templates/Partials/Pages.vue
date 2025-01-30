<template>
  <div>
    <div class="mb-4 mt-8 items-center justify-between border-b pb-3 sm:mt-0 sm:flex">
      <h3>{{ $t('Pages') }}</h3>
      <pretty-button v-if="!createPageModalShown" :text="$t('Add a page')" :icon="'plus'" @click="showPageModal" />
    </div>

    <!-- contact information page | can't be removed -->
    <div
      :class="isSelectedId === data.template_page_contact_information.id ? 'border-2	bg-sky-100' : ''"
      class="mb-2 flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2 hover:cursor-pointer hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-slate-800"
      @click="selectPage(data.template_page_contact_information)">
      <!-- detail of a page -->
      <div>
        <div class="mb-0 block">
          {{ data.template_page_contact_information.name }}
        </div>

        <ul class="text-xs text-gray-400">
          <li class="inline">
            {{ $t('Can’t be moved or deleted') }}
          </li>
        </ul>
      </div>
    </div>

    <!-- list of pages that can be deleted -->
    <draggable
      v-model="localPages"
      class="list-group"
      item-key="id"
      v-bind="dragOptions"
      :component-data="{
        tag: 'div',
        type: 'transition-group',
        name: !drag ? 'flip-list' : null,
      }"
      @start="drag = true"
      @end="drag = false"
      @change="updatePosition">
      <template #item="{ element }">
        <div
          v-if="renamePageModalShownId !== element.id"
          :class="isSelectedId === element.id ? 'border-2	bg-sky-100' : ''"
          class="mb-2 flex items-center rounded-lg hover:cursor-pointer border border-gray-200 bg-white py-2 pe-5 ps-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-slate-800"
          @click="selectPage(element)">
          <!-- icon to move position -->
          <div class="me-2">
            <svg
              class="handle cursor-move"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg">
              <path d="M7 7H9V9H7V7Z" fill="currentColor" />
              <path d="M11 7H13V9H11V7Z" fill="currentColor" />
              <path d="M17 7H15V9H17V7Z" fill="currentColor" />
              <path d="M7 11H9V13H7V11Z" fill="currentColor" />
              <path d="M13 11H11V13H13V11Z" fill="currentColor" />
              <path d="M15 11H17V13H15V11Z" fill="currentColor" />
              <path d="M9 15H7V17H9V15Z" fill="currentColor" />
              <path d="M11 15H13V17H11V15Z" fill="currentColor" />
              <path d="M17 15H15V17H17V15Z" fill="currentColor" />
            </svg>
          </div>

          <!-- detail of a page -->
          <div>
            <div class="mb-0 block">
              {{ element.name }}
            </div>

            <ul class="text-xs">
              <li class="me-4 inline cursor-pointer" @click="renamePageModal(element)">
                <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
              </li>
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(element)">
                {{ $t('Delete') }}
              </li>
            </ul>
          </div>
        </div>

        <!-- modal to edit the page -->
        <form
          v-else
          class="item-list mb-2 rounded-lg border border-gray-200 bg-white hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-slate-800"
          @submit.prevent="update(element)">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              ref="rename"
              v-model="form.name"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="renamePageModalShownId = 0" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="renamePageModalShownId = 0" />
            <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
          </div>
        </form>
      </template>
    </draggable>

    <!-- modal to create a new page -->
    <form
      v-if="createPageModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <text-input
          ref="newPage"
          v-model="form.name"
          :label="$t('Name')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="createPageModalShown = false" />
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createPageModalShown = false" />
        <pretty-button :text="$t('Add')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- blank state -->
    <div v-if="localPages.length === 0">
      <p class="rounded-lg border border-gray-200 bg-white p-5 text-center dark:border-gray-700 dark:bg-gray-900">
        {{ $t('Create at least one page to display contact’s data.') }}
      </p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import draggable from 'vuedraggable';

export default {
  components: {
    PrettyButton,
    PrettySpan,
    TextInput,
    Errors,
    draggable,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  emits: ['pageSelected'],

  data() {
    return {
      drag: false,
      loadingState: '',
      createPageModalShown: false,
      renamePageModalShownId: 0,
      localPages: [],
      isSelectedId: 0,
      form: {
        name: '',
        position: '',
        errors: [],
      },
    };
  },

  computed: {
    dragOptions() {
      return {
        animation: 200,
        group: 'description',
        disabled: false,
        ghostClass: 'ghost',
      };
    },
  },

  mounted() {
    this.localPages = this.data.template_pages;
  },

  methods: {
    showPageModal() {
      this.form.errors = [];
      this.form.name = '';
      this.createPageModalShown = true;

      this.$nextTick().then(() => {
        this.$refs.newPage.focus();
      });
    },

    renamePageModal(page) {
      this.form.name = page.name;
      this.renamePageModalShownId = page.id;

      this.$nextTick().then(() => {
        this.$refs.rename.focus();
      });
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.template_page_store, this.form)
        .then((response) => {
          this.flash(this.$t('The page has been added'), 'success');
          this.localPages.push(response.data.data);
          this.loadingState = null;
          this.createPageModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(page) {
      this.loadingState = 'loading';

      axios
        .put(page.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The page has been updated'), 'success');
          this.localPages[this.localPages.findIndex((x) => x.id === page.id)] = response.data.data;
          this.loadingState = null;
          this.renamePageModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(page) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(page.url.destroy)
          .then(() => {
            this.flash(this.$t('The page has been deleted'), 'success');
            var id = this.localPages.findIndex((x) => x.id === page.id);
            this.localPages.splice(id, 1);
          })
          .catch((error) => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },

    updatePosition(event) {
      // the event object comes from the draggable component
      // no idea why we need to +2 but otherwise it doesn't work
      this.form.position = event.moved.newIndex + 2;

      axios
        .post(event.moved.element.url.order, this.form)
        .then(() => {
          this.flash(this.$t('The position has been saved'), 'success');
        })
        .catch((error) => {
          this.loadingState = null;
          this.errors = error.response.data;
        });
    },

    selectPage(page) {
      this.isSelectedId = page.id;
      this.$emit('pageSelected', page);
    },
  },
};
</script>

<style lang="scss" scoped>
.flip-list-move {
  transition: transform 0.5s;
}
.no-move {
  transition: transform 0s;
}
</style>
