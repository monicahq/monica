<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Errors from '@/Shared/Form/Errors.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import { Pencil } from 'lucide-vue-next';

const props = defineProps({
  data: Object,
});

const form = useForm({
  religion_id: props.data.religion?.id,
});

const loadingState = ref(false);
const editReligion = ref(false);
const localReligions = ref(props.data.religions);
const religion = ref(props.data.religion?.name);

const update = () => {
  loadingState.value = 'loading';

  axios
    .put(props.data.url.update, form)
    .then((response) => {
      editReligion.value = false;
      loadingState.value = '';
      religion.value = response.data.data.religion.name;
    })
    .catch(() => {
      loadingState.value = '';
    });
};

const showEditModal = () => {
  editReligion.value = true;
};
</script>

<template>
  <div class="mb-4">
    <div class="pb-1 mb-2 items-center justify-between border-b border-gray-200 dark:border-gray-700 flex">
      <!-- title -->
      <div class="text-xs">{{ $t('Religion') }}</div>

      <span v-if="!editReligion" class="relative cursor-pointer" @click="showEditModal()">
        <Pencil class="h-3 w-3 text-gray-400" />
      </span>

      <!-- close button -->
      <span
        v-if="editReligion"
        class="cursor-pointer text-xs text-gray-600 dark:text-gray-400"
        @click="editReligion = false">
        {{ $t('Close') }}
      </span>
    </div>

    <!-- edit religion -->
    <div
      v-if="editReligion"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
      <form @submit.prevent="update()">
        <div class="border-b border-gray-200 p-2 dark:border-gray-700">
          <errors :errors="form.errors" />

          <!-- religions -->
          <dropdown
            v-model="form.religion_id"
            :data="localReligions"
            :required="false"
            :class="'mb-2'"
            :placeholder="$t('Choose a value')"
            :dropdown-class="'block w-full'" />
        </div>

        <div class="flex justify-between p-2">
          <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editReligion = false" />
          <pretty-button
            :href="'data.url.vault.create'"
            :text="$t('Save')"
            :state="loadingState"
            :icon="'check'"
            :class="'save'" />
        </div>
      </form>
    </div>

    <!-- blank state -->
    <p v-if="!religion" class="text-sm text-gray-600 dark:text-gray-400">{{ $t('Not set') }}</p>

    <p v-else>
      {{ religion }}
    </p>
  </div>
</template>

<style lang="scss" scoped>
.icon-sidebar {
  top: -2px;
}
</style>
