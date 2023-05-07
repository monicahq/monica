<script setup>
import { computed } from 'vue';

defineEmits(['edit', 'delete']);
defineProps({
  showEdit: Boolean,
  showDelete: Boolean,
});

const theme = computed(() => {
  return localStorage.theme === 'dark' ||
    (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
    ? 'dark'
    : 'light';
});
</script>

<template>
  <a-dropdown>
    <a class="ant-dropdown-link cursor-pointer" @click.prevent>
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
      </svg>
    </a>
    <template #overlay>
      <a-menu :theme="theme">
        <a-menu-item v-if="showEdit">
          <a href="" @click.prevent="$emit('edit', $event.target.value)">{{ $t('Edit') }}</a>
        </a-menu-item>
        <a-menu-item v-if="showDelete">
          <a href="" @click.prevent="$emit('delete', $event.target.value)">{{ $t('Delete') }}</a>
        </a-menu-item>
      </a-menu>
    </template>
  </a-dropdown>
</template>

<style lang="scss" scoped>
.ant-dropdown-menu {
  padding: 0;
}

.ant-dropdown-menu-item:hover {
  background-color: #f8fafc; // bg-slate-50
}

@media (prefers-color-scheme: dark) {
  .ant-dropdown-menu-item:hover {
    background-color: #020617; // bg-slate-950
  }
}
</style>
