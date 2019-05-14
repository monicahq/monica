<style scoped>
    .photo {
        height: 250px;
    }
     .photo-upload-zone {
        background: #fff;
        border: 1px solid #d6d6d6;
        border-style: dashed;
    }
     progress {
        -webkit-appearance: none;
        border: none;
        height: 8px;
        margin-bottom: 3px;
        width: 60%;
    }
     progress::-webkit-progress-bar {
        background: #e2e7ee;
        border-radius: 3px;
    }
     progress::-webkit-progress-value {
        border-radius: 3px;
        box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.4);
        background-color: #329FF1;
    }
</style>
<template>
  <div>
    <div class="">
      <h3>
        📄 {{ $t('people.photo_list_title') }}
        <span v-show="reachLimit == 'false'" class="fr relative" style="top: -7px;">
          <a v-if="displayUploadZone == false && displayUploadError == false && displayUploadProgress == false" class="btn" href="" @click.prevent="displayUploadZone = true">
            {{ $t('people.photo_list_cta') }}
          </a>
          <a v-if="displayUploadZone || displayUploadError || displayUploadProgress" class="btn" href="" @click.prevent="displayUploadZone = false; displayUploadError = false; displayUploadProgress = false">
            {{ $t('app.cancel') }}
          </a>
        </span>
      </h3>
    </div>
    <p v-show="reachLimit == 'true'">
      {{ $t('settings.storage_upgrade_notice') }}
    </p>
    <!-- EMPTY STATE -->
    <div v-if="displayUploadZone == false && displayUploadError == false && displayUploadProgress == false && photos.length == 0" class="ltr w-100 pt2">
      <div class="section-blank">
        <h3 class="mb4 mt3">
          {{ $t('people.photo_list_blank_desc') }}
        </h3>
        <img src="img/people/photos/photos_empty.svg" class="w-50 center" />
      </div>
    </div>
    <!-- FIRST STEP OF PHOTO UPLOAD -->
    <transition name="fade">
      <div v-if="displayUploadZone" class="ba br3 photo-upload-zone mb3 pa3">
        <div class="tc">
        </div>
        <div class="tc dib w-100 relative">
          <button class="btn">
            {{ $t('people.photo_upload_zone_cta') }}
          </button>
          <input id="file" ref="file" type="file" class="absolute o-0 w-100 h-100 pointer" style="left:0;"
                 @change="handleFileUpload()"
          />
        </div>
      </div>
    </transition>
    <!-- LAST STEP OF PHOTO UPLOAD -->
    <div v-if="displayUploadProgress" class="ba br3 photo-upload-zone mb3 pa3">
      <p class="tc mb1">
        {{ $t('people.document_upload_zone_progress') }}
      </p>
      <div class="tc mb1">
        <progress max="100" :value.prop="uploadPercentage"></progress>
      </div>
      <p class="tc f6 mb0">
        {{ $t('app.percent_uploaded', {percent: uploadPercentage}) }}
      </p>
    </div>
    <!-- ERROR STEP WHEN UPLOADING A PHOTO -->
    <transition name="fade">
      <div v-if="displayUploadError" class="ba br3 photo-upload-zone mb3 pa3">
        <div class="tc">
          <svg width="120" height="150" viewBox="0 0 120 150" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M27.2012 35.9138V101.562H92.7999V18.4375H44.6762L27.2012 35.9138ZM44.1274 21.6388V35.365H30.4024L44.1274 21.6388ZM46.0024 20.3125H90.9249V99.6875H29.0762V37.24H46.0024V20.3125Z" fill="#868E99" />
            <path d="M83.6988 72.3676H36.3013V74.2426H83.6988V72.3676Z" fill="#868E99" />
            <path d="M83.6988 82.245H36.3013V84.12H83.6988V82.245Z" fill="#868E99" />
            <path d="M83.6988 92.1226H36.3013V93.9976H83.6988V92.1226Z" fill="#868E99" />
            <path fill-rule="evenodd" clip-rule="evenodd" d="M78.5862 46.875C79.367 46.875 80 46.2314 80 45.4375C80 44.6436 79.367 44 78.5862 44H68.6897C67.9088 44 67.2759 44.6436 67.2759 45.4375C67.2759 47.8192 69.1748 49.75 71.5172 49.75C73.364 49.75 74.935 48.5499 75.5173 46.875H78.5862ZM40.4138 46.875C39.633 46.875 39 46.2314 39 45.4375C39 44.6436 39.633 44 40.4138 44H50.3103C51.0912 44 51.7241 44.6436 51.7241 45.4375C51.7241 47.8192 49.8252 49.75 47.4828 49.75C45.636 49.75 44.065 48.5499 43.4827 46.875H40.4138ZM55.7524 66.3213C55.888 66.0995 56.2309 65.69 56.7753 65.2677C57.698 64.552 58.8279 64.1248 60.2069 64.1248C61.5859 64.1248 62.7158 64.552 63.6385 65.2677C64.1829 65.69 64.5258 66.0995 64.6614 66.3213C65.0736 66.9955 65.9454 67.2023 66.6085 66.7831C67.2716 66.364 67.475 65.4776 67.0628 64.8034C66.7589 64.3064 66.199 63.6378 65.3535 62.9819C63.9596 61.9008 62.2381 61.2499 60.2069 61.2499C58.1757 61.2499 56.4542 61.9008 55.0603 62.9819C54.2148 63.6378 53.6549 64.3064 53.351 64.8034C52.9388 65.4776 53.1422 66.364 53.8053 66.7831C54.4684 67.2023 55.3402 66.9955 55.7524 66.3213Z" fill="#868E99" />
          </svg>
        </div>
        <p class="tc mb3">
          {{ $t('people.document_upload_zone_error') }}
        </p>
        <p class="tc">
          <input id="file" ref="file" type="file" @change="handleFileUpload()" />
        </p>
      </div>
    </transition>
    <!-- LIST OF PHOTO -->
    <div class="db mt3">
      <div class="flex flex-wrap">
        <div v-for="photo in photos" :key="photo.id" class="w-third-ns w-100">
          <div class="pa3 mr3 mb3 br2 ba b--gray-monica">
            <div class="cover bg-center photo w-100 h-100 br2 bb b--gray-monica pb2" :style="'background-image: url(' + photo.link + ');'" @click.prevent="modalPhoto(photo)">
            </div>
            <div class="pt2">
              <ul>
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
            <img :src="url" class="mw-90 h-auto mb3" />
            <div class="tc">
              <button class="btn" @click="showModal = false">
                {{ $t('app.close') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
export default {

  props: {
    hash: {
      type: String,
      default: '',
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
      displayUploadZone: false,
      displayUploadProgress: false,
      displayUploadError: false,
      file: '',
      uploadPercentage: 0,
      confirmDestroyPhotoId: 0,
      showModal: false,
      url: '',
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

    showUploadZone() {
      this.displayUploadZone = true;
    },

    handleFileUpload(){
      this.file = this.$refs.file.files[0];
      this.submitFile();
    },

    submitFile(){
      this.displayUploadZone = false;
      this.displayUploadProgress = true;
      let formData = new FormData();
      formData.append('photo', this.file);
      axios.post( 'people/' + this.hash + '/photos',
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data'
          },
          onUploadProgress: function( progressEvent ) {
            this.uploadPercentage = parseInt( Math.round( ( progressEvent.loaded * 100 ) / progressEvent.total ) );
          }.bind(this)
        }
      ).then(response => {
        this.displayUploadProgress = false;
        this.photos.push(response.data.data);
        this.$notify({
          group: 'main',
          title: this.$t('app.default_save_success'),
          text: '',
          type: 'success'
        });
      })
        .catch(error => {
          this.displayUploadProgress = false;
          this.file = null;
          this.displayUploadError = true;
        });
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
      this.url = photo.link;
      this.showModal = true;
    }
  }
};
</script>