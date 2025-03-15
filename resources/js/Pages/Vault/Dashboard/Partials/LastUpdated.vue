<template>
  <div>
    <h3 class="mb-3 flex border-b border-gray-200 pb-1 font-medium dark:border-gray-700">
      <span class="relative me-2">
        <History class="icon-sidebar relative inline h-4 w-4 text-gray-300 hover:text-gray-600 dark:text-gray-400" />
      </span>

      <span class="me-2 inline">
        {{ $t('Last updated') }}
      </span>

      <help :url="$page.props.help_links.last_updated_contacts" :top="'4px'" />
    </h3>
    <div v-for="contact in data" :key="contact.id" class="mb-2 flex items-center text-sm">
      <avatar :data="contact.avatar" :class="'me-2 h-5 w-5 rounded-full'" />

      <InertiaLink :href="contact.url.show" class="text-blue-500 hover:underline">
        {{ contact.name }}
      </InertiaLink>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Help from '@/Shared/Help.vue';
import Avatar from '@/Shared/Avatar.vue';
import { History } from 'lucide-vue-next';

export default {
  components: {
    InertiaLink: Link,
    Help,
    Avatar,
    History,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },
};
</script>

<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}
</style>
