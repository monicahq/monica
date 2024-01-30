<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative me-1">
          <svg
            class="icon-sidebar relative inline h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
              d="M6 6C6 5.44772 6.44772 5 7 5H17C17.5523 5 18 5.44772 18 6C18 6.55228 17.5523 7 17 7H7C6.44771 7 6 6.55228 6 6Z"
              fill="currentColor" />
            <path
              d="M6 10C6 9.44771 6.44772 9 7 9H17C17.5523 9 18 9.44771 18 10C18 10.5523 17.5523 11 17 11H7C6.44771 11 6 10.5523 6 10Z"
              fill="currentColor" />
            <path
              d="M7 13C6.44772 13 6 13.4477 6 14C6 14.5523 6.44771 15 7 15H17C17.5523 15 18 14.5523 18 14C18 13.4477 17.5523 13 17 13H7Z"
              fill="currentColor" />
            <path
              d="M6 18C6 17.4477 6.44772 17 7 17H11C11.5523 17 12 17.4477 12 18C12 18.5523 11.5523 19 11 19H7C6.44772 19 6 18.5523 6 18Z"
              fill="currentColor" />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M2 4C2 2.34315 3.34315 1 5 1H19C20.6569 1 22 2.34315 22 4V20C22 21.6569 20.6569 23 19 23H5C3.34315 23 2 21.6569 2 20V4ZM5 3H19C19.5523 3 20 3.44771 20 4V20C20 20.5523 19.5523 21 19 21H5C4.44772 21 4 20.5523 4 20V4C4 3.44772 4.44771 3 5 3Z"
              fill="currentColor" />
          </svg>
        </span>

        <span class="font-semibold"> {{ $t('Gifts') }} </span>
      </div>
      <pretty-button :text="$t('Add a gift')" :icon="'plus'" :class="'w-full sm:w-fit'" @click="showCreateGiftModal" />
    </div>

    <!-- add a note modal -->
    <form
      v-if="createGiftModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />
        <!-- type options -->
        <div class="border-b border-gray-200 px-5 pb-3 pt-5 dark:border-gray-700">
          <ul>
            <li class="me-5 inline-block">
              <div class="flex items-center">
                <input
                  id="object"
                  v-model="form.type"
                  value="object"
                  name="name-order"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                <label
                  for="object"
                  class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t('Physical gifts') }}
                </label>
              </div>
            </li>

            <li class="me-5 inline-block">
              <div class="flex items-center">
                <input
                  id="monetary"
                  v-model="form.type"
                  value="monetary"
                  name="name-order"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                <label
                  for="monetary"
                  class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t('Personalized gifts') }}
                </label>
              </div>
            </li>

            <li class="me-5 inline-block">
              <div class="flex items-center">
                <input
                  id="object2"
                  v-model="form.type"
                  value="object2"
                  name="name-order"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                <label
                  for="monetary"
                  class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t('DIY gifts') }}
                </label>
              </div>
            </li>
          </ul>
        </div>
        <!-- title -->
        <text-input
          ref="labelInput"
          v-model="form.name"
          :label="$t('Name')"
          :type="'text'"
          :input-class="'block w-full mb-3'"
          :required="true"
          :autocomplete="false"
          :maxlength="255" />

        <text-area
          v-model="form.description"
          :label="$t('Description')"
          :rows="10"
          :required="true"
          :maxlength="65535"
          :markdown="true"
          :textarea-class="'block w-full mb-3'" />

        <!-- title -->
        <text-input
          v-if="titleFieldShown"
          ref="newTitle"
          v-model="form.title"
          :label="$t('Title')"
          :type="'text'"
          :input-class="'block w-full mb-3'"
          :required="false"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="createGiftModalShown = false" />

        <!-- emotion -->
        <div v-if="emotionFieldShown" class="mt-2 block w-full">
          <p class="mb-2">{{ $t('How did you feel?') }}</p>
          <div v-for="emotion in data.emotions" :key="emotion.id" class="mb-2 flex items-center">
            <input
              :id="emotion.type"
              v-model="form.emotion"
              :value="emotion.id"
              name="emotion"
              type="radio"
              class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700 dark:bg-slate-900 dark:text-indigo-400" />
            <label :for="emotion.type" class="ms-2 block font-medium text-gray-700 dark:text-gray-300">
              {{ emotion.name }}
            </label>
          </div>
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createGiftModalShown = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
      </div>
    </form>

    <!-- notes -->
    <div v-if="localGifts.length > 0">
      <div
        v-for="gift in localGifts"
        :key="gift.id"
        class="mb-4 rounded border border-gray-200 last:mb-0 dark:border-gray-700 dark:bg-gray-900">
        <!-- body of the note, if not being edited -->
        <div v-if="editedGiftId !== gift.id">
          <div
            v-if="gift.title"
            class="border-b border-gray-200 p-3 text-xs font-semibold text-gray-600 dark:border-gray-700 dark:text-gray-400">
            {{ gift.title }}
          </div>

          <!-- excerpt, if it exists -->
          <div v-if="!gift.show_full_content && gift.body_excerpt" class="p-3">
            {{ gift.body_excerpt }}
            <span class="cursor-pointer text-blue-500 hover:underline" @click="showFullBody(gift)">
              {{ $t('View all') }}
            </span>
          </div>
          <!-- full body -->
          <div v-else class="p-3 whitespace-pre-line">
            {{ gift.description }}
          </div>

          <!-- details -->
          <div
            class="flex justify-between border-t border-gray-200 px-3 py-1 text-xs text-gray-600 hover:rounded-b hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400 hover:dark:bg-slate-900">
            <div class="flex items-center">
              <!-- emotion -->
              <div v-if="gift.emotion" class="relative me-3 inline">
                {{ gift.emotion.name }}
              </div>

              <!-- date -->
              <div class="relative me-3 inline">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="icon-note relative inline h-3 w-3 text-gray-400"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ gift.written_at }}
              </div>

              <!-- author -->
              <div v-if="gift.author" class="relative me-3 inline">
                <div class="icon-avatar relative flex">
                  <avatar :data="gift.author.avatar" :class="'relative me-1 h-3 w-3 rounded-full'" />
                  <span>{{ gift.author.name }}</span>
                </div>
              </div>
            </div>
            <div>
              <hover-menu
                :show-edit="true"
                :show-delete="true"
                @edit="showEditGiftModal(gift)"
                @delete="destroy(gift)" />
            </div>
          </div>
        </div>

        <!-- edit modal form -->
        <form v-if="editedGiftId === gift.id" class="bg-gray-50 dark:bg-gray-900" @submit.prevent="update(gift)">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />
            <!-- type options -->
            <div class="border-b border-gray-200 px-5 pb-3 pt-5 dark:border-gray-700">
              <ul>
                <li class="me-5 inline-block">
                  <div class="flex items-center">
                    <input
                      id="object"
                      v-model="form.type"
                      value="object"
                      name="name-order"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                    <label
                      for="object"
                      class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('Physical gifts') }}
                    </label>
                  </div>
                </li>

                <li class="me-5 inline-block">
                  <div class="flex items-center">
                    <input
                      id="monetary"
                      v-model="form.type"
                      value="monetary"
                      name="name-order"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                    <label
                      for="monetary"
                      class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('Personalized gifts') }}
                    </label>
                  </div>
                </li>

                <li class="me-5 inline-block">
                  <div class="flex items-center">
                    <input
                      id="object2"
                      v-model="form.type"
                      value="object2"
                      name="name-order"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                    <label
                      for="object2"
                      class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('DIY gifts') }}
                    </label>
                  </div>
                </li>
              </ul>
            </div>
            <text-input
              ref="labelInput"
              v-model="form.name"
              :label="$t('Name')"
              :type="'text'"
              :input-class="'block w-full mb-3'"
              :required="true"
              :autocomplete="false"
              :maxlength="255" />

            <text-area
              v-model="form.description"
              :label="$t('Description')"
              :rows="10"
              :required="true"
              :maxlength="65535"
              :markdown="true"
              :textarea-class="'block w-full mb-3'" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editedGiftId = 0" />
            <pretty-button :text="$t('Update')" :state="loadingState" :icon="'check'" :class="'save'" />
          </div>
        </form>
      </div>

      <!-- view all button -->
      <div v-if="moduleMode" class="text-center">
        <InertiaLink
          :href="data.url.index"
          class="rounded border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500 dark:border-gray-700">
          {{ $t('View all') }}
        </InertiaLink>
      </div>

      <!-- pagination -->
      <Pagination v-if="!moduleMode" :items="paginator" />
    </div>

    <!-- blank state -->
    <div
      v-if="localGifts.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_note.svg" :alt="$t('Gifts')" class="mx-auto mt-4 h-14 w-14" />
      <p class="px-5 pb-5 pt-2 text-center">{{ $t('There are no gifts yet.') }}</p>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import HoverMenu from '@/Shared/HoverMenu.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Avatar from '@/Shared/Avatar.vue';
import Pagination from '@/Components/Pagination.vue';

export default {
  components: {
    InertiaLink: Link,
    HoverMenu,
    PrettyButton,
    PrettySpan,
    TextInput,
    TextArea,
    Errors,
    Avatar,
    Pagination,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
    paginator: {
      type: Object,
      default: null,
    },
    moduleMode: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      loadingState: '',
      titleFieldShown: false,
      emotionFieldShown: false,
      createGiftModalShown: false,
      localGifts: [],
      editedGiftId: 0,
      form: {
        type: 'object',
        name: '',
        description: '',
        errors: [],
      },
    };
  },

  created() {
    this.localGifts = this.data.gifts;
  },

  methods: {
    showCreateGiftModal() {
      this.form.errors = [];
      (this.form.type = 'object'), (this.form.name = '');
      this.form.description = '';
      this.createGiftModalShown = true;
    },

    showEditGiftModal(gift) {
      this.editedGiftId = gift.id;
      this.form.type = gift.type;
      this.form.name = gift.name;
      this.form.description = gift.description;
    },

    showFullBody(gift) {
      this.localGifts[this.localGifts.findIndex((x) => x.id === gift.id)].show_full_content = true;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The gift has been created'), 'success');
          this.localGifts.unshift(response.data.data);
          this.loadingState = '';
          this.createGiftModalShown = false;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    update(gift) {
      this.loadingState = 'loading';

      axios
        .put(gift.url.update, this.form)
        .then((response) => {
          this.loadingState = '';
          this.flash(this.$t('The gift has been edited'), 'success');
          this.localGifts[this.localGifts.findIndex((x) => x.id === gift.id)] = response.data.data;
          this.editedGiftId = 0;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    destroy(gift) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(gift.url.destroy)
          .then(() => {
            this.flash(this.$t('The gift has been deleted'), 'success');
            var id = this.localGifts.findIndex((x) => x.id === gift.id);
            this.localGifts.splice(id, 1);
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
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

.icon-note {
  top: -1px;
}

.icon-avatar .img {
  top: 2px;
}
</style>
