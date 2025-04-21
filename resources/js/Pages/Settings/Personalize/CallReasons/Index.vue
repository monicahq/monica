<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <InertiaLink :href="data.url.settings" class="text-blue-500 hover:underline">
                {{ $t('Settings') }}
              </InertiaLink>
            </li>
            <li class="relative me-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="me-2 inline">
              <InertiaLink :href="data.url.personalize" class="text-blue-500 hover:underline">
                {{ $t('Personalize your account') }}
              </InertiaLink>
            </li>
            <li class="relative me-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">{{ $t('Call reasons') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0">
            <span class="me-1"> 📲 </span>
            {{ $t('All the call reasons') }}
          </h3>
          <pretty-button
            v-if="!createCallReasonTypeModalShown"
            :text="$t('add a call reason type')"
            :icon="'plus'"
            @click="showCallReasonTypeModal" />
        </div>

        <!-- help text -->
        <div class="mb-6 flex rounded-xs border bg-slate-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-slate-900">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 pe-2"
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
            <p class="mb-2">{{ $t('Call reasons let you indicate the reason of calls you make to your contacts.') }}</p>
          </div>
        </div>

        <!-- modal to create a call reason type -->
        <form
          v-if="createCallReasonTypeModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submitCallReasonType()">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              ref="newCallReasonType"
              v-model="form.callReasonTypeName"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createCallReasonTypeModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createCallReasonTypeModalShown = false" />
            <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
          </div>
        </form>

        <!-- list of call reasons -->
        <ul
          v-if="localCallReasonTypes.length > 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <li v-for="callReasonType in localCallReasonTypes" :key="callReasonType.id">
            <!-- detail of the call reason type -->
            <div
              v-if="renameCallReasonTypeModalShownId !== callReasonType.id"
              class="item-list flex items-center justify-between border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <span class="text-base font-semibold">{{ callReasonType.label }}</span>

              <!-- actions -->
              <ul class="text-sm">
                <li class="inline cursor-pointer" @click="renameCallReasonTypeModal(callReasonType)">
                  <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                </li>
                <li
                  class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900"
                  @click="destroyCallReasonType(callReasonType)">
                  {{ $t('Delete') }}
                </li>
              </ul>
            </div>

            <!-- rename a call reason type modal -->
            <form
              v-if="renameCallReasonTypeModalShownId === callReasonType.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
              @submit.prevent="updateCallReasonType(callReasonType)">
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <errors :errors="form.errors" />

                <text-input
                  ref="renameCallReasonType"
                  v-model="form.callReasonTypeName"
                  :label="$t('Name')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="renameCallReasonTypeModalShownId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span
                  :text="$t('Cancel')"
                  :class="'me-3'"
                  @click.prevent="renameCallReasonTypeModalShownId = 0" />
                <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
              </div>
            </form>

            <!-- list of call reasons -->
            <div
              v-for="reason in callReasonType.reasons"
              :key="reason.id"
              class="border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <!-- detail of the relationship type -->
              <div v-if="renameReasonModalId !== reason.id" class="flex items-center justify-between px-5 py-2 ps-6">
                <span>{{ reason.label }}</span>

                <!-- actions -->
                <ul class="text-sm">
                  <li class="inline cursor-pointer" @click="renameReasonModal(reason)">
                    <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                  </li>
                  <li
                    class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900"
                    @click="destroyReason(callReasonType, reason)">
                    {{ $t('Delete') }}
                  </li>
                </ul>
              </div>

              <!-- rename the reason modal -->
              <form
                v-else
                class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
                @submit.prevent="updateReason(callReasonType, reason)">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                  <errors :errors="form.errors" />

                  <text-input
                    ref="rename"
                    v-model="form.label"
                    :label="$t('Name')"
                    :type="'text'"
                    :autofocus="true"
                    :input-class="'block w-full'"
                    :required="true"
                    :class="'mb-4'"
                    :placeholder="$t('Wish good day')"
                    :autocomplete="false"
                    :maxlength="255"
                    @esc-key-pressed="renameReasonModalId = 0" />
                </div>

                <div class="flex justify-between p-5">
                  <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="renameReasonModalId = 0" />
                  <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
                </div>
              </form>
            </div>

            <!-- create a new reason -->
            <div
              v-if="createReasonModalId !== callReasonType.id"
              class="item-list border-b border-gray-200 px-5 py-2 ps-6 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <span
                class="cursor-pointer text-sm text-blue-500 hover:underline"
                @click="showReasonModal(callReasonType)"
                >{{ $t('add a reason') }}</span
              >
            </div>

            <!-- create a new resaon -->
            <form
              v-if="createReasonModalId === callReasonType.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
              @submit.prevent="storeReason(callReasonType)">
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <errors :errors="form.errors" />

                <text-input
                  ref="newReason"
                  v-model="form.label"
                  :label="$t('Name')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createReasonModalId = 0" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="createReasonModalId = 0" />
                <pretty-button :text="$t('Add')" :state="loadingState" :icon="'plus'" :class="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div
          v-if="localCallReasonTypes.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{ $t('Call reasons let you indicate the reason of calls you make to your contacts.') }}
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';

export default {
  components: {
    InertiaLink: Link,
    Layout,
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
      createCallReasonTypeModalShown: false,
      renameCallReasonTypeModalShownId: 0,
      createReasonModalId: 0,
      renameReasonModalId: 0,
      localCallReasonTypes: [],
      form: {
        callReasonTypeName: '',
        label: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localCallReasonTypes = this.data.call_reason_types;
  },

  methods: {
    showCallReasonTypeModal() {
      this.form.callReasonTypeName = '';
      this.createCallReasonTypeModalShown = true;
      this.renameCallReasonTypeModalShownId = 0;

      this.$nextTick().then(() => {
        this.$refs.newCallReasonType.focus();
      });
    },

    renameCallReasonTypeModal(callReasonType) {
      this.form.callReasonTypeName = callReasonType.label;
      this.renameCallReasonTypeModalShownId = callReasonType.id;
      this.createCallReasonTypeModalShown = false;

      this.$nextTick().then(() => {
        this.$refs.renameCallReasonType[0].focus();
      });
    },

    showReasonModal(callReasonType) {
      this.createReasonModalId = callReasonType.id;
      this.form.label = '';

      this.$nextTick().then(() => {
        this.$refs.newReason[0].focus();
      });
    },

    renameReasonModal(reason) {
      this.form.label = reason.label;
      this.renameReasonModalId = reason.id;

      this.$nextTick().then(() => {
        this.$refs.rename[0].focus();
      });
    },

    submitCallReasonType() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.call_reason_type_store, this.form)
        .then((response) => {
          this.flash(this.$t('The call reason type has been created'), 'success');
          this.localCallReasonTypes.unshift(response.data.data);
          this.loadingState = null;
          this.createCallReasonTypeModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    updateCallReasonType(callReasonType) {
      this.loadingState = 'loading';

      axios
        .put(callReasonType.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The call reason type has been updated'), 'success');
          this.localCallReasonTypes[this.localCallReasonTypes.findIndex((x) => x.id === callReasonType.id)] =
            response.data.data;
          this.loadingState = null;
          this.renameCallReasonTypeModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroyCallReasonType(callReasonType) {
      if (
        confirm(
          this.$t(
            'Are you sure? This will delete all the call reasons of this type for all the contacts that were using it.',
          ),
        )
      ) {
        axios
          .delete(callReasonType.url.destroy)
          .then(() => {
            this.flash(this.$t('The call reason type has been deleted'), 'success');
            var id = this.localCallReasonTypes.findIndex((x) => x.id === callReasonType.id);
            this.localCallReasonTypes.splice(id, 1);
          })
          .catch((error) => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },

    storeReason(callReasonType) {
      this.loadingState = 'loading';

      axios
        .post(callReasonType.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The call reason has been created'), 'success');
          this.loadingState = null;
          this.createReasonModalId = 0;
          var id = this.localCallReasonTypes.findIndex((x) => x.id === callReasonType.id);
          this.localCallReasonTypes[id].reasons.unshift(response.data.data);
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    updateReason(callReasonType, reason) {
      this.loadingState = 'loading';

      axios
        .put(reason.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The call reason has been updated'), 'success');
          this.loadingState = null;
          this.renameReasonModalId = 0;
          var callReasonTypeId = this.localCallReasonTypes.findIndex((x) => x.id === callReasonType.id);
          var typeId = this.localCallReasonTypes[callReasonTypeId].reasons.findIndex((x) => x.id === reason.id);
          this.localCallReasonTypes[callReasonTypeId].reasons[typeId] = response.data.data;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroyReason(callReasonType, reason) {
      if (
        confirm(
          this.$t(
            'Are you sure? This will delete all the relationships of this type for all the contacts that were using it.',
          ),
        )
      ) {
        axios
          .delete(reason.url.destroy)
          .then(() => {
            this.flash(this.$t('The call reason has been deleted'), 'success');
            var callReasonTypeId = this.localCallReasonTypes.findIndex((x) => x.id === callReasonType.id);
            var typeId = this.localCallReasonTypes[callReasonTypeId].reasons.findIndex((x) => x.id === reason.id);
            this.localCallReasonTypes[callReasonTypeId].reasons.splice(typeId, 1);
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
