<style lang="scss" scoped>
</style>

<template>
  <!-- list of contact information -->
  <div>
    <h2 class="ma0 bb pa2 fw4 f5">
      Contact information
      <span class="fr pointer bb b--dotted bt-0 bl-0 br-0 f6" @click.prevent="editMode = true" v-if="!editMode">{{ $t('app.edit') }}</span>
      <span class="fr pointer bb b--dotted bt-0 bl-0 br-0 f6" @click.prevent="editMode = false" v-if="editMode">{{ $t('app.close') }}</span>
    </h2>

    <!-- non edit mode -->
    <template v-if="!editMode">
      <div class="flex flex-wrap">
        <span class="pa2">
          <a href="">https://facebook.com/regis</a>
        </span>
        <span class="pa2">
          <a href="">+ 3483 384384 8234</a>
        </span>
      </div>
    </template>

    <!-- edit mode -->
    <template v-if="editMode">
      <form @submit.prevent="submit">
        <errors :classes="'mb3'" />

        <!-- list of exiting items -->
        <div class="flex items-center pa2">
          <div class="w-100">
            <form-select
              :id="''"
              ref=""
              :options="''"
              :label="'Content type'"
              :custom-class="'db w-90'"
            />
          </div>
          <div class="w-100">
            <form-input
              v-on:escape="editMode = false"
              :id="'title'"
              :type="'text'"
              :required="false"
              :label-class="'db mb2'"
              :input-class="'db'"
              :title="'Content'">
            </form-input>
          </div>
          <a href="">Delete</a>
        </div>

        <div class="flex items-center pa2">
          <div class="w-100">
            <form-select
              :id="''"
              ref=""
              :options="''"
              :label="'Content type'"
              :custom-class="'db w-90'"
            />
          </div>
          <div class="w-100">
            <form-input
              v-on:escape="editMode = false"
              :id="'title'"
              :type="'text'"
              :required="false"
              :label-class="'db mb2'"
              :input-class="'db'"
              :title="'Content'">
            </form-input>
          </div>
          <a href="">Delete</a>
        </div>

        <!-- add a new contact information link -->
        <div class="pa2">
          <a href="" class="dib pointer bb b--dotted bt-0 bl-0 br-0 no-underline">Add a new way of contacting Pierre</a>
        </div>
      </form>
    </template>
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
    };
  },

  created: function() {
  },

  methods: {
  }
};
</script>
