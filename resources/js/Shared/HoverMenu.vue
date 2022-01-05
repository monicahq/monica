<style lang="scss" scoped>
</style>

<template>
  <a-dropdown>
    <a class="ant-dropdown-link cursor-pointer" @click.prevent>
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
           stroke="currentColor"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"
        />
      </svg>
    </a>
    <template #overlay>
      <a-menu>
        <a-menu-item>
          <a href="javascript:;">Edit</a>
        </a-menu-item>
        <a-menu-item>
          <a href="javascript:;">Delete</a>
        </a-menu-item>
      </a-menu>
    </template>
  </a-dropdown>
</template>

<script>

export default {
  components: {
  },

  props: {
  },
};
</script>
