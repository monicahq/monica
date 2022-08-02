<template>
  <div class="relative inline" :class="divOuterClass" :style="'top: ' + top">
    <a-popover placement="bottomLeft">
      <template #content>
        <div class="flex">
          <img
            class="mr-2 inline rounded-full"
            src="https://ucarecdn.com/2f86ee48-9258-4b5c-aaf5-82269edcf770/-/scale_crop/45x45/smart/"
            alt="" />
          <div>
            <p>Last talked to: Apr 20, 2012</p>
            <p>Last activity: Apr 20, 2012</p>
          </div>
        </div>
      </template>
      <template v-if="showName" #title>
        <span>FOU</span>
      </template>
      <div class="inline-flex items-center text-sm">
        <img
          class="inline rounded-full"
          :class="showName ? 'mr-2' : ''"
          :src="
            'https://ucarecdn.com/2f86ee48-9258-4b5c-aaf5-82269edcf770/-/scale_crop/' +
            previewContactSize +
            'x' +
            previewContactSize +
            '/smart/'
          "
          alt="" />
        <a v-if="showName" class="colored-link" href="">FOU</a>
      </div>
    </a-popover>
  </div>
</template>

<script>
export default {
  components: {},

  props: {
    top: {
      type: String,
      default: '0px',
    },
    contactId: {
      type: Number,
      default: 0,
    },
    previewContactSize: {
      type: Number,
      default: 18,
    },
    showName: {
      type: Boolean,
      default: true,
    },
    divOuterClass: {
      type: String,
      default: '',
    },
  },
};
</script>

<style lang="scss" scoped></style>
