<style lang="scss" scoped>
</style>

<template>
  <div class="inline relative" :style="'top: ' + top">
    <a-popover placement="bottomLeft">
      <template #content>
        <div class="flex">
          <img class="rounded-full mr-2 inline" src="https://ucarecdn.com/2f86ee48-9258-4b5c-aaf5-82269edcf770/-/scale_crop/45x45/smart/" alt="">
          <div>
            <p>Last talked to: Apr 20, 2012</p>
            <p>Last activity: Apr 20, 2012</p>
          </div>
        </div>
      </template>
      <template #title>
        <span>Lorraine Del Giacco</span>
      </template>
      <div class="inline-flex text-sm items-center">
        <img class="rounded-full mr-2 inline" src="https://ucarecdn.com/2f86ee48-9258-4b5c-aaf5-82269edcf770/-/scale_crop/25x25/smart/" alt="">
        <a class="colored-link" href="">Lorraine Del Giaccosldkfjalsdjkfs</a>
      </div>
    </a-popover>
  </div>
</template>

<script>

export default {
  components: {
  },

  props: {
    top: {
      type: String,
      default: '0px',
    },
  },
};
</script>
