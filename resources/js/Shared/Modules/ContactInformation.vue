<script setup>
import { ref, watch, reactive, useTemplateRef } from 'vue';
import { flash } from '@/methods';
import { trans } from 'laravel-vue-i18n';
import ComboBox from '@/Shared/Form/ComboBox.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import Errors from '@/Shared/Form/Errors.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import JetConfirmationModal from '@/Components/Jetstream/ConfirmationModal.vue';
import { Headset } from 'lucide-vue-next';

const props = defineProps({
  data: Object,
});

const loadingState = ref('');
const adding = ref(false);
const localData = ref(props.data.contact_information);
const editingId = ref(0);
const deleting = ref(null);
const kinds = ref(null);
const protocol = ref(null);

const newDataRef = useTemplateRef('newData');
const renameRef = useTemplateRef('rename');

const form = reactive({
  data: '',
  contact_information_type_id: 0,
  contact_information_kind: null,
  contact_information_kind_custom: null,
  errors: [],
});

watch(
  () => form.contact_information_type_id,
  (newValue) => {
    for (const optgroup in props.data.contact_information_types) {
      let types = props.data.contact_information_types[optgroup].options;
      let id = types.findIndex((x) => x.id === newValue);
      if (id === -1) {
        continue;
      }
      let type = types[id];
      kinds.value = props.data.contact_information_kinds[type.type] ?? null;
      let p = props.data.protocols[type.name_translation_key] ?? null;
      protocol.value = p !== null ? p.url : null;
      return;
    }
    kinds.value = null;
    protocol.value = null;
  },
  { immediate: true },
);

const showCreateModal = () => {
  form.errors = [];
  adding.value = true;
  form.contact_information_type_id = props.data.contact_information_types.email.options[0].id;
  form.contact_information_kind = null;
  form.contact_information_kind_custom = null;
  form.data = '';
};

const showEditModal = (info) => {
  form.errors = [];
  editingId.value = info.id;
  form.contact_information_type_id = info.contact_information_type.id;
  form.contact_information_kind = info.contact_information_kind?.id;
  if (info.contact_information_kind !== null && info.contact_information_kind.id == '-1') {
    form.contact_information_kind_custom = info.contact_information_kind.name;
  }
  form.data = info.data;
};

const preSubmit = () => {
  if (form.contact_information_kind == -1) {
    form.contact_information_kind = form.contact_information_kind_custom;
  }
};

const submit = () => {
  loadingState.value = 'loading';

  preSubmit();
  axios
    .post(props.data.url.store, form)
    .then((response) => {
      const type = response.data.data.contact_information_type.type;
      if (localData.value.length === 0) {
        localData.value = {};
      }
      if (localData.value[type] === undefined) {
        localData.value[type] = [];
      }
      localData.value[type].unshift(response.data.data);
      loadingState.value = '';
      adding.value = false;
      flash(trans('The contact information has been created'), 'success');
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const update = (info) => {
  loadingState.value = 'loading';

  preSubmit();
  axios
    .put(info.url.update, form)
    .then((response) => {
      loadingState.value = '';
      const type = response.data.data.contact_information_type.type;
      localData.value[type][localData.value[type].findIndex((x) => x.id === info.id)] = response.data.data;
      editingId.value = 0;
      flash(trans('The contact information has been updated'), 'success');
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const destroy = () => {
  loadingState.value = 'loading';

  axios
    .delete(deleting.value.url.destroy)
    .then(() => {
      loadingState.value = '';
      const type = deleting.value.contact_information_type.type;
      localData.value[type].splice(
        localData.value[type].findIndex((x) => x.id === deleting.value.id),
        1,
      );
      deleting.value = null;
      flash(trans('The contact information has been deleted'), 'success');
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
      deleting.value = null;
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
        @click="showCreateModal" />
    </div>

    <!-- add a contact information modal -->
    <form
      v-if="adding"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <div v-if="form.errors.length > 0" class="p-5">
          <Errors :errors="form.errors" />
        </div>

        <!-- contact information types -->
        <div class="p-5">
          <Dropdown
            ref="newType"
            v-model.number="form.contact_information_type_id"
            :data="data.contact_information_types"
            :required="true"
            :placeholder="$t('Choose a value')"
            :dropdown-class="'block w-full'"
            :label="$t('Type')"
            :autofocus="true"
            @change="newDataRef.focus()" />
        </div>

        <!-- content -->
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <label class="mb-2 block text-sm dark:text-gray-100" for="newData">
            {{ $t('Content') }}
          </label>
          <div class="relative flex">
            <ComboBox
              v-if="kinds !== null"
              class="me-3 flex-none"
              v-model="form.contact_information_kind"
              v-model:new-value="form.contact_information_kind_custom"
              :label="$t('Kind')"
              :data="kinds"
              :required="false"
              :placeholder="$t('Choose a value')"
              :dropdown-class="'block'" />
            <TextInput
              ref="newData"
              id="newData"
              class="flex-1"
              v-model="form.data"
              :label="$t('Data')"
              :type="'text'"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              :placeholder="protocol"
              @esc-key-pressed="adding = false" />
          </div>
        </div>
      </div>

      <div class="flex justify-between p-5">
        <PrettySpan :text="$t('Cancel')" :class="'me-3'" @click="adding = false" />
        <PrettyButton :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- contact infos -->
    <!-- blank state -->
    <div
      v-if="localData.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_contact.svg" :alt="$t('Contact informations')" class="mx-auto mt-4 h-20 w-20" />
      <p class="px-5 pb-5 pt-2 text-center">
        {{ $t('There are no contact informations yet.') }}
      </p>
    </div>

    <template v-else v-for="(contactInformationGroup, gid) in data.contact_information_groups" :key="gid">
      <div v-if="localData[gid] !== undefined && localData[gid].length > 0">
        <p>{{ contactInformationGroup }}</p>
        <ul class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <li
            v-for="info in localData[gid]"
            :key="info.id"
            class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
            <!-- contact information -->
            <div v-if="editingId !== info.id" class="flex items-center justify-between px-3 py-2">
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
                <li class="me-4 inline cursor-pointer text-blue-500 hover:underline" @click="showEditModal(info)">
                  {{ $t('Edit') }}
                </li>
                <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="deleting = info">
                  {{ $t('Delete') }}
                </li>
              </ul>
            </div>

            <!-- edit info modal -->
            <form v-if="editingId === info.id" class="bg-gray-50 dark:bg-gray-900" @submit.prevent="update(info)">
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
                    <ComboBox
                      v-if="kinds !== null"
                      class="me-3 flex-none"
                      v-model="form.contact_information_kind"
                      v-model:new-value="form.contact_information_kind_custom"
                      :label="$t('Kind')"
                      :data="kinds"
                      :required="false"
                      :placeholder="$t('Choose a value')"
                      :dropdown-class="'block'" />
                    <TextInput
                      ref="rename"
                      id="rename"
                      class="flex-1"
                      v-model="form.data"
                      :label="$t('Data')"
                      :type="'text'"
                      :autofocus="true"
                      :input-class="'block w-full'"
                      :required="true"
                      :autocomplete="false"
                      :maxlength="255"
                      :placeholder="protocol"
                      @esc-key-pressed="editingId = 0" />
                  </div>
                </div>
              </div>

              <div class="flex justify-between p-5">
                <PrettySpan :text="$t('Cancel')" :class="'me-3'" @click="editingId = 0" />
                <PrettyButton :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
              </div>
            </form>
          </li>
        </ul>
      </div>
    </template>

    <JetConfirmationModal :show="deleting !== null" @close="deleting = null">
      <template #title>
        {{ $t('Delete a contact information') }}
      </template>

      <template #content>
        {{ $t('Are you sure? This action cannot be undone.') }}
      </template>

      <template #footer>
        <PrettySpan :text="$t('Cancel')" :class="'me-3'" @click="deleting = null" />
        <PrettyButton :text="$t('Delete')" :state="loadingState" :icon="'trash'" :class="'save'" @click="destroy" />
      </template>
    </JetConfirmationModal>
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
