<template>
  <div class="relative">
    <button ref="toggleButton" type="button" @click.prevent="toggleOpen">
      <span class="sr-only">Open Avatar Upload</span>
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-3 w-3 text-gray-300 hover:text-gray-600 dark:text-gray-400"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
      </svg>
    </button>
    <div
      ref="dropdown"
      class="z-10 absolute hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-auto dark:bg-gray-700 dark:divide-gray-600">
      <div class="py-2">
        <div class="block px-4 py-2">Photo Suggestions</div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 px-4 py-2">
          <photo-suggestion v-model="photo" :photos="photos" @select="select" />
        </div>
      </div>
      <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownUserAvatarButton">
        <li>
          <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
            >Upload a photo...</a
          >
        </li>
        <li>
          <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
            >Remove photo</a
          >
        </li>
      </ul>
    </div>
    <avatar :data="data.avatar" :class="'mx-auto mb-6 sm:w-1/2'" :img-classes="'rounded sm:w-72'" />
  </div>
</template>

<script>
import Avatar from '@/Shared/Avatar.vue';
import PhotoSuggestion from '@/Shared/Form/PhotoSuggestion.vue';

export default {
  components: {
    Avatar,
    PhotoSuggestion,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      photo: { src: '' },
      photos: [
        { src: 'https://flowbite.s3.amazonaws.com/docs/gallery/square/image-1.jpg' },
        { src: 'https://flowbite.s3.amazonaws.com/docs/gallery/square/image-2.jpg' },
        { src: 'https://flowbite.s3.amazonaws.com/docs/gallery/square/image-3.jpg' },
        { src: 'https://flowbite.s3.amazonaws.com/docs/gallery/square/image-4.jpg' },
        { src: 'https://flowbite.s3.amazonaws.com/docs/gallery/square/image-5.jpg' },
        { src: 'https://flowbite.s3.amazonaws.com/docs/gallery/square/image-6.jpg' },
        { src: 'https://flowbite.s3.amazonaws.com/docs/gallery/square/image-7.jpg' },
        { src: 'https://flowbite.s3.amazonaws.com/docs/gallery/square/image-8.jpg' },
        { src: 'https://flowbite.s3.amazonaws.com/docs/gallery/square/image-9.jpg' },
        { src: 'https://flowbite.s3.amazonaws.com/docs/gallery/square/image-10.jpg' },
        { src: 'https://flowbite.s3.amazonaws.com/docs/gallery/square/image-11.jpg' },
      ],
    };
  },

  mounted() {
    document.addEventListener('click', (event) => {
      if (!this.$refs.dropdown.contains(event.target) && !this.$refs.toggleButton.contains(event.target)) {
        this.$refs.dropdown.classList.add('hidden');
      }
    });
  },

  methods: {
    toggleOpen() {
      this.$refs.dropdown.classList.toggle('hidden');
    },
    select(photo) {
      console.log(photo);
    },
  },
};
</script>
