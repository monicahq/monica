<style scoped lang="scss">
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
                 @change="handleFileUpload($event)"
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
          <input id="file" ref="file" type="file" @change="handleFileUpload($event)" />
        </p>
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
      displayUploadZone: false,
      displayUploadProgress: false,
      displayUploadError: false,
      file: '',
      uploadPercentage: 0,
      confirmDestroyPhotoId: 0,
      url: '',
    };
  },

  methods: {

    showUploadZone() {
      this.displayUploadZone = true;
    },

    inUpload() {
      return this.displayUploadZone || this.displayUploadError || this.displayUploadProgress;
    },

    cancelUpload() {
      this.displayUploadZone = false;
      this.displayUploadError = false;
      this.displayUploadProgress = false;
    },

    handleFileUpload(event){
      this.$emit('upload', event);
      if (!event.cancelBubble) {
        this.forceFileUpload();
      }
    },

    forceFileUpload(){
      const f = this.$refs.file !== undefined ? this.$refs.file.files[0] : undefined;
      if (f === undefined) {
        return Promise.resolve();
      }
      this.file = f;
      return this.submitFile();
    },

    submitFile(){
      this.displayUploadZone = false;
      this.displayUploadProgress = true;

      const formData = new FormData();
      formData.append('photo', this.file);

      return axios.post(`people/${this.hash}/photos`,
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

        this.$emit('newphoto', response.data.data);

        return response.data.data;
      })
        .catch(error => {
          this.displayUploadProgress = false;
          this.file = null;
          this.displayUploadError = true;
        });
    },
  }
};
</script>
