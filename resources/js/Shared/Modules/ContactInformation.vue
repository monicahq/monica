<script setup>
import { ref, watch, reactive, useTemplateRef } from 'vue';
import { flash } from '@/methods';
import { trans } from 'laravel-vue-i18n';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import Errors from '@/Shared/Form/Errors.vue';
import { Headset } from 'lucide-vue-next';
import JetConfirmationModal from '@/Components/Jetstream/ConfirmationModal.vue';

const props = defineProps({
  data: Object,
});

const loadingState = ref('');
const addContactInformationModalShown = ref(false);
const localContactInformation = ref(props.data.contact_information);
const editedContactInformationId = ref(0);
const contactInformationDeleting = ref(null);
const contactInformationKinds = ref(null);
const protocol = ref(null);

const newDataRef = useTemplateRef('newData');
const renameRef = useTemplateRef('rename');

const form = reactive({
  data: '',
  contact_information_type_id: 0,
  contact_information_kind: null,
  errors: [],
});

watch(
  () => form.contact_information_type_id,
  (newValue) => {
    let id = props.data.contact_information_types.findIndex((x) => x.id === newValue);
    if (id === -1) {
      contactInformationKinds.value = null;
      protocol.value = null;
      return;
    }
    let type = props.data.contact_information_types[id];
    contactInformationKinds.value = props.data.contact_information_kinds[type.type] ?? null;
    let p = props.data.protocols[type.name_translation_key] ?? null;
    protocol.value = p !== null ? p.url : null;
  },
  { immediate: true },
);

const showCreateContactInformationModal = () => {
  form.errors = [];
  addContactInformationModalShown.value = true;
  form.contact_information_type_id = props.data.contact_information_types[0].id;
  form.contact_information_kind = null;
  form.data = '';
};

const showEditContactInformationModal = (info) => {
  form.errors = [];
  editedContactInformationId.value = info.id;
  form.contact_information_type_id = info.contact_information_type.id;
  form.contact_information_kind = info.contact_information_kind?.id;
  form.data = info.data;
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, form)
    .then((response) => {
      flash(trans('The contact information has been created'), 'success');
      localContactInformation.value.unshift(response.data.data);
      loadingState.value = '';
      addContactInformationModalShown.value = false;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const update = (info) => {
  loadingState.value = 'loading';

  axios
    .put(info.url.update, form)
    .then((response) => {
      loadingState.value = '';
      flash(trans('The contact information has been updated'), 'success');
      localContactInformation.value[localContactInformation.value.findIndex((x) => x.id === info.id)] =
        response.data.data;
      editedContactInformationId.value = 0;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const destroy = () => {
  loadingState.value = 'loading';

  axios
    .delete(contactInformationDeleting.value.url.destroy)
    .then(() => {
      loadingState.value = '';
      flash(trans('The contact information has been deleted'), 'success');
      let id = localContactInformation.value.findIndex((x) => x.id === contactInformationDeleting.value.id);
      localContactInformation.value.splice(id, 1);
      contactInformationDeleting.value = null;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
      contactInformationDeleting.value = null;
    });
};
</script>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <Headset class="h-4 w-4 text-gray-600" />

        <span class="font-semibold">
          {{ $t('Contact information') }}
        </span>
      </div>
      <PrettyButton
        :text="$t('Add a contact information')"
        :icon="'plus'"
        :class="'w-full sm:w-fit'"
        @click="showCreateContactInformationModal" />
    </div>

    <!-- add a contact information modal -->
    <form
      v-if="addContactInformationModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <div v-if="form.errors.length > 0" class="p-5">
          <Errors :errors="form.errors" />
        </div>

        <!-- contact information types -->
        <div class="p-5">
          <Dropdown
            v-model.number="form.contact_information_type_id"
            :data="data.contact_information_types"
            :required="true"
            :placeholder="$t('Choose a value')"
            :dropdown-class="'block w-full'"
            :label="$t('Type')"
            @change="newDataRef.focus()" />
        </div>

        <!-- content -->
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <label class="mb-2 block text-sm dark:text-gray-100" for="newData">
            {{ $t('Content') }}
          </label>
          <div class="relative flex">
            <Dropdown
              v-if="contactInformationKinds !== null"
              class="me-3 flex-none"
              v-model="form.contact_information_kind"
              :data="contactInformationKinds"
              :required="false"
              :placeholder="$t('Choose a value')"
              :dropdown-class="'block'" />
            <TextInput
              ref="newData"
              id="newData"
              class="flex-1"
              v-model="form.data"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              :placeholder="protocol"
              @esc-key-pressed="addContactInformationModalShown = false" />
          </div>
        </div>
      </div>

      <div class="flex justify-between p-5">
        <PrettySpan :text="$t('Cancel')" :class="'me-3'" @click="addContactInformationModalShown = false" />
        <PrettyButton :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- contact infos -->
    <div v-if="localContactInformation.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="info in localContactInformation"
          :key="info.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <!-- contact information -->
          <div v-if="editedContactInformationId !== info.id" class="flex items-center justify-between px-3 py-2">
            <div>
              <a
                :href="info.data_with_protocol"
                class="text-blue-500 hover:underline"
                rel="noopener noreferrer"
                target="_blank">
                {{ info.data }}
              </a>
              <span v-if="info.contact_information_kind" class="me-2 text-xs text-gray-500">
                — {{ info.contact_information_kind.name }}
              </span>
              <span v-if="info.label !== info.data" class="ms-2 text-xs text-gray-500"> ({{ info.label }}) </span>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li
                class="me-4 inline cursor-pointer text-blue-500 hover:underline"
                @click="showEditContactInformationModal(info)">
                {{ $t('Edit') }}
              </li>
              <li
                class="inline cursor-pointer text-red-500 hover:text-red-900"
                @click="contactInformationDeleting = info">
                {{ $t('Delete') }}
              </li>
            </ul>
          </div>

          <!-- edit info modal -->
          <form
            v-if="editedContactInformationId === info.id"
            class="bg-gray-50 dark:bg-gray-900"
            @submit.prevent="update(info)">
            <div class="border-b border-gray-200 dark:border-gray-700">
              <div v-if="form.errors.length > 0" class="p-5">
                <Errors :errors="form.errors" />
              </div>

              <!-- contact information types -->
              <div class="p-5">
                <Dropdown
                  v-model.number="form.contact_information_type_id"
                  :data="data.contact_information_types"
                  :required="true"
                  :placeholder="$t('Choose a value')"
                  :dropdown-class="'block w-full'"
                  :label="$t('Type')"
                  @change="renameRef[0].focus()" />
              </div>

              <!-- content -->
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <label class="mb-2 block text-sm dark:text-gray-100" for="rename">
                  {{ $t('Content') }}
                </label>
                <div class="relative flex">
                  <Dropdown
                    v-if="contactInformationKinds !== null"
                    class="me-3 flex-none"
                    v-model="form.contact_information_kind"
                    :data="contactInformationKinds"
                    :required="false"
                    :placeholder="$t('Choose a value')"
                    :dropdown-class="'block'" />
                  <TextInput
                    ref="rename"
                    id="rename"
                    class="flex-1"
                    v-model="form.data"
                    :type="'text'"
                    :autofocus="true"
                    :input-class="'block w-full'"
                    :required="true"
                    :autocomplete="false"
                    :maxlength="255"
                    :placeholder="protocol"
                    @esc-key-pressed="editedContactInformationId = 0" />
                </div>
              </div>
            </div>

            <div class="flex justify-between p-5">
              <PrettySpan :text="$t('Cancel')" :class="'me-3'" @click="editedContactInformationId = 0" />
              <PrettyButton :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
            </div>
          </form>
        </li>
      </ul>
    </div>

    <JetConfirmationModal :show="contactInformationDeleting !== null" @close="contactInformationDeleting = null">
      <template #title>
        {{ $t('Delete a contact information') }}
      </template>

      <template #content>
        {{ $t('Are you sure? This action cannot be undone.') }}
      </template>

      <template #footer>
        <PrettySpan :text="$t('Cancel')" :class="'me-3'" @click="contactInformationDeleting = null" />
        <PrettyButton :text="$t('Delete')" :state="loadingState" :icon="'trash'" :class="'save'" @click="destroy" />
      </template>
    </JetConfirmationModal>

    <!-- blank state -->
    <div
      v-if="localContactInformation.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_contact.svg" :alt="$t('Contact informations')" class="mx-auto mt-4 h-20 w-20" />
      <p class="px-5 pb-5 pt-2 text-center">
        {{ $t('There are no contact informations yet.') }}
      </p>
    </div>
  </div>
</template>

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
