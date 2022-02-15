<style lang="scss" scoped>
.optional-badge {
  border-radius: 4px;
  color: #283e59;
  background-color: #edf2f9;
  padding: 1px 3px;
}

.length {
  top: 10px;
  right: 10px;
  background-color: #e5eeff;
  padding: 3px 4px;
}

.counter {
  padding-right: 64px;
}
</style>

<template>
  <div :class="divOuterClass">
    <label v-if="label" class="mb-2 block text-sm" :for="id">
      {{ label }}
      <span v-if="!required" class="optional-badge text-xs"> optional </span>
    </label>

    <div class="component relative">
      <select
        :id="id"
        v-model="selectedId"
        :autocomplete="country - name"
        :class="localDropdownClasses"
        :required="required"
        :disabled="disabled"
        :placeholder="placeholder"
        @change="change">
        <option v-for="item in data" :key="item.id" :value="item.id">
          {{ item.name }}
        </option>
      </select>
    </div>

    <p v-if="help" class="mb-3 mt-1 text-xs">
      {{ help }}
    </p>
  </div>
</template>

<script>
export default {
  props: {
    id: {
      type: String,
      default: 'dropdown-',
    },
    data: {
      type: Array,
      default() {
        return [];
      },
    },
    dropdownClass: {
      type: String,
      default: '',
    },
    divOuterClass: {
      type: String,
      default: '',
    },
    modelValue: {
      type: [String, Number],
      default: '',
    },
    name: {
      type: String,
      default: 'input',
    },
    help: {
      type: String,
      default: '',
    },
    label: {
      type: String,
      default: '',
    },
    required: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    autocomplete: {
      type: String,
      default: '',
    },
    placeholder: {
      type: String,
      default: '',
    },
    ref: {
      type: String,
      default: 'input',
    },
  },
  emits: ['update:modelValue'],

  data() {
    return {
      localDropdownClasses: '',
      selectedId: 0,
    };
  },

  created() {
    this.localDropdownClasses =
      'py-2 px-3 border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-white rounded-md shadow-sm focus:outline-none sm:text-sm ' +
      this.dropdownClass;

    var element = this.data.find((x) => x.selected == true);
    if (element) {
      this.selectedId = element.id;
    }
  },

  methods: {
    focus() {
      this.$refs.input.focus();
    },

    sendEscKey() {
      this.$emit('esc-key-pressed');
    },

    change() {
      this.$emit('update:modelValue', this.selectedId);
    },
  },
};
</script>
