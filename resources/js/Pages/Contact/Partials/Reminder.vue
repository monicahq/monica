<style lang="scss" scoped>
</style>

<template>
  <!-- list of reminders -->
  <div class="">
    <!-- summary and call to action -->
    <div class="flex justify-between pa2" v-show="!editMode">
      <p class="mv0">Showing 5 out of 13 reminders</p>
      <a :href="''" class="mb1 db btn btn-primary" @click.prevent="editMode = true">Add a new reminder</a>
    </div>

    <!-- add a new reminder -->
    <template v-if="editMode">
      <form @submit.prevent="submit" class="pa2">
        <errors :errors="form.errors" :classes="'mb3'" />

        <form-input
          v-on:escape="editMode = false"
          :id="'title'"
          :type="'text'"
          :required="true"
          :label-class="'db mb2'"
          :input-class="'db mb3'"
          :title="'What should we remind you about?'">
        </form-input>

        <form-input
          v-on:escape="editMode = false"
          :id="'title'"
          :type="'date'"
          :required="true"
          :label-class="'db mb2'"
          :input-class="'db mb3'"
          :title="'What is the date of this event? It can be in the past.'">
        </form-input>

        What is

        <!-- Actions -->
        <div class="">
          <loading-button :classes="'btn add w-auto-ns w-100 mb2 pv2 ph3'" :state="loadingState" :text="$t('app.save')" :cypress-selector="'submit-add-work-information'" />
          <a class="btn dib tc w-auto-ns w-100 mb2 pv2 ph3 pointer" data-cy="cancel-add-work-information" @click="editMode = false">{{ $t('app.cancel') }}</a>
        </div>
      </form>
    </template>

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
import FormSelect from '@/Shared/Select';
import LoadingButton from '@/Shared/LoadingButton';
import Errors from '@/Shared/Errors';

export default {
  components: {
    FormInput,
    FormSelect,
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
      loadingState: '',
      form: {
        errors: [],
      },
    };
  },

  created: function() {
  },

  methods: {
  }
};
</script>
