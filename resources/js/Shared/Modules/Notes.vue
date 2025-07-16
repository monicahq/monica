<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <NotebookPen class="h-4 w-4 text-gray-600" />

        <span class="font-semibold"> {{ $t('Notes') }} </span>
      </div>
      <pretty-button :text="$t('Add a note')" :icon="'plus'" :class="'w-full sm:w-fit'" @click="showCreateNoteModal" />
    </div>

    <!-- add a note modal -->
    <form
      v-if="createNoteModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <text-area
          v-model="form.body"
          :label="$t('Body')"
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
          @esc-key-pressed="createNoteModalShown = false" />

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

        <!-- cta to add a title -->
        <span
          v-if="!titleFieldShown"
          class="me-2 inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300 dark:border-gray-700 dark:bg-slate-800 dark:hover:bg-slate-700"
          @click="showTitleField">
          {{ $t('+ add title') }}
        </span>

        <!-- cta to add emotion -->
        <span
          v-if="!emotionFieldShown"
          class="inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300 dark:border-gray-700 dark:bg-slate-800 dark:hover:bg-slate-700"
          @click="showEmotionField">
          {{ $t('+ add emotion') }}
        </span>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createNoteModalShown = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
      </div>
    </form>

    <!-- notes -->
    <div v-if="localNotes.length > 0">
      <div
        v-for="note in localNotes"
        :key="note.id"
        class="mb-4 rounded-xs border border-gray-200 last:mb-0 dark:border-gray-700 dark:bg-gray-900">
        <!-- body of the note, if not being edited -->
        <div v-if="editedNoteId !== note.id">
          <div
            v-if="note.title"
            class="border-b border-gray-200 p-3 text-xs font-semibold text-gray-600 dark:border-gray-700 dark:text-gray-400">
            {{ note.title }}
          </div>

          <!-- excerpt, if it exists -->
          <div v-if="!note.show_full_content && note.body_excerpt" class="p-3">
            {{ note.body_excerpt }}
            <span class="cursor-pointer text-blue-500 hover:underline" @click="showFullBody(note)">
              {{ $t('View all') }}
            </span>
          </div>
          <!-- full body -->
          <div v-else class="p-3 whitespace-pre-line">
            {{ note.body }}
          </div>

          <!-- details -->
          <div
            class="flex justify-between border-t border-gray-200 px-3 py-1 text-xs text-gray-600 hover:rounded-b hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-slate-900">
            <div class="flex items-center gap-4">
              <!-- emotion -->
              <div v-if="note.emotion" class="relative me-3 inline">
                {{ note.emotion.name }}
              </div>

              <!-- date -->
              <div class="relative flex items-center gap-1">
                <CalendarDays class="h-3 w-3" />
                {{ note.written_at }}
              </div>

              <!-- author -->
              <div v-if="note.author" class="relative me-3 inline">
                <div class="icon-avatar flex items-center gap-1">
                  <avatar :data="note.author.avatar" :class="'h-3 w-3 rounded-full'" />
                  <span>{{ note.author.name }}</span>
                </div>
              </div>
            </div>
            <div>
              <hover-menu
                :show-edit="true"
                :show-delete="true"
                @edit="showEditNoteModal(note)"
                @delete="destroy(note)" />
            </div>
          </div>
        </div>

        <!-- edit modal form -->
        <form v-if="editedNoteId === note.id" class="bg-gray-50 dark:bg-gray-900" @submit.prevent="update(note)">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-area
              v-model="form.body"
              :label="$t('Body')"
              :rows="10"
              :required="true"
              :maxlength="65535"
              :markdown="true"
              :textarea-class="'block w-full mb-3'" />

            <!-- title -->
            <text-input
              ref="newTitle"
              v-model="form.title"
              :label="$t('Title')"
              :type="'text'"
              :input-class="'block w-full'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="editedNoteId = 0" />

            <!-- emotion -->
            <div v-if="form.emotion" class="mt-2 block w-full">
              <p class="mb-2">How did you feel?</p>
              <div v-for="emotion in data.emotions" :key="emotion.id" class="mb-2 flex items-center">
                <input
                  :id="emotion.type"
                  v-model="form.emotion"
                  :value="emotion.id"
                  name="emotion"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:text-indigo-400" />
                <label :for="emotion.type" class="ms-2 block font-medium text-gray-700 dark:text-gray-300">
                  {{ emotion.name }}
                </label>
              </div>
            </div>
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editedNoteId = 0" />
            <pretty-button :text="$t('Update')" :state="loadingState" :icon="'check'" :class="'save'" />
          </div>
        </form>
      </div>

      <!-- view all button -->
      <div v-if="moduleMode" class="text-center">
        <InertiaLink
          :href="data.url.index"
          class="rounded-xs border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500 dark:border-gray-700">
          {{ $t('View all') }}
        </InertiaLink>
      </div>

      <!-- pagination -->
      <Pagination v-if="!moduleMode" :items="paginator" />
    </div>

    <!-- blank state -->
    <div
      v-if="localNotes.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_note.svg" :alt="$t('Notes')" class="mx-auto mt-4 h-14 w-14" />
      <p class="px-5 pb-5 pt-2 text-center">{{ $t('There are no notes yet.') }}</p>
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
import { NotebookPen, CalendarDays } from 'lucide-vue-next';

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
    NotebookPen,
    CalendarDays,
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
      createNoteModalShown: false,
      localNotes: [],
      editedNoteId: 0,
      form: {
        title: '',
        body: '',
        emotion: '',
        errors: [],
      },
    };
  },

  created() {
    this.localNotes = this.data.notes;
  },

  methods: {
    showCreateNoteModal() {
      this.form.errors = [];
      this.form.title = '';
      this.form.body = '';
      this.createNoteModalShown = true;
    },

    showEmotionField() {
      this.form.emotion = '';
      this.emotionFieldShown = true;
    },

    showEditNoteModal(note) {
      this.editedNoteId = note.id;
      this.form.title = note.title;
      this.form.body = note.body;
      this.form.emotion = note.emotion?.id;
    },

    showTitleField() {
      this.titleFieldShown = true;
      this.form.title = '';

      this.$nextTick().then(() => {
        this.$refs.newTitle.focus();
      });
    },

    showFullBody(note) {
      this.localNotes[this.localNotes.findIndex((x) => x.id === note.id)].show_full_content = true;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The note has been created'), 'success');
          this.localNotes.unshift(response.data.data);
          this.loadingState = '';
          this.createNoteModalShown = false;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    update(note) {
      this.loadingState = 'loading';

      axios
        .put(note.url.update, this.form)
        .then((response) => {
          this.loadingState = '';
          this.flash(this.$t('The note has been updated'), 'success');
          this.localNotes[this.localNotes.findIndex((x) => x.id === note.id)] = response.data.data;
          this.editedNoteId = 0;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    destroy(note) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .delete(note.url.destroy)
          .then(() => {
            this.flash(this.$t('The note has been deleted'), 'success');
            var id = this.localNotes.findIndex((x) => x.id === note.id);
            this.localNotes.splice(id, 1);
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
