<script>
const input = require('./PInput').default;

const radio = {
  name: 'radio',

  input_type: 'radio',
  input_iclass: 'p-round p-thick',

  components: input.components,
  model: input.model,
  props: input.props,
  data: input.data,
  computed: input.computed,
  watch: input.watch,
  mounted: input.mounted,
  methods: input.methods,

  render: input.render,
};

export default radio;
</script>
