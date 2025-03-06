<template>
  <span
    :class="'relative cursor-pointer border-zinc-900 bg-white text-sm dark:border-zinc-100 dark:bg-gray-800 dark:text-gray-100'">
    {{ text }}
  </span>
</template>

<script>
export default {
  props: {
    text: {
      type: String,
      default: '',
    },
  },
};
</script>

<style lang="scss" scoped>
span {
  --tw-shadow: 2px 2px 0 #191a1b !important;
  border-radius: 0.25rem !important;
  border-width: 1px !important;
  box-shadow:
    var(--tw-ring-offset-shadow, 0 0 transparent), var(--tw-ring-shadow, 0 0 transparent), var(--tw-shadow) !important;
  display: inline-block !important;
  position: relative !important;
  text-decoration: none !important;
  transition-duration: 0.15s !important;
  transition-property:
    background-color,
    border-color,
    color,
    fill,
    stroke,
    opacity,
    box-shadow,
    transform,
    filter,
    backdrop-filter,
    -webkit-backdrop-filter !important;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1) !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
  padding-left: 9px;
  padding-right: 9px;

  &:hover {
    box-shadow: none !important;
    transform: translate(2px, 2px);
  }
}

.dark span {
  --tw-shadow: 2px 2px 0 rgb(242, 242, 245) !important;
}
</style>
