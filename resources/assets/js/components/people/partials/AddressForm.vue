<style scoped>
</style>

<template>
  <div>
    <form
      class="measure center"
      @keyup.enter="event => { $emit('submit', event) }"
    >
      <div class="mt3">
        <form-input
          id="name"
          v-model="name"
          :title="$t('people.contact_address_form_name')"
          input-type="text"
          :required="false"
          :iclass="'pa2 db w-100'"
        />
      </div>
      <div class="mt3">
        <form-input
          id="street"
          v-model="street"
          :title="$t('people.contact_address_form_street')"
          input-type="text"
          :required="false"
        />
      </div>
      <div class="mt3">
        <form-input
          id="city"
          v-model="city"
          :title="$t('people.contact_address_form_city')"
          input-type="text"
          :required="false"
        />
      </div>
      <div class="mt3">
        <form-input
          id="province"
          v-model="province"
          :title="$t('people.contact_address_form_province')"
          input-type="text"
          :required="false"
        />
      </div>
      <div class="mt3">
        <form-input
          id="postal_code"
          v-model="postalCode"
          :title="$t('people.contact_address_form_postal_code')"
          input-type="text"
          :required="false"
        />
      </div>
      <div class="mt3">
        <form-select
          id="name"
          v-model="country"
          :title="$t('people.contact_address_form_country')"
          :options="countries"
          :required="false"
        />
      </div>
      <div class="mt3">
        <form-input
          id="latitude"
          v-model="latitude"
          :title="$t('people.contact_address_form_latitude')"
          input-type="number"
          :required="false"
        />
      </div>
      <div class="mt3">
        <form-input
          id="longitude"
          v-model="longitude"
          :title="$t('people.contact_address_form_latitude')"
          input-type="number"
          :required="false"
        />
      </div>
      <div class="lh-copy mt3">
        <a v-if="mode == 'edit'"
           class="btn btn-primary"
           @click.prevent="event => { $emit('submit', event) }"
        >
          {{ $t('app.save') }}
        </a>
        <a v-else
           class="btn btn-primary"
           @click.prevent="event => { $emit('submit', event) }"
        >
          {{ $t('app.add') }}
        </a>
        <a class="btn"
           @click.prevent="event => { $emit('cancel', event) }"
        >
          {{ $t('app.cancel') }}
        </a>
      </div>
    </form>
  </div>
</template>

<script>
export default {

  props: {
    name: {
      type: String,
      default: '',
    },
    street: {
      type: String,
      default: '',
    },
    city: {
      type: String,
      default: '',
    },
    province: {
      type: String,
      default: '',
    },
    postalCode: {
      type: String,
      default: '',
    },
    country: {
      type: String,
      default: '',
    },
    latitude: {
      type: Number,
      default: 0,
    },
    longitude: {
      type: Number,
      default: 0,
    },
    countries: {
      type: Array,
      default: function() {
        return [];
      }
    },
    mode: {
      type: String,
      default: 'add',
    }
  },

  watch: {
    name: function(value) {
      this.$emit('update:name', value);
    },
    street: function(value) {
      this.$emit('update:street', value);
    },
    city: function(value) {
      this.$emit('update:city', value);
    },
    province: function(value) {
      this.$emit('update:province', value);
    },
    postalCode: function(value) {
      this.$emit('update:postalCode', value);
    },
    country: function(value) {
      this.$emit('update:country', value);
    },
    latitude: function(value) {
      this.$emit('update:latitude', value);
    },
    longitude: function(value) {
      this.$emit('update:longitude', value);
    },
  }

};
</script>
