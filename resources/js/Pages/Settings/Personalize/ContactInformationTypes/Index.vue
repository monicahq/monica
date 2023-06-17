<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b">
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
            <li class="inline">
              {{ $t('Contact information types') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0">
            <span class="me-1"> ☎️ </span>
            {{ $t('All the contact information types') }}
          </h3>
          <pretty-button
            v-if="!createContactInformationTypeModalShown"
            :text="$t('Add a type')"
            :icon="'plus'"
            @click="showContactInformationTypeModal" />
        </div>

        <!-- modal to create a new contact information type -->
        <form
          v-if="createContactInformationTypeModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              ref="newContactInformationType"
              v-model="form.name"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full mb-3'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createContactInformationTypeModalShown = false" />

            <text-input
              v-model="form.protocol"
              :label="$t('Protocol')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              :help="
                $t(
                  'A contact information can be clickable. For instance, a phone number can be clickable and launch the default application in your computer. If you do not know the protocol for the type you are adding, you can simply omit this field.',
                )
              "
              @esc-key-pressed="createContactInformationTypeModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createContactInformationTypeModalShown = false" />
            <pretty-button :text="$t('Add')" :state="loadingState" :icon="'plus'" :class="'save'" />
          </div>
        </form>

        <!-- list of groups types -->
        <ul
          v-if="localContactInformationTypes.length > 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <li
            v-for="contactInformationType in localContactInformationTypes"
            :key="contactInformationType.id"
            class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
            <!-- detail of the contact information type -->
            <div
              v-if="renameContactInformationTypeModalShownId !== contactInformationType.id"
              class="flex items-center justify-between px-5 py-2">
              <div>
                <span class="text-base">{{ contactInformationType.name }}</span>
                <code v-if="contactInformationType.protocol" class="code ms-3 text-xs"
                  >[{{
                    $t('Protocol: :name', {
                      name: contactInformationType.protocol,
                    })
                  }}]</code
                >
              </div>

              <!-- actions -->
              <ul class="text-sm">
                <li
                  class="inline cursor-pointer text-blue-500 hover:underline"
                  @click="updateAdressTypeModal(contactInformationType)">
                  {{ $t('Rename') }}
                </li>
                <li
                  v-if="contactInformationType.can_be_deleted"
                  class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900"
                  @click="destroy(contactInformationType)">
                  {{ $t('Delete') }}
                </li>
              </ul>
            </div>

            <!-- rename a contactInformationType modal -->
            <form
              v-if="renameContactInformationTypeModalShownId === contactInformationType.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800"
              @submit.prevent="update(contactInformationType)">
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <errors :errors="form.errors" />

                <text-input
                  ref="rename"
                  v-model="form.name"
                  :label="$t('Name')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full mb-3'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="renameContactInformationTypeModalShownId = 0" />

                <text-input
                  v-model="form.protocol"
                  :label="$t('Protocol')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  :help="'A contact information can be clickable. For instance, a phone number can be clickable and we will launch the default application in your computer associated with a phone number. If you do not know the protocol for the type you are adding, you can simply omit this field.'"
                  @esc-key-pressed="createContactInformationTypeModalShown = false" />
              </div>

              <div class="flex justify-between p-5">
                <pretty-span
                  :text="$t('Cancel')"
                  :class="'me-3'"
                  @click.prevent="renameContactInformationTypeModalShownId = 0" />
                <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div
          v-if="localContactInformationTypes.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{
              $t(
                'Are you sure? This will remove the contact information types from all contacts, but won’t delete the contacts themselves.',
              )
            }}
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';
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
      createContactInformationTypeModalShown: false,
      renameContactInformationTypeModalShownId: 0,
      localContactInformationTypes: [],
      form: {
        name: '',
        protocol: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localContactInformationTypes = this.data.contact_information_types;
  },

  methods: {
    showContactInformationTypeModal() {
      this.form.name = '';
      this.form.protocol = '';
      this.createContactInformationTypeModalShown = true;
      this.renameContactInformationTypeModalShownId = 0;

      this.$nextTick().then(() => {
        this.$refs.newContactInformationType.focus();
      });
    },

    updateAdressTypeModal(contactInformationType) {
      this.form.name = contactInformationType.name;
      this.form.protocol = contactInformationType.protocol;
      this.renameContactInformationTypeModalShownId = contactInformationType.id;
      this.createContactInformationTypeModalShown = false;

      this.$nextTick().then(() => {
        this.$refs.rename[0].focus();
      });
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.contact_information_type_store, this.form)
        .then((response) => {
          this.flash(this.$t('The contact information type has been created'), 'success');
          this.localContactInformationTypes.unshift(response.data.data);
          this.loadingState = null;
          this.createContactInformationTypeModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(contactInformationType) {
      this.loadingState = 'loading';

      axios
        .put(contactInformationType.url.update, this.form)
        .then((response) => {
          this.flash(this.$t('The contact information type has been updated'), 'success');
          this.localContactInformationTypes[
            this.localContactInformationTypes.findIndex((x) => x.id === contactInformationType.id)
          ] = response.data.data;
          this.loadingState = null;
          this.renameContactInformationTypeModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(contactInformationType) {
      if (
        confirm(
          this.$t(
            'Are you sure? This will remove the contact information types from all contacts, but won’t delete the contacts themselves.',
          ),
        )
      ) {
        axios
          .delete(contactInformationType.url.destroy)
          .then(() => {
            this.flash(this.$t('The contact information type has been deleted'), 'success');
            var id = this.localContactInformationTypes.findIndex((x) => x.id === contactInformationType.id);
            this.localContactInformationTypes.splice(id, 1);
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
