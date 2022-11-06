<script setup>
import Errors from '@/Shared/Form/Errors.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import { useForm } from '@inertiajs/inertia-vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
  data: Object,
});

const form = useForm({
  religion_id: '',
});

const loadingState = ref(false);
const editReligion = ref(false);
const localReligions = ref([]);
const religion = ref('');

onMounted(() => {
  form.religion_id = props.data.religion ? props.data.religion.id : null;
  religion.value = props.data.religion ? props.data.religion.name : null;
  localReligions.value = props.data.religions;
});

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
    <div class="mb-3 items-center justify-between border-b border-gray-200 dark:border-gray-700 sm:flex">
      <div class="mb-2 text-xs sm:mb-0">{{ $t('contact.religions_title') }}</div>
      <span v-if="!editReligion" class="relative cursor-pointer" @click="showEditModal()">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="icon-sidebar relative inline h-3 w-3 text-gray-300 hover:text-gray-600 dark:text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
      </span>

      <!-- close button -->
      <span
        v-if="editReligion"
        class="cursor-pointer text-xs text-gray-600 dark:text-gray-400"
        @click="editReligion = false">
        {{ $t('app.close') }}
      </span>
    </div>

    <!-- edit religion -->
    <div
      v-if="editReligion"
      class="bg-form mb-6 rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-900">
      <form @submit.prevent="update()">
        <div class="border-b border-gray-200 p-2 dark:border-gray-700">
          <errors :errors="form.errors" />

          <!-- companies -->
          <dropdown
            v-model="form.religion_id"
            :data="localReligions"
            :required="false"
            :div-outer-class="'mb-2'"
            :placeholder="$t('app.choose_value')"
            :dropdown-class="'block w-full'" />
        </div>

        <div class="flex justify-between p-2">
          <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="editReligion = false" />
          <pretty-button
            :href="'data.url.vault.create'"
            :text="$t('app.save')"
            :state="loadingState"
            :icon="'check'"
            :classes="'save'" />
        </div>
      </form>
    </div>

    <!-- blank state -->
    <p v-if="!religion" class="text-sm text-gray-600 dark:text-gray-400">Not set</p>

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
