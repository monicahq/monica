<style scoped>
  .photo {
      height: 200px;
  }
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
          <form-input
            :id="'name'"
            v-model="newGift.name"
            :input-type="'text'"
            :maxlength="255"
            :required="true"
            :class="'dtc pr2'"
            :title="$t('people.gifts_add_gift_name')"
            :validator="$v.newGift.name"
            @submit="store"
          />
        </div>

        <!-- ADDITIONAL FIELDS -->
        <div v-show="displayMenu" class="bb b--gray-monica pv3 mb3">
          <ul class="list">
            <li v-show="!displayComment" class="di pointer" :class="dirltr ? 'mr3' : 'ml3'">
              <a href="" @click.prevent="displayComment = true">{{ $t('people.gifts_add_comment') }}</a>
            </li>
            <li v-show="!displayUrl" class="di pointer" :class="dirltr ? 'mr3' : 'ml3'">
              <a href="" @click.prevent="displayUrl = true">{{ $t('people.gifts_add_link') }}</a>
            </li>
            <li v-show="!displayAmount" class="di pointer" :class="dirltr ? 'mr3' : 'ml3'">
              <a href="" @click.prevent="displayAmount = true; newGift.amount = 0;">{{ $t('people.gifts_add_value') }}</a>
            </li>
            <li v-if="familyContacts.length > 0" v-show="!displayRecipient" class="di pointer" :class="dirltr ? 'mr3' : 'ml3'">
              <a href="" @click.prevent="displayRecipient = true">{{ $t('people.gifts_add_recipient') }}</a>
            </li>
            <li v-if="!reachLimit" v-show="!displayUpload" class="di pointer" :class="dirltr ? 'mr3' : 'ml3'">
              <a href="" @click.prevent="() => { displayUpload = true; $refs.upload.showUploadZone(); }">{{ $t('people.gifts_add_photo') }}</a>
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
            :placeholder="'https://'"
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
            :required="displayAmount"
            @submit="store"
          />
        </div>

        <div v-if="displayRecipient" class="dt dt--fixed pb3 mb3 bb b--gray-monica">
          <!-- RECIPIENT -->
          <form-checkbox
            v-model="hasRecipient"
            :name="'has_recipient'"
            @change="(val) => { if (val) { $refs.recipient.focus() } }"
          >
            {{ $t('people.gifts_add_someone', {name: ''}) }}
          </form-checkbox>
          <form-select
            ref="recipient"
            v-model="newGift.recipient_id"
            :label="$t('people.gifts_add_recipient_field')"
            :options="familyContacts"
            :validator="$v.newGift.recipient_id"
            @input="hasRecipient = true"
          />
        </div>

        <div v-show="displayUpload" class="dt dt--fixed pb3 mb3 bb b--gray-monica">
          <span class="mb2 b">
            {{ $t('people.gifts_add_photo_title') }}
          </span>

          <photo-upload
            v-show="photos.length == 0"
            ref="upload"
            :contact-id="contactId"
            @upload.stop="handlePhoto($event)"
          />

          <!-- LIST OF PHOTO -->
          <div v-show="photos.length > 0">
            <div class="flex flex-wrap">
              <div v-for="photo in photos" :key="photo.id" class="w-third-ns w-100">
                <div v-if="photo.id > 0" class="pa2 mb3 br2 ba b--gray-monica" :class="dirltr ? 'mr3' : 'ml3'">
                  <div class="cover bg-center photo w-100 h-100 br2 bb b--gray-monica pb2"
                       :style="'background-image: url(' + photo.link + ');'"
                  >
                  </div>
                  <div class="pt2">
                    <ul>
                      <li>
                        <a class="pointer" href="" @click.prevent="deletePhoto(photo)">
                          {{ $t('people.photo_delete') }}
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>
                <div v-else class="ba br3 photo-upload-zone mb3 pa3">
                  <div class="tc dib w-100 relative">
                    {{ $tc('app.file_selected', photos.length, {count: photos.length}) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
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
import PhotoUpload from '../photo/PhotoUpload.vue';
import { validationMixin } from 'vuelidate';
import { required, maxLength } from 'vuelidate/lib/validators';

export default {
  components: {
    Error,
    PhotoUpload
  },

  mixins: [validationMixin],

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
    reachLimit: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      photos: [],
      displayComment: false,
      displayUrl: false,
      displayAmount: false,
      displayRecipient: false,
      displayUpload: false,
      newGift: {
        name: '',
        status: 'idea',
        comment: null,
        url: null,
        amount: null,
        date: null,
        recipient_id: null,
        photo_id: null,
      },
      hasRecipient: false,
      errors: [],
    };
  },

  validations() {
    var v = {
      newGift: {
        name: {
          required,
          maxLength: maxLength(255),
        },
      }
    };

    if (this.hasRecipient) {
      v.newGift = Object.assign(v.newGift, {
        recipient_id: {
          required,
        }
      });
    }

    return v;
  },

  computed: {
    locale() {
      return this.$root.locale;
    },

    dirltr() {
      return this.$root.htmldir == 'ltr';
    },

    displayMenu() {
      return !this.displayComment ||
        !this.displayUrl ||
        !this.displayAmount ||
        !(this.displayRecipient || this.familyContacts.length == 0) ||
        !(this.displayUpload || this.reachLimit);
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
        this.newGift.recipient_id = this.gift.recipient ? this.gift.recipient.id : null;
        this.hasRecipient = this.newGift.recipient_id != null;
        this.newGift.date = this.gift.date;
        this.photos = this.gift.photos;
      } else {
        this.newGift.name = '';
        this.newGift.comment = null;
        this.newGift.url = null;
        this.newGift.amount = null;
        this.newGift.status = 'idea';
        this.newGift.recipient_id = null;
        this.newGift.date = null;
        this.hasRecipient = false;
      }
      this.displayComment = this.gift ? this.gift.comment : false;
      this.displayUrl = this.gift ? this.gift.url : false;
      this.displayAmount = this.gift ? this.gift.amount : false;
      this.displayRecipient = this.gift ? (this.gift.recipient ? this.gift.recipient.id !== 0 : false) : false;
      this.displayUpload= this.gift ? this.gift.photos.length > 0 : false;

      this.errors = [];
      this.$v.$reset();
    },

    close() {
      this.resetFields();
      this.$emit('cancel');
    },

    store() {
      if (! this.hasRecipient) {
        this.newGift.recipient_id = null;
      }

      this.$v.$touch();

      if (this.$v.$invalid) {
        return;
      }

      let method = this.gift ? 'put' : 'post';
      let url = this.gift ? 'api/gifts/'+this.gift.id : 'api/gifts';

      let vm = this;
      axios[method](url, this.newGift)
        .then(response => {
          return vm.storePhoto(response);
        })
        .then(response => {
          vm.close();
          vm.$emit('update', response.data.data);
          return response;
        })
        .then(() => {
          this.$notify({
            group: 'main',
            title: vm.$t('people.gifts_add_success'),
            text: '',
            type: 'success'
          });
        })
        .catch(error => {
          vm._errorHandle(error);
        });
    },

    storePhoto(response) {
      let vm = this;
      return this.$refs.upload.forceFileUpload()
        .then(photo => {
          if (photo !== undefined) {
            axios.put('api/gifts/'+response.data.data.id+'/photo/'+photo.id);
            response.data.data.photos.push(photo);
          }
          return response;
        })
        .catch(error => {
          vm._errorHandle(error);
          return response;
        });
    },

    _errorHandle(error) {
      if (error.response && typeof error.response.data === 'object') {
        this.errors = _.flatten(_.toArray(error.response.data));
      } else {
        this.errors = [vm.$t('app.error_try_again'), error.message];
      }
    },

    deletePhoto(photo) {
      axios.delete('api/photos/' + photo.id)
        .then(response => {
          this.photos.splice(this.photos.indexOf(photo), 1);
          if (this.photos.length == 0) {
            this.$refs.upload.showUploadZone();
          }
        });
    },

    handlePhoto(event) {
      this.photos.push({ id: -1, link: '' });
    },
  }
};
</script>
