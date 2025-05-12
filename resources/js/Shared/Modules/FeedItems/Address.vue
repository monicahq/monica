<template>
  <div class="rounded-lg border border-gray-300 dark:border-gray-700">
    <!-- contact information -->
    <div
      v-if="!contactViewMode"
      class="flex items-center border-b border-gray-300 px-3 py-2 text-sm dark:border-gray-700">
      <avatar
        :data="data.contact.avatar"
        :class="'relative me-2 h-5 w-5 rounded-full border border-gray-200 dark:border-gray-800'" />

      <div class="flex flex-col">
        <InertiaLink :href="data.contact.url" class="text-gray-800 hover:underline dark:text-gray-200">{{
          data.contact.name
        }}</InertiaLink>
      </div>
    </div>

    <div v-if="data.address.object" class="flex justify-between px-3 py-3">
      <!-- address detail -->
      <div>
        <p v-if="data.address.object.type" class="mb-2 text-sm font-semibold">
          {{ data.address.object.type.name }}
        </p>
        <div>
          <p v-if="data.address.object.line_1">
            {{ data.address.object.line_1 }}
          </p>
          <p v-if="data.address.object.line_2">
            {{ data.address.object.line_2 }}
          </p>
          <p v-if="data.address.object.postal_code || data.address.object.city">
            {{ data.address.object.postal_code }} {{ data.address.object.city }}
          </p>
          <p v-if="data.address.object.country">
            {{ data.address.object.country }}
          </p>
        </div>
      </div>

      <!-- map image -->
      <div v-if="data.address.object.image">
        <a :href="data.address.object.url.show" target="_blank">
          <img :src="data.address.object.image" class="rounded-xs" :alt="data.address.object.description" />
        </a>
      </div>
    </div>

    <div v-else class="flex justify-between px-3 py-3">
      <p>{{ data.address.description }}</p>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Avatar from '@/Shared/Avatar.vue';

export default {
  components: {
    InertiaLink: Link,
    Avatar,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
    contactViewMode: {
      type: Boolean,
      default: true,
    },
  },
};
</script>
