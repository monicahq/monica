<script setup>
import { ref, watch, nextTick, computed, onMounted, useTemplateRef } from 'vue';
import JetLabel from '@/Components/Label.vue';
import JetInput from '@/Components/Input.vue';
import JetInputError from '@/Components/InputError.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';
import JetButton from '@/Components/Button.vue';
import WaitForKey from '@/Pages/Webauthn/Partials/WaitForKey.vue';

const props = defineProps({
  errorMessage: String,
  name: String,
  form: Object,
});

const emit = defineEmits(['start', 'stop', 'register', 'update:name']);

const registering = ref(false);
const error = ref(props.errorMessage);
const nameInput = useTemplateRef('nameInput');

onMounted(() => {
  props.form.reset();
  nextTick().then(() => nameInput.value.focus());
});

watch(
  () => props.errorMessage,
  (value) => {
    error.value = value;
  },
);

const processing = computed(() => registering.value === true || props.form.processing);

const begin = () => {
  registering.value = true;
  error.value = '';

  nextTick().then(() => emit('start'));
  axios
    .post(route('webauthn.store.options'))
    .then((response) => {
      if (response.data !== undefined) {
        registerWaitForKey(response.data.publicKey);
      } else {
        nextTick().then(() => registerWaitForKey(response.props.publicKey));
      }
    })
    .catch((e) => {
      stop();
      error.value = e.response.data.errors[0];
    });
};

const registerWaitForKey = (publicKey) => {
  if (registering.value === true) {
    emit('register', publicKey);
  }
};

const stop = () => {
  registering.value = false;
  nextTick().then(() => emit('stop'));
};
</script>

<template>
  <form @submit.prevent="begin">
    <JetInputError :message="form.errors.register" class="mt-2" />

    <div class="mt-4" v-show="!processing || form.errors.name">
      <JetLabel for="name" :value="$t('Key name')" />
      <JetInput
        type="text"
        class="mt-1 block w-3/4"
        id="name"
        ref="nameInput"
        :value="name"
        @input="$emit('update:name', $event.target.value)"
        required
        @keyup.enter="begin()" />

      <JetInputError :message="form.errors.name" class="mt-2" />
    </div>

    <div class="mt-4" v-show="registering">
      <WaitForKey :error-message="error" :form="form" @retry="begin()" />
    </div>

    <div class="mt-5 flex items-center">
      <JetSecondaryButton @click="stop()">
        {{ $t('Cancel') }}
      </JetSecondaryButton>

      <JetButton class="ms-2" :class="{ 'opacity-25': processing }" :disabled="processing">
        {{ $t('Submit') }}
      </JetButton>
    </div>
  </form>
</template>
