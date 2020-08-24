<style scoped>
.photo {
  height: 250px;
}
</style>

<template>
  <div>
    <div class="">
      <h3>
        📄 {{ $t('people.photo_list_title') }}
        <span v-if="reachLimit == 'false'" class="fr relative" style="top: -7px;">
          <a v-if="!onUpload" class="btn" href=""
             @click.prevent="() => { onUpload = true; $refs.upload.showUploadZone(); }"
          >
            {{ $t('people.photo_list_cta') }}
          </a>
          <a v-else class="btn" href=""
             @click.prevent="() => { onUpload = false; $refs.upload.cancelUpload(); }"
          >
            {{ $t('app.cancel') }}
          </a>
        </span>
      </h3>
    </div>

    <p v-show="reachLimit == 'true'">
      {{ $t('settings.storage_upgrade_notice') }}
    </p>

    <!-- EMPTY STATE -->
    <div v-if="!onUpload && photos.length == 0" class="ltr w-100 pt2">
      <div class="section-blank">
        <h3 class="mb4 mt3">
          {{ $t('people.photo_list_blank_desc') }}
        </h3>
        <img src="img/people/photos/photos_empty.svg" :alt="$t('people.photo_title')" class="w-50 center" />
      </div>
    </div>

    <photo-upload
      ref="upload"
      :contact-id="contactId"
      @newphoto="handleNewPhoto($event)"
    />

    <!-- LIST OF PHOTO -->
    <div class="db mt3">
      <div class="flex flex-wrap">
        <div v-for="photo in photos" :key="photo.id" class="w-third-ns w-100 pointer">
          <div class="pa3 mb3 br2 ba b--gray-monica" :class="dirltr ? 'mr3' : 'ml3'">
            <div class="cover bg-center photo w-100 h-100 br2 bb b--gray-monica pb2"
                 :style="'background-image: url(' + photo.link + ');'"
                 @click.prevent="modalPhoto(photo)"
            >
            </div>
            <div class="pt2">
              <ul>
                <li v-show="currentPhotoIdAsAvatar == photo.id">
                  🤩 {{ $t('people.photo_current_profile_pic') }}
                </li>
                <li v-show="currentPhotoIdAsAvatar != photo.id">
                  <a class="pointer" @click.prevent="makeProfilePicture(photo)">
                    {{ $t('people.photo_make_profile_pic') }}
                  </a>
                </li>
                <li v-show="confirmDestroyPhotoId != photo.id">
                  <a class="pointer" href="" @click.prevent="confirmDestroyPhotoId = photo.id">
                    {{ $t('people.photo_delete') }}
                  </a>
                </li>
                <li v-show="confirmDestroyPhotoId == photo.id">
                  <a class="pointer" href="" @click.prevent="confirmDestroyPhotoId = 0">
                    {{ $t('app.cancel') }}
                  </a> <a class="pointer" href="" @click.prevent="deletePhoto(photo)">
                    {{ $t('app.delete_confirm') }}
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL ZOOM PHOTO -->
    <transition v-if="showModal" name="modal">
      <div class="modal-mask">
        <div class="modal-wrapper">
          <div class="modal-container">

            <img :src="url" :alt="$t('people.photo_title')" class="mw-90 h-auto mb3" />

            <ul class="list pl0 tc">
              <li class="di mr3"><a class="pointer" @click="displayPrev" v-if="canShowPrev">< {{ $t('people.photo_previous') }}</a></li>
              <li class="di mr3"><button class="btn" @click="showModal = false">{{ $t('app.close') }}</button></li>
              <li class="di"><a class="pointer" @click="displayNext" v-if="canShowNext">{{ $t('people.photo_next') }} ></a></li>
            </ul>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>

import PhotoUpload from './PhotoUpload.vue';

export default {

  components: {
    PhotoUpload,
  },

  props: {
    hash: {
      type: String,
      default: '',
    },
    contactId: {
      type: Number,
      default: 0,
    },
    reachLimit: {
      type: String,
      default: '',
    },
    currentPhotoIdAsAvatar: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      photos: [],
      file: '',
      uploadPercentage: 0,
      confirmDestroyPhotoId: 0,
      showModal: false,
      url: '',
      onUpload: false,
      canShowPrev: false,
      canShowNext: false
    };
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {

    prepareComponent() {
      this.getPhotos();
    },

    getPhotos() {
      axios.get('people/' + this.hash + '/photos')
        .then(response => {
          this.photos = response.data.data;
        });
    },

    handleNewPhoto(photo) {
      this.$notify({
        group: 'main',
        title: this.$t('app.default_save_success'),
        text: '',
        type: 'success'
      });

      this.photos.push(photo);
    },

    deletePhoto(photo) {
      axios.delete( 'people/' + this.hash + '/photos/' + photo.id)
        .then(response => {
          this.photos.splice(this.photos.indexOf(photo), 1);
          this.$notify({
            group: 'main',
            title: this.$t('app.default_save_success'),
            text: '',
            type: 'success'
          });
        })
        .catch(error => {
        });
    },

    makeProfilePicture(photo) {
      axios.post( 'people/' + this.hash + '/makeProfilePicture/' + photo.id)
        .then(response => {
          window.location.href = 'people/' + this.hash;
        });
    },

    modalPhoto(photo) {
      this.modal_photo = photo;
      this.url = photo.link;

      if(this.modalHasNext()) {
        this.canShowNext = true;
      } else {
        this.canShowNext = false;
      }

      if(this.modalHasPrev()) {
        this.canShowPrev = true;
      } else {
        this.canShowPrev = false;
      }

      this.showModal = true;
    },

    /**
     * checks whether the there is a photo to the left of the current
     * photo shown in the modal
     *
     * @return {Boolean}
     */
    modalHasNext() {
      let index = this.photos.indexOf(this.modal_photo);

      return index < this.photos.length-1;
    },

    /**
     * checks whether the there is a photo to the right of the current
     * photo shown in the modal
     *
     * @return {Boolean}
     */
    modalHasPrev() {
      let index = this.photos.indexOf(this.modal_photo);

      return index > 0;
    },

    /**
     * set photo in modal located to the right of current modal photo
     * from our photos list
     */
    displayNext() {
      let index = this.photos.indexOf(this.modal_photo);

      let photo = this.photos[index+1];

      this.modalPhoto(photo);
    },

    /**
     * set photo in modal located to the left of current modal photo
     * from our photos list
     */
    displayPrev() {
      let index = this.photos.indexOf(this.modal_photo);

      let photo = this.photos[index-1];

      this.modalPhoto(photo);
    }
  }
};
</script>
