<style scoped>
</style>

<template>
  <div>
    <!-- Add a gift -->
    <transition name="fade">
      <div class="ba br3 mb3 pa3 b--black-40">
        <div class="pb3 mb3 flex-ns b--gray-monica">
          <!-- STATUS -->
          <form-radio
            :id="'status_idea'"
            v-model="newGift.status"
            :name="'status'"
            :required="true"
            :value="'idea'"
            :color="'success'"
            :full-class="'p-default p-fill p-curve'"
          >
            {{ $t('people.gifts_add_gift_idea') }}
          </form-radio>

          <form-radio
            :id="'status_offered'"
            v-model="newGift.status"
            :name="'status'"
            :required="true"
            :value="'offered'"
            :color="'info'"
            :full-class="'p-default p-fill p-curve'"
          >
            {{ $t('people.gifts_add_gift_already_offered') }}
          </form-radio>

          <form-radio
            :id="'status_received'"
            v-model="newGift.status"
            :name="'status'"
            :required="true"
            :value="'received'"
            :color="'warning'"
            :full-class="'p-default p-fill p-curve'"
          >
            {{ $t('people.gifts_add_gift_received') }}
          </form-radio>
        </div>

        <div class="dt dt--fixed pb3 mb3 mb0-ns bb b--gray-monica">
          <!-- NAME -->
          <div class="dtc pr2">
            <p class="mb2 b">
              Gift name
            </p>
            <form-input
              :id="'name'"
              v-model="newGift.name"
              :input-type="'text'"
              :maxlength="255"
              :required="true"
              @submit="store"
            />
          </div>
        </div>

        <!-- ADDITIONAL FIELDS -->
        <div v-show="!displayComment || !displayUrl || !displayAmount || !displayRecipient || familyContacts.length > 0" class="bb b--gray-monica pv3 mb3">
          <ul class="list">
            <li v-show="!displayComment" class="di pointer" :class="dirltr ? 'mr3' : ''">
              <a href="" @click.prevent="displayComment = true">{{ $t('people.gifts_add_comment') }}</a>
            </li>
            <li v-show="!displayUrl" class="di pointer" :class="dirltr ? 'mr3' : 'ml3'">
              <a href="" @click.prevent="displayUrl = true">{{ $t('people.gifts_add_link') }}</a>
            </li>
            <li v-show="!displayAmount" class="di pointer" :class="dirltr ? 'mr3' : 'ml3'">
              <a href="" @click.prevent="displayAmount = true; newGift.amount = 0;">{{ $t('people.gifts_add_value') }}</a>
            </li>
            <li v-if="familyContacts.length > 0" v-show="!displayRecipient" class="di pointer" :class="dirltr ? '' : 'ml3'">
              <a href="" @click.prevent="displayRecipient = true">{{ $t('people.gifts_add_recipient') }}</a>
            </li>
          </ul>
        </div>

        <div v-if="displayComment" class="dt dt--fixed pb3 mb3 bb b--gray-monica">
          <!-- COMMENT -->
          <form-input
            :id="'comment'"
            v-model="newGift.comment"
            :input-type="'text'"
            :class="'dtc pr2'"
            :title="$t('people.gifts_add_comment')"
            @submit="store"
          />
        </div>

        <div v-if="displayUrl" class="dt dt--fixed pb3 mb3 bb b--gray-monica">
          <!-- URL -->
          <form-input
            :id="'url'"
            v-model="newGift.url"
            :input-type="'text'"
            :class="'dtc pr2'"
            :title="$t('people.gifts_add_link')"
            @submit="store"
          />
        </div>

        <div v-if="displayAmount" class="dt dt--fixed pb3 mb3 bb b--gray-monica">
          <!-- AMOUNT -->
          <form-input
            :id="'amount'"
            v-model="newGift.amount"
            :input-type="'number'"
            :class="'dtc pr2'"
            :title="$t('people.gifts_add_value')"
            @submit="store"
          />
        </div>

        <div v-if="displayRecipient" class="dt dt--fixed pb3 mb3 bb b--gray-monica">
          <!-- RECIPIENT -->
          <form-checkbox
            v-model="hasRecipient"
            :name="'has_recipient'"
            @change="$refs.recipient.focus()"
          >
            {{ $t('people.gifts_add_someone', {name: ''}) }}
          </form-checkbox>
          <form-select
            ref="'recipient'"
            v-model="newGift.recipient"
            :options="familyContacts"
            @input="hasRecipient = true"
          />
        </div>

        <error :errors="errors" />

        <!-- ACTIONS -->
        <div class="pt3">
          <div class="flex-ns justify-between">
            <div class="">
              <a class="btn btn-secondary tc w-auto-ns w-100 mb2 pb0-ns" @click.prevent="close">
                {{ $t('app.cancel') }}
              </a>
            </div>
            <div class="">
              <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" @click.prevent="store">
                {{ gift ? $t('app.update') : $t('app.add') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import Error from '../../partials/Error.vue';

export default {
  components: {
    Error
  },

  props: {
    contactId: {
      type: Number,
      default: 0,
    },
    gift: {
      type: Object,
      default: null,
    },
    familyContacts: {
      type: Array,
      default: () => [],
    },
  },

  data() {
    return {
      displayComment: false,
      displayUrl: false,
      displayAmount: false,
      displayRecipient: false,
      newGift: {
        name: '',
        status: 'idea',
        comment: null,
        url: null,
        amount: null,
        date: null,
        recipient_id: null,
      },
      hasRecipient: false,
      errors: [],
    };
  },

  computed: {
    locale() {
      return this.$root.locale;
    },

    dirltr() {
      return this.$root.htmldir == 'ltr';
    }
  },

  mounted() {
    this.resetFields();
  },

  methods: {
    resetFields() {
      this.newGift.contact_id = this.contactId;
      if (this.gift) {
        this.newGift.contact_id = this.gift.contact.id;
        this.newGift.name = this.gift.name;
        this.newGift.comment = this.gift.comment;
        this.newGift.url = this.gift.url;
        this.newGift.amount = this.gift.amount;
        this.newGift.status = this.gift.status;
        this.newGift.recipient_id = this.gift.recipient_id;
        this.newGift.date = this.gift.date;
      } else {
        this.newGift.name = '';
        this.newGift.comment = null;
        this.newGift.url = null;
        this.newGift.amount = null;
        this.newGift.status = 'idea';
        this.newGift.recipient_id = null;
        this.newGift.date = null;
      }
      this.displayComment = this.gift ? this.gift.comment : false;
      this.displayUrl = this.gift ? this.gift.url : false;
      this.displayAmount = this.gift ? this.gift.amount : false;
      this.displayRecipient = this.gift ? this.gift.recipient_id : false;

      this.errors = [];
    },

    close() {
      this.resetFields();
      this.$emit('cancel');
    },

    store() {
      if (! this.hasRecipient) {
        this.newGift.recipient_id = null;
      }
      let method = this.gift ? 'put' : 'post';
      let url = this.gift ? 'api/gifts/'+this.gift.id : 'api/gifts';
      axios[method](url, this.newGift)
        .then(response => {
          this.close();
          this.$emit('update', response.data.data);

          this.$notify({
            group: 'main',
            title: this.$t('people.gift_add_success'),
            text: '',
            type: 'success'
          });
        })
        .catch(error => {
          this.errors = _.flatten(_.toArray(error.response.data));
        });
    },
  }
};
</script>
