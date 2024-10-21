<script setup>
import { Dropdown, Menu, MenuItem } from 'ant-design-vue';
import MenuIcon from '@/Shared/Icons/MenuIcon.vue';

defineEmits(['edit', 'delete']);
defineProps({
  showEdit: Boolean,
  showDelete: Boolean,
});
</script>

<template>
  <Dropdown>
    <a class="ant-dropdown-link cursor-pointer" @click.prevent>
      <MenuIcon />
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
