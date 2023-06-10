<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Errors from '@/Shared/Form/Errors.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';

const props = defineProps({
  data: Object,
});

const form = useForm({
  slice_of_life_id: props.data.slice?.id,
});

const loadingState = ref(false);
const editSlicesModalShown = ref(false);
const localSlices = ref(props.data.slices);
const slice = ref(props.data.slice);

const showSliceModal = () => {
  editSlicesModalShown.value = true;
};

const update = () => {
  loadingState.value = 'loading';

  axios
    .put(props.data.url.slice_store, form)
    .then((response) => {
      editSlicesModalShown.value = false;
      loadingState.value = '';
      slice.value = response.data.data;
    })
    .catch(() => {
      loadingState.value = '';
    });
};

const reset = () => {
  form.slice_of_life_id = null;
  axios.delete(props.data.url.slice_reset, form).then(() => {
    editSlicesModalShown.value = false;
    slice.value = null;
  });
};
</script>

<template>
  <div class="mb-8">
    <p class="mb-2 flex items-center justify-between font-bold">
      <span>{{ $t('Slices of life') }}</span>

      <span
        v-if="!editSlicesModalShown && localSlices.length > 0"
        class="relative cursor-pointer text-xs text-gray-600 dark:text-gray-400"
        @click="showSliceModal">
        {{ $t('Edit') }}
      </span>

      <!-- close button -->
      <span
        v-if="editSlicesModalShown"
        class="cursor-pointer text-xs text-gray-600 dark:text-gray-400"
        @click="editSlicesModalShown = false">
        {{ $t('Close') }}
      </span>
    </p>

    <!-- edit slice of life -->
    <div
      v-if="editSlicesModalShown && localSlices.length > 0"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
      <form @submit.prevent="update()">
        <div class="border-b border-gray-200 p-2 dark:border-gray-700">
          <errors :errors="form.errors" />

          <!-- slices of life -->
          <dropdown
            v-model="form.slice_of_life_id"
            :data="localSlices"
            :required="false"
            :class="'mb-2'"
            :placeholder="$t('Choose a value')"
            :dropdown-class="'block w-full'" />
        </div>

        <div class="flex justify-between p-2">
          <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editSlicesModalShown = false" />
          <pretty-button
            :href="'data.url.vault.create'"
            :text="$t('Save')"
            :state="loadingState"
            :icon="'check'"
            :class="'save'" />
        </div>

        <div v-if="slice" class="border-t border-gray-200 p-2 dark:border-gray-700">
          <p class="cursor-pointer text-sm text-blue-500 hover:underline" @click="reset()">
            {{ $t('Or remove the slice') }}
          </p>
        </div>
      </form>
    </div>

    <!-- blank state -->
    <p v-if="!slice" class="text-sm text-gray-600 dark:text-gray-400">{{ $t('Not set') }}</p>

    <div v-else>
      <Link :href="slice.url.show" class="text-blue-500 hover:underline">
        {{ slice.name }}
      </Link>
    </div>
  </div>
</template>
