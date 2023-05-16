<script setup>
import { Dropdown, Menu, MenuItem } from 'ant-design-vue';

defineEmits(['edit', 'delete']);
defineProps({
  showEdit: Boolean,
  showDelete: Boolean,
});
</script>

<template>
  <Dropdown>
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
      <Menu :theme="isDark() ? 'dark' : 'light'">
        <MenuItem v-if="showEdit">
          <a href="" @click.prevent="$emit('edit', $event.target.value)">{{ $t('Edit') }}</a>
        </MenuItem>
        <MenuItem v-if="showDelete">
          <a href="" @click.prevent="$emit('delete', $event.target.value)">{{ $t('Delete') }}</a>
        </MenuItem>
      </Menu>
    </template>
  </Dropdown>
</template>

<style lang="scss" scoped>
.ant-dropdown-menu {
  padding: 0;
}

.ant-dropdown-menu-item:hover {
  background-color: #f8fafc; // bg-slate-50
}

.dark .ant-dropdown-menu-item:hover {
  background-color: #020617 !important; // bg-slate-950
}
</style>
