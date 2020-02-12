<template>
  <div class="di">
    <button :class="classes" name="save" type="submit" :data-cy="cypressSelector">
      <ball-pulse-loader v-if="state == 'loading'" color="#fff" size="7px" />
      <span v-if="state != 'loading'">
        {{ text }}
      </span>
    </button>
  </div>
</template>

<script>
import 'vue-loaders/dist/vue-loaders.css';
import BallPulseLoader from 'vue-loaders/src/loaders/ball-pulse';

export default {
  components: {
    BallPulseLoader,
  },

  props: {
    text: {
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
    cypressSelector: {
      type: String,
      default: '',
    },
  }
};
</script>
