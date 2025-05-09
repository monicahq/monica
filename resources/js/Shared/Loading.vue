<template>
  <div class="inline-block">
    <div class="hollow-dots-spinner">
      <div class="dot" />
      <div class="dot" />
      <div class="dot" />
    </div>
  </div>
</template>

<style lang="scss" scoped>
.hollow-dots-spinner,
.hollow-dots-spinner * {
  box-sizing: border-box;
}

.hollow-dots-spinner {
  height: 15px;
  width: calc(30px * 3);
}

.hollow-dots-spinner .dot {
  width: 10px;
  height: 10px;
  margin: 0 calc(10px / 2);
  border: calc(10px / 5) solid #1d4ed8;
  border-radius: 50%;
  float: left;
  transform: scale(0);
  animation: hollow-dots-spinner-animation 1000ms ease infinite 0ms;
}

.hollow-dots-spinner .dot:nth-child(1) {
  animation-delay: calc(200ms * 1);
}

.hollow-dots-spinner .dot:nth-child(2) {
  animation-delay: calc(200ms * 2);
}

.hollow-dots-spinner .dot:nth-child(3) {
  animation-delay: calc(200ms * 3);
}

@keyframes hollow-dots-spinner-animation {
  50% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    opacity: 0;
  }
}
</style>
