<template>
  <div>
    <div v-if="dataerror || exception" class="border-red mb-3 rounded-xs border p-3" v-bind="$attrs">
      <p class="mb-2">{{ $t('Oops! Something went wrong.') }}</p>
      <template v-if="exception">
        <p class="mb0">
          {{ errors.message }}
        </p>
        <p>
          <a href="" @click.prevent="toggle">
            {{ $t('More errors') }}
          </a>
        </p>
        <p v-show="traces">
          <span class="mb0">
            {{ $t('Exception:') }}
            {{ errors.exception }}
          </span>
          <br />
          <span class="text-xs">
            {{
              $t(':file at line :line', {
                file: errors.file,
                line: errors.line,
              })
            }}
          </span>
          <br />
          <span v-for="trace in errors.trace" :key="trace.id" class="text-xs">
            {{
              $t(':file in :class at line :line', {
                file: trace.file,
                class: `${trace.class}${trace.type}${trace.function}`,
                line: trace.line,
              })
            }}
            <br />
          </span>
        </p>
      </template>
      <template v-else-if="dataerror">
        <!-- <p v-if="flatten[0] !== $t('The given data was invalid.')" class="mb0">
          {{ flatten[0] }}
        </p> -->
        <template v-if="display(flatten[1])">
          <p v-for="errorsList in flatten[1]" :key="errorsList.id">
            <span v-for="error in errorsList" :key="error.id" class="mb0 mt2 text-sm">
              {{ error }}
            </span>
          </p>
        </template>
      </template>
    </div>
  </div>
</template>

<script>
export default {
  inheritAttrs: false,

  props: {
    errors: {
      type: [Object, Array],
      default: null,
    },
  },

  data() {
    return {
      traces: false,
    };
  },

  computed: {
    dataerror() {
      return this.errors !== null && (this.errors.errors !== undefined || this.flatten.length > 0);
    },
    flatten() {
      return _.flatten(_.toArray(this.errors));
    },
    exception() {
      return this.errors !== null && this.errors.exception !== undefined;
    },
  },

  methods: {
    display(val) {
      return _.isObject(val);
    },
    toggle() {
      this.traces = !this.traces;
    },
  },
};
</script>

<style lang="scss" scoped>
.border-red {
  background-color: #fff5f5;
  border-color: #fc8181;
  color: #c53030;
}

.dark .border-red {
  background-color: #333131 !important;
  border-color: #4b2626 !important;
  color: #c53030 !important;
}
</style>
