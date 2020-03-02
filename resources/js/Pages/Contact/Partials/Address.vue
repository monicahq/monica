<style lang="scss" scoped>
</style>

<template>
  <!-- list of addresses -->
  <div class="pa2">

    <!-- non-edit mode -->
    <template v-if="!editMode">
      <p class="mb1 f6 mt0">
        {{ $t('people.contact_address_title') }}
        <span class="fr pointer bb b--dotted bt-0 bl-0 br-0" @click.prevent="editMode = true">{{ $t('app.edit') }}</span>
      </p>

      <!-- list of addresses -->
      <ul class="ma0 pl0 list">
        <li v-for="address in contact.addresses" :key="address.id">
          {{ address.full }} <a :href="address.google_map_link">Map</a>
        </li>
      </ul>
    </template>

    <!-- form to edit the work information -->
    <template v-if="editMode">
      <form @submit.prevent="submit">
        <errors :errors="form.errors" :classes="'mb3'" />

        <form-input
          v-model="form.street"
          v-on:escape="editMode = false"
          :id="'title'"
          :input-type="'text'"
          :required="false"
          :label-class="'db mb2'"
          :input-class="'db mb2'"
          :title="$t('people.contact_address_form_street')">
        </form-input>

        <div class="dt-ns dt--fixed di mb2">
          <div class="dtc-ns pr2-ns pb0-ns w-100 pb3">
            <form-input
              v-model="form.province"
              v-on:escape="editMode = false"
              :id="'title'"
              :input-type="'text'"
              :required="false"
              :label-class="'db mb2'"
              :input-class="'db'"
              :title="$t('people.contact_address_form_province')">
            </form-input>
          </div>
          <div class="dtc-ns pb0-ns w-100">
            <form-input
              v-model="form.postal_code"
              v-on:escape="editMode = false"
              :id="'title'"
              :input-type="'text'"
              :required="false"
              :label-class="'db mb2'"
              :input-class="'db'"
              :title="$t('people.contact_address_form_postal_code')">
            </form-input>
          </div>
        </div>

        <div class="dt-ns dt--fixed di mb2">
          <div class="dtc-ns pr2-ns pb0-ns w-100 pb3">
            <form-input
              v-model="form.city"
              v-on:escape="editMode = false"
              :id="'title'"
              :input-type="'text'"
              :required="false"
              :label-class="'db mb2'"
              :input-class="'db'"
              :title="$t('people.contact_address_form_city')">
            </form-input>
          </div>
          <div class="dtc-ns pb0-ns w-100">
            <form-input
              v-model="form.country"
              v-on:escape="editMode = false"
              :id="'title'"
              :input-type="'text'"
              :required="false"
              :label-class="'db mb2'"
              :input-class="'db'"
              :title="$t('people.contact_address_form_country')">
            </form-input>
          </div>
        </div>

        <!-- Actions -->
        <div class="">
          <div class="flex-ns justify-between">
            <div>
            </div>
            <div class="">
              <a class="btn dib tc w-auto-ns w-100 mb2 pv2 ph3 pointer" data-cy="cancel-add-work-information" @click="editMode = false">{{ $t('app.cancel') }}</a>
              <loading-button :classes="'btn add w-auto-ns w-100 mb2 pv2 ph3'" :state="loadingState" :text="$t('app.save')" :cypress-selector="'submit-add-work-information'" />
            </div>
          </div>
        </div>
      </form>
    </template>

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
