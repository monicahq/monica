<script setup>
import { watch, nextTick, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import JetLabel from '@/Components/Label.vue';
import JetInput from '@/Components/Input.vue';
import JetInputError from '@/Components/InputError.vue';
import JetButton from '@/Components/Button.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';

const form = useForm({
  name: '',
});

const props = defineProps({
  keyid: Number,
  nameUpdate: String,
});

const emit = defineEmits(['close']);

const nameInput = useTemplateRef('nameInput');

const updateKey = () => {
  form.put(route('webauthn.update', props.keyid), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      form.reset();
      nextTick().then(() => emit('close'));
    },
  });
};

watch(
  () => props.nameUpdate,
  (value) => {
    form.name = value;
    nextTick().then(() => nameInput.value.focus());
  },
  {
    immediate: true,
  },
);
</script>

<template>
  <form @submit.prevent="updateKey">
    <div class="mt-4">
      <JetLabel for="keyname" :value="$t('Key name')" />
      <JetInput
        type="text"
        class="mt-1 block"
        id="keyname"
        ref="nameInput"
        v-model="form.name"
        required
        @keyup.enter="updateKey" />

      <JetInputError :message="form.errors.name" class="mt-2" />
    </div>

    <div class="mt-5">
      <JetSecondaryButton @click="$emit('close')">
        {{ $t('Cancel') }}
      </JetSecondaryButton>

      <JetButton class="ms-2" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
        {{ $t('Update') }}
      </JetButton>
    </div>
  </form>
</template>
