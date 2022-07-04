<style lang="scss" scoped>
.icon {
  top: -1px;
}

a {
  --tw-shadow: 2px 2px 0 #575a5d !important;
  border-radius: 0.25rem !important;
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

<template>
  <inertia-link
    :class="classes"
    class="dark:box-s relative bg-white text-sm dark:bg-gray-700 dark:text-gray-300"
    :href="href">
    <!-- + icon -->
    <svg
      v-if="icon === 'plus' && state != 'loading'"
      xmlns="http://www.w3.org/2000/svg"
      class="icon relative inline h-5 w-5"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
    </svg>

    <!-- check icon -->
    <svg
      v-if="icon === 'check' && state != 'loading'"
      xmlns="http://www.w3.org/2000/svg"
      class="icon relative inline h-5 w-5"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>

    <span>
      {{ text }}
    </span>
  </inertia-link>
</template>

<script>
export default {
  components: {},

  props: {
    text: {
      type: String,
      default: '',
    },
    icon: {
      type: String,
      default: '',
    },
    href: {
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
