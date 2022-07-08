<style lang="scss" scoped>
.ant-dropdown-menu {
  padding: 0;
}

.ant-dropdown-menu-item:hover {
  background-color: rgb(248 250 252 / var(--tw-bg-opacity));
}
</style>

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
      <a-menu>
        <a-menu-item v-if="showEdit">
          <a href="" @click.prevent="$emit('edit', $event.target.value)">Edit</a>
        </a-menu-item>
        <a-menu-item v-if="showDelete">
          <a href="" @click.prevent="$emit('delete', $event.target.value)">{{ $t('app.delete') }}</a>
        </a-menu-item>
      </a-menu>
    </template>
  </a-dropdown>
</template>

<script>
export default {
  components: {},

  props: {
    showEdit: {
      type: Boolean,
      default: false,
    },
    showDelete: {
      type: Boolean,
      default: false,
    },
  },
};
</script>
