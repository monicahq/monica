<script>
export default {
  props: {
    value: {
      type: Number,
      default: 0,
    },
  },

  render(createElement) {
    const text = this.$tc('people.stay_in_touch_modal_label', this.value, {count: '[slot]'});
    const texts = _.split(text, '[slot]');
    return createElement('div', [
      createElement('span', texts[0]),
      this.$slots.default,
      createElement('span', texts[1]),
    ]);
  },
};
</script>
