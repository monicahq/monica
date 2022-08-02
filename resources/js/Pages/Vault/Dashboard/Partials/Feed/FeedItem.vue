<template>
  <div class="timeline-item relative flex items-center">
    <div class="icon z-10 mr-3 flex items-center justify-center rounded-full bg-gray-200">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-4 w-4 text-stone-500"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
      </svg>
    </div>
    <span class="mr-3 text-sm">
      {{ message }}
    </span>
  </div>
</template>

<script>
export default {
  props: {
    message: {
      type: String,
      default: null,
    },
  },
};
</script>

<style lang="scss" scoped>
.timeline-item {
  margin-left: 16px;
  padding: 18px 0;

  &::before {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    display: block;
    width: 2px;
    content: '';
    background-color: #d2d5da;
  }

  .icon {
    width: 32px;
    height: 32px;
    margin-left: -15px;
  }
}
</style>
