<style scoped style="scss">
div >>> .avatar-small {
  height: 30px;
  width: 30px;
  font-size: 13px;
  border-radius: 0.5rem;
}
</style>

<template>
  <div>
    <div v-if="!limited" class="flex fr">
      <a class="btn btn-primary" href="" @click.prevent="openModal">{{ $t('settings.me_select') }}</a>
    </div>
    <div v-if="meContact" class="dib pointer ml2 fl collapse">
      <span class="dt-row">
        <span class="dtc">
          <avatar :contact="meContact" :clickable="true" :imgclass="'avatar-small br1'" />
        </span>
        <span class="dtc">
          <a :href="'people/' + meContact.hash_id" class="avatar-small" :class="dirltr ? 'ml1' : 'mr1'">
            {{ meContact.complete_name }}
          </a>
        </span>
      </span>
    </div>
    <div v-else class="dib pointer fl">
      {{ $t('settings.me_no_contact') }}<br />
      <a v-if="!limited" href="" @click.prevent="openModal">{{ $t('settings.me_select_click') }}</a>
      <div v-else v-html="$t('settings.personalisation_paid_upgrade_vue', {url: 'settings/subscriptions' })"></div>
    </div>
    <div class="cb"></div>

    <sweet-modal ref="modal" overlay-theme="dark" :title="$t('settings.me_select')">
      <form>
        <contact-select
          v-model="newContact"
          :required="true"
          :title="$t('settings.me_choose')"
          :name="'me_contact_id'"
          :placeholder="$t('settings.me_choose_placeholder')"
          :default-options="existingContacts"
        />
      </form>
      <div slot="button">
        <a class="btn fl" href="" @click.prevent="remove">
          {{ $t('settings.me_remove_contact') }}
        </a>
        <a class="btn" href="" @click.prevent="closeModal">
          {{ $t('app.cancel') }}
        </a>
        <a class="btn btn-primary" href="" @click.prevent="save">
          {{ $t('app.save') }}
        </a>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
import { SweetModal } from 'sweet-modal-vue';

export default {
  components: {
    SweetModal,
  },

  props: {
    contact: {
      type: Object,
      default: null,
    },
    existingContacts: {
      type: Array,
      default: () => [],
    },
    limited: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      meContact: null,
      newContact: null
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    }
  },

  watch: {
    contact(value) {
      this.newContact = value;
    }
  },

  mounted() {
    this.meContact = this.contact;
    this.newContact = this.contact;
  },

  methods: {
    save() {
      axios.post('me/contact', {
        contact_id: this.newContact.id
      })
        .then(response => {
          this.$emit('change', this.newContact);
          this.meContact = this.newContact;
          this.closeModal();
        });
    },

    remove() {
      this.newContact = null;
      axios.delete('me/contact')
        .then(response => {
          this.$emit('change', null);
          this.meContact = null;
          this.closeModal();
        });
    },

    openModal() {
      this.$refs.modal.open();
    },

    closeModal() {
      this.$refs.modal.close();
    }
  }
};
</script>
