<script>
let input = require('./PInput').default;

let checkbox = {
  name: 'checkbox',

  input_type: 'checkbox',
  input_iclass: 'p-curve p-thick',

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

export default checkbox;
</script>