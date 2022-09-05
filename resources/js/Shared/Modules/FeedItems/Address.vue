<template>
  <div class="rounded-lg border border-gray-300 dark:border-gray-700">
    <!-- contact information -->
    <div
      v-if="!contactViewMode"
      class="flex items-center border-b border-gray-300 px-3 py-2 text-sm dark:border-gray-700">
      <avatar
        :data="data.contact.avatar"
        :classes="'rounded-full border-gray-200 dark:border-gray-800 border relative h-5 w-5 mr-2'" />

      <div class="flex flex-col">
        <inertia-link :href="data.contact.url" class="text-gray-800 hover:underline dark:text-gray-200">{{
          data.contact.name
        }}</inertia-link>
      </div>
    </div>

    <div v-if="data.address.object" class="flex justify-between px-3 py-3">
      <!-- address detail -->
      <div>
        <p v-if="data.address.object.type" class="mb-2 text-sm font-semibold">
          {{ data.address.object.type.name }}
        </p>
        <div>
          <p v-if="data.address.object.street">
            {{ data.address.object.street }}
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
          <img :src="data.address.object.image" class="rounded" :alt="data.address.object.description" />
        </a>
      </div>
    </div>

    <div v-else class="flex justify-between px-3 py-3">
      <p>{{ data.address.description }}</p>
    </div>
  </div>
</template>

<script>
import Avatar from '@/Shared/Avatar.vue';

export default {
  components: {
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

<style lang="scss" scoped>
.icon-avatar {
  top: 3px;
  left: -11px;
}
</style>
