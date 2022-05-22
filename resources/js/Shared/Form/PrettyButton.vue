<style lang="scss" scoped>
.icon {
  top: -1px;
}

.save {
  background-color: #fcf27e;
}

button {
  border-radius: 0.25rem !important;
  border-color: #191a1b;
  border-width: 1px !important;
  box-shadow: 2px 2px 0 #191a1b;
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

  &:disabled {
    box-shadow: none;
    transform: translate(0, 0);
  }
}
</style>

<template>
  <button
    :class="classes"
    :disabled="state == 'loading' || state == 'disabled'"
    class="relative text-sm"
    name="save"
    type="submit">
    <span v-if="state == 'loading'"> Loading… </span>

    <!-- + icon -->
    <svg
      v-if="icon === 'plus' && state != 'loading'"
      xmlns="http://www.w3.org/2000/svg"
      class="icon relative mr-1 inline h-5 w-5"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
    </svg>

    <!-- check icon -->
    <svg
      v-if="icon === 'check' && state != 'loading'"
      xmlns="http://www.w3.org/2000/svg"
      class="icon relative mr-1 inline h-5 w-5"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>

    <span v-if="state != 'loading'">
      {{ text }}
    </span>

    <!-- arrow icon -->
    <svg
      v-if="icon === 'arrow' && state != 'loading'"
      xmlns="http://www.w3.org/2000/svg"
      class="icon relative ml-1 mr-1 inline h-5 w-5"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
    </svg>
  </button>
</template>

<script>
export default {
  props: {
    text: {
      type: String,
      default: '',
    },
    icon: {
      type: String,
      default: '',
    },
    state: {
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
