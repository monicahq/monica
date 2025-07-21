<script setup>
import { ref, reactive, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { flash } from '@/methods';
import Layout from '@/Layouts/Layout.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import JetConfirmationModal from '@/Components/Jetstream/ConfirmationModal.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import { filter } from 'lodash';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const loadingState = ref('');
const creatingContactInformation = ref(false);
const contactInformationEditing = ref(0);
const localContactInformationTypes = ref(props.data.contact_information_types);
const contactInformationDeleting = ref(null);

const groupsFiltered = computed(() =>
  filter(props.data.contact_information_groups, (_, key) => key !== 'email' && key !== 'phone'),
);

const form = reactive({
  name: '',
  protocol: '',
  type: '',
  errors: [],
});

const showContactInformationTypeModal = () => {
  form.name = '';
  form.protocol = '';
  form.type = '';
  creatingContactInformation.value = true;
  contactInformationEditing.value = 0;
};

const updateAdressTypeModal = (contactInformationType) => {
  form.name = contactInformationType.name;
  form.protocol = contactInformationType.protocol;
  form.type = contactInformationType.type;
  contactInformationEditing.value = contactInformationType.id;
  creatingContactInformation.value = false;
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.contact_information_type_store, form)
    .then((response) => {
      localContactInformationTypes.value.unshift(response.data.data);
      loadingState.value = null;
      creatingContactInformation.value = false;
      flash(trans('The contact information type has been created'), 'success');
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const update = (contactInformationType) => {
  loadingState.value = 'loading';

  axios
    .put(contactInformationType.url.update, form)
    .then((response) => {
      let id = localContactInformationTypes.value[contactInformationType.type].findIndex(
        (x) => x.id === contactInformationType.id,
      );
      localContactInformationTypes.value[contactInformationType.type][id] = response.data.data;
      loadingState.value = null;
      contactInformationEditing.value = 0;
      flash(trans('The contact information type has been updated'), 'success');
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const destroy = () => {
  axios
    .delete(contactInformationDeleting.value.url.destroy)
    .then(() => {
      let id = localContactInformationTypes.value.findIndex((x) => x.id === contactInformationDeleting.value.id);
      localContactInformationTypes.value.splice(id, 1);
      contactInformationDeleting.value = null;
      flash(trans('The contact information type has been deleted'), 'success');
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
      contactInformationDeleting.value = null;
    });
};
</script>

<template>
  <Layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <Link :href="data.url.settings" class="text-blue-500 hover:underline">
                {{ $t('Settings') }}
              </Link>
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
              <Link :href="data.url.personalize" class="text-blue-500 hover:underline">
                {{ $t('Personalize your account') }}
              </Link>
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
          <PrettyButton
            v-if="!creatingContactInformation"
            :text="$t('Add a type')"
            :icon="'plus'"
            @click="showContactInformationTypeModal" />
        </div>

        <!-- modal to create a new contact information type -->
        <form
          v-if="creatingContactInformation"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <Errors :errors="form.errors" />

            <Dropdown
              v-model="form.type"
              dropdown-class="mb-2"
              :label="$t('Type')"
              :required="true"
              :data="groupsFiltered"
              @esc-key-pressed="creatingContactInformation = false" />

            <TextInput
              ref="newContactInformationType"
              v-model="form.name"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full mb-3'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="creatingContactInformation = false" />

            <TextInput
              v-model="form.protocol"
              :label="$t('Protocol')"
              :type="'text'"
              :input-class="'block w-full'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              :help="
                $t(
                  'A contact information can be clickable. For instance, a phone number can be clickable and launch the default application in your computer. If you do not know the protocol for the type you are adding, you can simply omit this field.',
                )
              "
              @esc-key-pressed="creatingContactInformation = false" />
          </div>

          <div class="flex justify-between p-5">
            <PrettySpan :text="$t('Cancel')" :class="'me-3'" @click="creatingContactInformation = false" />
            <PrettyButton :text="$t('Add')" :state="loadingState" :icon="'plus'" :class="'save'" />
          </div>
        </form>

        <!-- list of groups types -->
        <template v-for="(contactInformationGroup, gid) in data.contact_information_groups" :key="gid">
          <span>{{ contactInformationGroup }}</span>
          <ul
            v-if="localContactInformationTypes[gid].length > 0"
            class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="contactInformationType in localContactInformationTypes[gid]"
              :key="contactInformationType.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <!-- detail of the contact information type -->
              <div
                v-if="contactInformationEditing !== contactInformationType.id"
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
                    {{ $t('Edit') }}
                  </li>
                  <li
                    v-if="contactInformationType.can_be_deleted"
                    class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900"
                    @click="contactInformationDeleting = contactInformationType">
                    {{ $t('Delete') }}
                  </li>
                </ul>
              </div>

              <!-- rename a contactInformationType modal -->
              <form
                v-if="contactInformationEditing === contactInformationType.id"
                class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
                @submit.prevent="update(contactInformationType)">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                  <Errors :errors="form.errors" />

                  <TextInput
                    ref="rename"
                    v-model="form.name"
                    :label="$t('Name')"
                    :type="'text'"
                    :autofocus="true"
                    :input-class="'block w-full mb-3'"
                    :required="true"
                    :autocomplete="false"
                    :maxlength="255"
                    @esc-key-pressed="contactInformationEditing = 0" />

                  <TextInput
                    v-model="form.protocol"
                    :label="$t('Protocol')"
                    :type="'text'"
                    :input-class="'block w-full'"
                    :required="false"
                    :autocomplete="false"
                    :maxlength="255"
                    :disabled="contactInformationType.can_be_deleted === false"
                    :help="
                      $t(
                        'A contact information can be clickable. For instance, a phone number can be clickable and launch the default application in your computer. If you do not know the protocol for the type you are adding, you can simply omit this field.',
                      )
                    "
                    @esc-key-pressed="creatingContactInformation = false" />
                </div>

                <div class="flex justify-between p-5">
                  <PrettySpan :text="$t('Cancel')" :class="'me-3'" @click.prevent="contactInformationEditing = 0" />
                  <PrettyButton :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
                </div>
              </form>
            </li>
          </ul>
        </template>

        <JetConfirmationModal :show="contactInformationDeleting !== null" @close="contactInformationDeleting = null">
          <template #title>
            {{ $t('Delete a contact information type') }}
          </template>

          <template #content>
            {{
              $t(
                'Are you sure? This will remove the contact information type from all contacts, but won’t delete the contacts themselves.',
              )
            }}
          </template>

          <template #footer>
            <PrettySpan :text="$t('Cancel')" :class="'me-3'" @click.prevent="contactInformationDeleting = null" />
            <PrettyButton :text="$t('Delete')" :state="loadingState" :icon="'trash'" :class="'save'" @click="destroy" />
          </template>
        </JetConfirmationModal>

        <!-- blank state -->
        <div
          v-if="localContactInformationTypes.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{ $t('There are no contact information types yet.') }}
          </p>
        </div>
      </div>
    </main>
  </Layout>
</template>

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
