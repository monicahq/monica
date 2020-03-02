<style lang="scss" scoped>
</style>

<template>
  <!-- list of reminders -->
  <div class="">
    <!-- summary and call to action -->
    <div class="flex pa2">
      <p class="mv0">Showing 5 out of 13 reminders</p>
      <inertia-link :href="''" class="mb1 db btn">Add a new reminder</inertia-link>
    </div>


    <!-- list of the first 5 reminders -->
    <ul class="ma0 list pl0">
      <li class="bb pa2 flex items-center relative">
        <span class="mr3 tc">
          <span class="db mb1">Feb 25</span>
          <span class="f6">2020</span>
        </span>
        <div>
          <span class="mb1 db">Anniversary of the day you first met</span>
          <div class="f6">
            <span class="dib mr2">Every week</span>
            <span>Long description about the thing</span>
          </div>
        </div>
        <div class="absolute right-1 pointer bb b--dotted bt-0 bl-0 br-0 f6">
          Edit
        </div>
      </li>
      <li class="bb pa2 flex items-center relative">
        <span class="mr3 tc">
          <span class="db mb1">Feb 25</span>
          <span class="f6">2020</span>
        </span>
        <div>
          <span class="mb1 db">Anniversary of the day you first met</span>
          <div class="f6">
            <span class="dib mr2">Every week</span>
            <span>Long description about the thing</span>
          </div>
        </div>
        <div class="absolute right-1 pointer bb b--dotted bt-0 bl-0 br-0 f6">
          Edit
        </div>
      </li>
      <li class="bb pa2 flex items-center relative">
        <span class="mr3 tc">
          <span class="db mb1">Feb 25</span>
          <span class="f6">2020</span>
        </span>
        <div>
          <span class="mb1 db">Anniversary of the day you first met</span>
          <div class="f6">
            <span class="dib mr2">Every week</span>
            <span>Long description about the thing</span>
          </div>
        </div>
        <div class="absolute right-1 pointer bb b--dotted bt-0 bl-0 br-0 f6">
          Edit
        </div>
      </li>
      <li class="bb pa2 flex items-center relative">
        <span class="mr3 tc">
          <span class="db mb1">Feb 25</span>
          <span class="f6">2020</span>
        </span>
        <div>
          <span class="mb1 db">Anniversary of the day you first met</span>
          <div class="f6">
            <span class="dib mr2">Every week</span>
            <span>Long description about the thing</span>
          </div>
        </div>
        <div class="absolute right-1 pointer bb b--dotted bt-0 bl-0 br-0 f6">
          Edit
        </div>
      </li>
      <li class="bb pa2 flex items-center relative">
        <span class="mr3 tc">
          <span class="db mb1">Feb 25</span>
          <span class="f6">2020</span>
        </span>
        <div>
          <span class="mb1 db">Anniversary of the day you first met</span>
          <div class="f6">
            <span class="dib mr2">Every week</span>
            <span>Long description about the thing</span>
          </div>
        </div>
        <div class="absolute right-1 pointer bb b--dotted bt-0 bl-0 br-0 f6">
          Edit
        </div>
      </li>
    </ul>

    <!-- view more link -->
    <div class="pa2 tc">
      <inertia-link :href="''" class="">View all the reminders</inertia-link>
    </div>
  </div>
</template>

<script>
import FormInput from '@/Shared/Input';
import LoadingButton from '@/Shared/LoadingButton';
import Errors from '@/Shared/Errors';

export default {
  components: {
    FormInput,
    LoadingButton,
    Errors
  },

  props: {
    contact: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      editMode: false,
      form: {
        street: null,
        city: null,
        province: null,
        postal_code: null,
        country: null,
        latitude: null,
        longitude: null,
        errors: [],
      },
      loadingState: '',
    };
  },

  created: function() {
  },

  methods: {
  }
};
</script>
