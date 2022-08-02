<template>
  <span :class="classes" class="relative cursor-pointer text-sm">
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
    classes: {
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
  background-color: #fff;
  border-color: #191a1b;
  border-width: 1px !important;
  box-shadow: var(--tw-ring-offset-shadow, 0 0 transparent), var(--tw-ring-shadow, 0 0 transparent), var(--tw-shadow) !important;
  display: inline-block !important;
  position: relative !important;
  text-decoration: none !important;
  transition-duration: 0.15s !important;
  transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform, filter,
    backdrop-filter, -webkit-backdrop-filter !important;
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

@media (prefers-color-scheme: dark) {
  a {
    --tw-shadow: 2px 2px 0 #575a5d !important;
  }
}
</style>
