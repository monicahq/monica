<template>
  <inertia-link :class="linkClasses" :href="href" preserve-scroll>
    <!-- + icon -->
    <svg
      v-if="icon === 'plus'"
      xmlns="http://www.w3.org/2000/svg"
      class="icon relative inline h-5 w-5"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
    </svg>

    <!-- check icon -->
    <svg
      v-else-if="icon === 'check'"
      xmlns="http://www.w3.org/2000/svg"
      class="icon relative inline h-5 w-5"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>

    <!-- door icon -->
    <svg
      v-else-if="icon === 'exit'"
      class="icon relative mr-1 inline h-5 w-5"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      stroke-width="1.5"
      stroke="currentColor">
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
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

  computed: {
    linkClasses() {
      return [
        'relative text-sm dark:text-gray-100 dark:box-s',
        'bg-white dark:bg-gray-800 border-zinc-900 dark:border-zinc-100',
        this.classes,
      ];
    },
  },
};
</script>

<style lang="scss" scoped>
.icon {
  top: -1px;
}

a {
  --tw-shadow: 2px 2px 0 #191a1b !important;
  border-radius: 0.25rem !important;
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
    --tw-shadow: 2px 2px 0 rgb(242, 242, 245) !important;
  }
}
</style>
