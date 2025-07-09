<script setup>
import { ref, useTemplateRef } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import JetButton from '@/Components/Button.vue';
import JetFormSection from '@/Components/Jetstream/FormSection.vue';
import JetInput from '@/Components/Input.vue';
import JetInputError from '@/Components/InputError.vue';
import JetLabel from '@/Components/Label.vue';
import JetActionMessage from '@/Components/Jetstream/ActionMessage.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';

const props = defineProps({
  user: Object,
});

const form = useForm({
  _method: 'PUT',
  first_name: props.user.first_name,
  last_name: props.user.last_name,
  email: props.user.email,
  photo: null,
});

const photoPreview = ref(null);
const photoInput = useTemplateRef('photoInput');

const updateProfileInformation = () => {
  if (photoInput.value) {
    form.photo = photoInput.value.files[0];
  }

  form.post(route('user-profile-information.update'), {
    errorBag: 'updateProfileInformation',
    preserveScroll: true,
    onSuccess: () => clearPhotoFileInput(),
  });
};

const selectNewPhoto = () => {
  photoInput.value.click();
};

const updatePhotoPreview = () => {
  const photo = photoInput.value.files[0];

  if (!photo) {
    return;
  }

  const reader = new FileReader();

  reader.onload = (e) => {
    photoPreview.value = e.target.result;
  };

  reader.readAsDataURL(photo);
};

const deletePhoto = () => {
  router.delete(route('current-user-photo.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      photoPreview.value = null;
      clearPhotoFileInput();
    },
  });
};

const clearPhotoFileInput = () => {
  if (photoInput.value?.value) {
    photoInput.value.value = null;
  }
};
</script>

<template>
  <JetFormSection @submitted="updateProfileInformation">
    <template #title>
      {{ $t('Profile Information') }}
    </template>

    <template #description>
      {{ $t("Update your account's profile information and email address.") }}
    </template>

    <template #form>
      <!-- Profile Photo -->
      <div v-if="$page.props.jetstream.managesProfilePhotos" class="col-span-6 sm:col-span-4">
        <!-- Profile Photo File Input -->
        <input ref="photoInput" type="file" class="hidden" @change="updatePhotoPreview" />

        <JetLabel for="photo" ::value="$t('Photo')" />

        <!-- Current Profile Photo -->
        <div v-show="!photoPreview" class="mt-2">
          <img :src="user.profile_photo_url" :alt="user.name" class="h-20 w-20 rounded-full object-cover" />
        </div>

        <!-- New Profile Photo Preview -->
        <div v-show="photoPreview" class="mt-2">
          <span
            class="block h-20 w-20 rounded-full bg-cover bg-center bg-no-repeat"
            :style="'background-image: url(\'' + photoPreview + '\');'" />
        </div>

        <JetSecondaryButton class="me-2 mt-2" type="button" @click.prevent="selectNewPhoto">
          {{ $t('Select A New Photo') }}
        </JetSecondaryButton>

        <JetSecondaryButton v-if="user.profile_photo_path" type="button" class="mt-2" @click.prevent="deletePhoto">
          {{ $t('Remove Photo') }}
        </JetSecondaryButton>

        <JetInputError :message="form.errors.photo" class="mt-2" />
      </div>

      <!-- First Name -->
      <div class="col-span-6 mb-4 sm:col-span-4">
        <JetLabel for="first_name" :value="$t('First name')" />
        <JetInput
          id="first_name"
          v-model="form.first_name"
          type="text"
          class="mt-1 block w-full"
          autocomplete="firstname" />
        <JetInputError :message="form.errors.first_name" class="mt-2" />
      </div>

      <!-- Last Name -->
      <div class="col-span-6 mb-4 sm:col-span-4">
        <JetLabel for="last_name" :value="$t('Last name')" />
        <JetInput
          id="last_name"
          v-model="form.last_name"
          type="text"
          class="mt-1 block w-full"
          autocomplete="lastname" />
        <JetInputError :message="form.errors.last_name" class="mt-2" />
      </div>

      <!-- Email -->
      <div class="col-span-6 mb-4 sm:col-span-4">
        <JetLabel for="email" :value="$t('Email')" />
        <JetInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" autocomplete="email" />
        <JetInputError :message="form.errors.email" class="mt-2" />
      </div>
    </template>

    <template #actions>
      <JetActionMessage :on="form.recentlySuccessful" class="me-3">
        {{ $t('Saved.') }}
      </JetActionMessage>

      <JetButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
        {{ $t('Save') }}
      </JetButton>
    </template>
  </JetFormSection>
</template>
