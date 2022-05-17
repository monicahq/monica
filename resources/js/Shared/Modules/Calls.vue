<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

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

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative mr-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-sidebar relative inline h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
          </svg>
        </span>

        <span class="font-semibold">Calls</span>
      </div>
      <pretty-span @click="showCreateCallModal()" :text="'Log a call'" :icon="'plus'" :classes="'sm:w-fit w-full'" />
    </div>

    <!-- add a call modal -->
    <form v-if="createCallModalShown" class="bg-form mb-6 rounded-lg border border-gray-200" @submit.prevent="submit()">
      <div>
        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <!-- date -->
        <div class="flex border-b border-gray-200">
          <div class="p-5">
            <p class="mb-2 block text-sm">When did the call happened?</p>
            <v-date-picker class="inline-block h-full" v-model="form.called_at" :model-config="modelConfig">
              <template v-slot="{ inputValue, inputEvents }">
                <input class="rounded border bg-white px-2 py-1" :value="inputValue" v-on="inputEvents" />
              </template>
            </v-date-picker>
          </div>

          <!-- audio or video -->
          <div class="border-l border-gray-200 p-5">
            <p class="mb-2 block text-sm">Nature of the call</p>

            <div class="flex">
              <div class="mr-6 flex items-center">
                <input
                  id="audio"
                  v-model="form.type"
                  value="audio"
                  name="type"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500" />
                <label for="audio" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700">
                  This was an audio-only call
                </label>
              </div>

              <div class="flex items-center">
                <input
                  id="video"
                  v-model="form.type"
                  value="video"
                  name="type"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500" />
                <label for="video" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700">
                  This was a video call
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- who called -->
        <div class="border-b border-gray-200 p-5">
          <p class="mb-2 block text-sm">Who called?</p>

          <div class="mb-4 flex">
            <div class="mr-6 flex items-center">
              <input
                id="me"
                v-model="form.who_initiated"
                value="me"
                name="who_initiated"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500" />
              <label for="me" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700"> I called </label>
            </div>

            <div class="flex items-center">
              <input
                id="me_not_answered"
                v-model="form.who_initiated"
                value="me_not_answered"
                name="who_initiated"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500" />
              <label for="me_not_answered" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700">
                I called, but {{ data.contact_name }} didn't answer
              </label>
            </div>
          </div>

          <div class="flex">
            <div class="mr-6 flex items-center">
              <input
                id="contact"
                v-model="form.who_initiated"
                value="contact"
                name="who_initiated"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500" />
              <label for="contact" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700">
                {{ data.contact_name }} called
              </label>
            </div>

            <div class="flex items-center">
              <input
                id="contact_not_answered"
                v-model="form.who_initiated"
                value="contact_not_answered"
                name="who_initiated"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500" />
              <label for="contact_not_answered" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700">
                {{ data.contact_name }} called, but I didn't answer
              </label>
            </div>
          </div>
        </div>

        <!-- description field -->
        <div v-if="descriptionFieldShown" class="border-b border-gray-200 p-5">
          <text-area
            v-model="form.description"
            :label="'Description'"
            :rows="10"
            :required="false"
            :maxlength="65535"
            :textarea-class="'block w-full mb-3'" />
        </div>

        <!-- reason field -->
        <div v-if="reasonFieldShown" class="border-b border-gray-200 p-5">
          <p class="mb-2 block text-sm">Was there a reason for the call?</p>
          <select
            v-model="form.call_reason_id"
            name="types"
            id="types"
            class="w-full rounded-md border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm">
            <optgroup
              v-for="callReasonType in data.call_reason_types"
              :key="callReasonType.id"
              :label="callReasonType.label">
              <option v-for="reason in callReasonType.reasons" :key="reason.id" :value="reason.id">
                {{ reason.label }}
              </option>
            </optgroup>
          </select>
        </div>

        <!-- options -->
        <div class="border-b border-gray-200 p-5">
          <!-- cta to add a description -->
          <span
            v-if="!descriptionFieldShown"
            class="mr-2 inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
            @click="showDescriptionField">
            + add description
          </span>

          <!-- cta to add a reason -->
          <span
            v-if="!reasonFieldShown"
            class="mr-2 inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
            @click="showReasonField">
            + add reason
          </span>

          <!-- cta to add emotion -->
          <span
            v-if="!emotionFieldShown"
            class="inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
            @click="showEmotionField">
            + add emotion
          </span>
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="createCallModalShown = false" />
        <pretty-button :text="'Add date'" :state="loadingState" :icon="'plus'" :classes="'save'" />
      </div>
    </form>

    <!-- calls -->
    <ul v-if="localCalls.length > 0" class="mb-2 rounded-lg border border-gray-200 bg-white">
      <li v-for="call in localCalls" :key="call.id" class="item-list border-b border-gray-200 hover:bg-slate-50">
        <div v-if="editedCallId !== call.id" class="flex items-center justify-between p-3">
          <div class="flex items-center">
            <div>
              <!-- icon phone cancel -->
              <svg
                v-if="!call.answered"
                xmlns="http://www.w3.org/2000/svg"
                class="mr-2 h-4 w-4 text-red-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z" />
              </svg>

              <!-- call accepted -->
              <svg
                v-if="call.answered"
                xmlns="http://www.w3.org/2000/svg"
                class="mr-2 h-4 w-4 text-green-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>

            <span class="mr-2 text-sm text-gray-500">{{ call.called_at }}</span>

            <!-- who called -->
            <span
              v-if="call.who_initiated == 'me'"
              class="mr-2 rounded border border-neutral-200 py-1 px-2 text-xs font-semibold text-neutral-800">
              I called
            </span>
            <span
              v-else
              class="mr-2 rounded border border-neutral-200 py-1 px-2 text-xs font-semibold text-neutral-800">
              {{ data.contact_name }} called
            </span>

            <!-- reason, if defined -->
            <span v-if="call.reason">{{ call.reason.label }}</span>
          </div>

          <hover-menu :show-edit="true" :show-delete="true" @edit="showUpdateCallModal(call)" @delete="destroy(call)" />
        </div>

        <!-- edit call -->
        <form v-if="editedCallId === call.id" class="bg-form" @submit.prevent="update(call)">
          <errors :errors="form.errors" />

          <div class="border-b border-gray-200">
            <!-- date -->
            <div class="flex border-b border-gray-200">
              <div class="p-5">
                <p class="mb-2 block text-sm">When did the call happened?</p>
                <v-date-picker class="inline-block h-full" v-model="form.called_at" :model-config="modelConfig">
                  <template v-slot="{ inputValue, inputEvents }">
                    <input class="rounded border bg-white px-2 py-1" :value="inputValue" v-on="inputEvents" />
                  </template>
                </v-date-picker>
              </div>

              <!-- audio or video -->
              <div class="border-l border-gray-200 p-5">
                <p class="mb-2 block text-sm">Nature of the call</p>

                <div class="flex">
                  <div class="mr-6 flex items-center">
                    <input
                      id="audio"
                      v-model="form.type"
                      value="audio"
                      name="type"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500" />
                    <label for="audio" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700">
                      This was an audio-only call
                    </label>
                  </div>

                  <div class="flex items-center">
                    <input
                      id="video"
                      v-model="form.type"
                      value="video"
                      name="type"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500" />
                    <label for="video" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700">
                      This was a video call
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- who called -->
            <div class="border-b border-gray-200 p-5">
              <p class="mb-2 block text-sm">Who called?</p>

              <div class="mb-4 flex">
                <div class="mr-6 flex items-center">
                  <input
                    id="me"
                    v-model="form.who_initiated"
                    value="me"
                    name="who_initiated"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500" />
                  <label for="me" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700"> I called </label>
                </div>

                <div class="flex items-center">
                  <input
                    id="me_not_answered"
                    v-model="form.who_initiated"
                    value="me_not_answered"
                    name="who_initiated"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500" />
                  <label for="me_not_answered" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700">
                    I called, but {{ data.contact_name }} didn't answer
                  </label>
                </div>
              </div>

              <div class="flex">
                <div class="mr-6 flex items-center">
                  <input
                    id="contact"
                    v-model="form.who_initiated"
                    value="contact"
                    name="who_initiated"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500" />
                  <label for="contact" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700">
                    {{ data.contact_name }} called
                  </label>
                </div>

                <div class="flex items-center">
                  <input
                    id="contact_not_answered"
                    v-model="form.who_initiated"
                    value="contact_not_answered"
                    name="who_initiated"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500" />
                  <label for="contact_not_answered" class="ml-2 block cursor-pointer text-sm font-medium text-gray-700">
                    {{ data.contact_name }} called, but I didn't answer
                  </label>
                </div>
              </div>
            </div>

            <!-- description field -->
            <div v-if="descriptionFieldShown" class="border-b border-gray-200 p-5">
              <text-area
                v-model="form.description"
                :label="'Description'"
                :rows="10"
                :required="false"
                :maxlength="65535"
                :textarea-class="'block w-full mb-3'" />
            </div>

            <!-- reason field -->
            <div v-if="reasonFieldShown" class="border-b border-gray-200 p-5">
              <p class="mb-2 block text-sm">Was there a reason for the call?</p>
              <select
                v-model="form.call_reason_id"
                name="types"
                id="types"
                class="w-full rounded-md border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm">
                <optgroup
                  v-for="callReasonType in data.call_reason_types"
                  :key="callReasonType.id"
                  :label="callReasonType.label">
                  <option v-for="reason in callReasonType.reasons" :key="reason.id" :value="reason.id">
                    {{ reason.label }}
                  </option>
                </optgroup>
              </select>
            </div>

            <!-- options -->
            <div class="border-b border-gray-200 p-5">
              <!-- cta to add a description -->
              <span
                v-if="!descriptionFieldShown"
                class="mr-2 inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
                @click="showDescriptionField">
                + add description
              </span>

              <!-- cta to add a reason -->
              <span
                v-if="!reasonFieldShown"
                class="mr-2 inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
                @click="showReasonField">
                + add reason
              </span>

              <!-- cta to add emotion -->
              <span
                v-if="!emotionFieldShown"
                class="inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
                @click="showEmotionField">
                + add emotion
              </span>
            </div>
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="editedCallId = 0" />
            <pretty-button :text="'Update'" :state="loadingState" :icon="'check'" :classes="'save'" />
          </div>
        </form>
      </li>
    </ul>

    <!-- blank state -->
    <div v-if="localCalls.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">There are no calls logged yet.</p>
    </div>
  </div>
</template>

<script>
import HoverMenu from '@/Shared/HoverMenu';
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import TextArea from '@/Shared/Form/TextArea';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    HoverMenu,
    PrettyButton,
    PrettySpan,
    TextInput,
    TextArea,
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
      createCallModalShown: false,
      localCalls: [],
      loadingState: '',
      editedCallId: 0,
      descriptionFieldShown: false,
      reasonFieldShown: false,
      form: {
        called_at: '',
        call_reason_id: 0,
        description: '',
        type: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localCalls = this.data.calls;
    this.form.who_initiated = 'me';
    this.form.type = 'audio';
  },

  methods: {
    showDescriptionField() {
      this.descriptionFieldShown = true;
      this.form.description = '';
    },

    showReasonField() {
      this.reasonFieldShown = true;
      this.form.call_reason_id = '';
    },

    showCreateCallModal() {
      this.form.errors = [];
      this.descriptionFieldShown = false;
      this.reasonFieldShown = false;
      this.createCallModalShown = true;
    },

    showUpdateCallModal(call) {
      this.form.errors = [];
      this.form.description = call.description;

      if (call.description) {
        this.descriptionFieldShown = true;
      }

      this.form.type = call.type;

      if (!call.answered && call.who_initiated == 'me') {
        this.form.who_initiated = 'me_not_answered';
      }
      if (call.answered && call.who_initiated == 'me') {
        this.form.who_initiated = 'me';
      }
      if (call.answered && call.who_initiated == 'contact') {
        this.form.who_initiated = 'contact';
      }
      if (!call.answered && call.who_initiated == 'contact') {
        this.form.who_initiated = 'contact_not_answered';
      }

      if (call.reason) {
        this.form.call_reason_id = call.reason.id;
        this.reasonFieldShown = true;
      } else {
        this.form.call_reason_id = 0;
        this.reasonFieldShown = false;
      }
      this.form.called_at = call.called_at;
      this.editedCallId = call.id;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.loadingState = '';
          this.flash('The call has been created', 'success');
          this.localCalls.unshift(response.data.data);
          this.createCallModalShown = false;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    update(call) {
      this.loadingState = 'loading';

      axios
        .put(call.url.update, this.form)
        .then((response) => {
          this.loadingState = '';
          this.flash('The call has been edited', 'success');
          this.localCalls[this.localCalls.findIndex((x) => x.id === call.id)] = response.data.data;
          this.editedCallId = 0;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    destroy(call) {
      if (confirm('Are you sure?')) {
        axios
          .delete(call.url.destroy)
          .then((response) => {
            this.flash('The call has been deleted', 'success');
            var id = this.localCalls.findIndex((x) => x.id === call.id);
            this.localCalls.splice(id, 1);
          })
          .catch((error) => {
            this.form.errors = error.response.data;
          });
      }
    },
  },
};
</script>
