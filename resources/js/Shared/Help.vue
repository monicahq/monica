<script setup>
import { usePage } from '@inertiajs/vue3';
import { Tooltip } from 'ant-design-vue';
import { computed } from 'vue';
import FaceIcon from '@/Shared/Icons/FaceIcon.vue';

const props = defineProps({
  url: String,
  top: {
    type: String,
    default: '3px',
  },
});

const finalURL = computed(() => {
  return usePage().props.help_url + props.url;
});
</script>

<template>
  <a
    v-if="$page.props.auth.user?.help_shown"
    class="relative inline cursor-pointer"
    :href="finalURL"
    :style="'top:' + top"
    target="_blank"
    lang="en"
    rel="noopener noreferrer">
    <Tooltip placement="topLeft" :title="$t('This link will open in a new tab')" arrow-point-at-center>
      <FaceIcon :type="'question'" />
    </Tooltip>
  </a>
</template>
