<style scoped>
    .document-upload-zone {
        background: #fff;
        border: 1px solid #d6d6d6;
        border-style: dashed;
    }

    .document-type {
        width: 60px;
    }

    .document-type-icon {
        background: #ADF4FE;
        border-radius: 13px;
        font-size: 11px;
        padding: 5px 8px;
    }

    .document-date {
        width: 110px;
    }

    .document-action {
        width: 50px;
    }

    .document-action-wrapper {
        background: #fff;
        opacity: 0.8;
        border-radius: 3px;
        line-height: 18px;
        padding: 2px 5px;
    }

    .document-action-wrapper:hover {
        box-shadow: 1px 0px 1px rgba(43, 45, 80, 0.16), -1px 1px 1px rgba(43, 45, 80, 0.16), 0px 1px 4px rgba(43, 45, 80, 0.18);
    }

    .document-action-menu {
        border-radius: 3px;
        box-shadow: 1px 0px 1px rgba(43, 45, 80, 0.16), -1px 1px 1px rgba(43, 45, 80, 0.16), 0px 1px 4px rgba(43, 45, 80, 0.18);
        right: 10px;
        top: 34px;
    }

    .document-action-menu-item.delete {
        color: #CB4066;
    }

    .document-action-menu-item.delete:hover {
        color: #fff;
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
        📄 {{ $t('people.document_list_title') }}

        <span v-show="reachLimit === 'false'" class="fr relative" style="top: -7px;">
          <a v-if="displayUploadZone === false && displayUploadError === false && displayUploadProgress === false" class="btn edit-information" html="" @click.prevent="displayUploadZone = true">
            {{ $t('people.document_list_cta') }}
          </a>
          <a v-if="displayUploadZone || displayUploadError || displayUploadProgress" class="btn edit-information" href="" @click.prevent="displayUploadZone = false; displayUploadError = false; displayUploadProgress = false">
            {{ $t('app.cancel') }}
          </a>
        </span>
      </h3>
    </div>

    <p v-show="reachLimit === 'true'">
      {{ $t('settings.storage_upgrade_notice') }}
    </p>

    <!-- EMPTY STATE -->
    <div v-if="displayUploadZone === false && displayUploadError === false && displayUploadProgress === false && documents.length === 0" class="ltr w-100 pt2">
      <div class="section-blank">
        <h3 class="mb0">
          {{ $t('people.document_list_blank_desc') }}
        </h3>
      </div>
    </div>

    <!-- FIRST STEP OF DOCUMENT UPLOAD -->
    <transition name="fade">
      <div v-if="displayUploadZone" class="ba br3 document-upload-zone mb3 pa3">
        <div class="tc">
          <svg width="120" height="150" viewBox="0 0 120 150" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M27.2012 35.9138V101.562H92.7999V18.4375H44.6762L27.2012 35.9138ZM44.1274 21.6388V35.365H30.4024L44.1274 21.6388ZM46.0024 20.3125H90.9249V99.6875H29.0762V37.24H46.0024V20.3125Z" fill="#868E99" />
            <path d="M63.9413 42.7338H36.3013V44.6088H63.9413V42.7338Z" fill="#868E99" />
            <path d="M63.9413 52.6112H36.3013V54.4862H63.9413V52.6112Z" fill="#868E99" />
            <path d="M83.6988 62.4888H36.3013V64.3638H83.6988V62.4888Z" fill="#868E99" />
            <path d="M83.6988 72.3676H36.3013V74.2426H83.6988V72.3676Z" fill="#868E99" />
            <path d="M83.6988 82.245H36.3013V84.12H83.6988V82.245Z" fill="#868E99" />
            <path d="M83.6988 92.1226H36.3013V93.9976H83.6988V92.1226Z" fill="#868E99" />
            <path d="M84.6362 41.585H67.8774V58.3425H84.6362V41.585ZM82.7612 56.4675H69.7524V43.46H82.7612V56.4675Z" fill="#868E99" />
            <path d="M35.2864 107.656C33.6551 107.656 32.3276 108.984 32.3276 110.615C32.3276 112.246 33.6551 113.574 35.2864 113.574C36.9176 113.574 38.2451 112.246 38.2451 110.615C38.2451 108.984 36.9176 107.656 35.2864 107.656ZM35.2864 112.636C34.1714 112.636 33.2651 111.729 33.2651 110.615C33.2651 109.501 34.1726 108.594 35.2864 108.594C36.4001 108.594 37.3076 109.501 37.3076 110.615C37.3076 111.729 36.4001 112.636 35.2864 112.636Z" fill="#868E99" />
            <path d="M10.6401 41.265L13.3251 42.6762L12.8126 39.6862L14.9851 37.5687L11.9839 37.1325L10.6401 34.4125L9.29764 37.1325L6.29639 37.5687L8.46889 39.6862L7.95639 42.6762L10.6401 41.265ZM8.31014 38.2225L9.92014 37.9887L10.6401 36.53L11.3601 37.9887L12.9701 38.2225L11.8051 39.3587L12.0801 40.9625L10.6401 40.2062L9.20014 40.9625L9.47514 39.3587L8.31014 38.2225Z" fill="#868E99" />
            <path d="M36.3237 10.1125L35.365 8.17004L34.405 10.1125L32.2612 10.425L33.8125 11.9375L33.4462 14.0725L35.3637 13.065L37.2812 14.0725L36.9162 11.9375L38.4675 10.425L36.3237 10.1125ZM36.0375 12.36L35.365 12.0063L34.6925 12.36L34.8212 11.61L34.275 11.0788L35.0275 10.97L35.3637 10.2888L35.7 10.97L36.4525 11.0788L35.9075 11.61L36.0375 12.36Z" fill="#868E99" />
            <path d="M108.859 87.615L110.776 88.6225L110.41 86.4875L111.961 84.975L109.818 84.6625L108.859 82.72L107.9 84.6625L105.756 84.975L107.308 86.4875L106.941 88.6225L108.859 87.615ZM108.523 85.52L108.859 84.8387L109.195 85.52L109.948 85.6287L109.403 86.16L109.531 86.91L108.859 86.5562L108.186 86.91L108.315 86.16L107.77 85.6287L108.523 85.52Z" fill="#868E99" />
            <path d="M108.859 17.1625L107.618 20.9837H103.6L106.849 23.345L105.609 27.165L108.859 24.8037L112.109 27.165L110.868 23.345L114.116 20.9837H110.1L108.859 17.1625ZM109.765 22.9875L110.325 24.71L108.859 23.645L107.391 24.7112L107.951 22.9875L106.485 21.9212H108.299L108.859 20.1975L109.419 21.9212H111.233L109.765 22.9875Z" fill="#868E99" />
            <path d="M98.9138 6.42749C97.2826 6.42749 95.9551 7.75499 95.9551 9.38624C95.9551 11.0175 97.2826 12.345 98.9138 12.345C100.545 12.345 101.873 11.0175 101.873 9.38624C101.873 7.75499 100.545 6.42749 98.9138 6.42749ZM98.9138 11.4075C97.7988 11.4075 96.8926 10.5 96.8926 9.38624C96.8926 8.27249 97.8001 7.36499 98.9138 7.36499C100.028 7.36499 100.935 8.27249 100.935 9.38624C100.935 10.5 100.028 11.4075 98.9138 11.4075Z" fill="#868E99" />
            <path d="M110.779 58.8438C109.461 58.8438 108.39 59.915 108.39 61.2325C108.39 62.55 109.461 63.6213 110.779 63.6213C112.096 63.6213 113.168 62.55 113.168 61.2325C113.168 59.915 112.096 58.8438 110.779 58.8438ZM110.779 62.6837C109.979 62.6837 109.328 62.0325 109.328 61.2325C109.328 60.4325 109.979 59.7813 110.779 59.7813C111.579 59.7813 112.23 60.4325 112.23 61.2325C112.23 62.0325 111.579 62.6837 110.779 62.6837Z" fill="#868E99" />
            <path d="M18.6973 18.3362C16.9836 18.3362 15.5898 19.7299 15.5898 21.4437C15.5898 23.1574 16.9836 24.5512 18.6973 24.5512C20.4111 24.5512 21.8048 23.1574 21.8048 21.4437C21.8048 19.7299 20.4111 18.3362 18.6973 18.3362ZM18.6973 23.6137C17.5011 23.6137 16.5273 22.6399 16.5273 21.4437C16.5273 20.2474 17.5011 19.2737 18.6973 19.2737C19.8936 19.2737 20.8673 20.2474 20.8673 21.4437C20.8673 22.6399 19.8936 23.6137 18.6973 23.6137Z" fill="#868E99" />
            <path d="M27.4414 110.794C27.8502 110.898 28.2652 110.99 28.6839 111.074L28.8677 110.155C28.4639 110.074 28.0652 109.984 27.6702 109.885L27.4414 110.794Z" fill="#868E99" />
            <path d="M9.64111 94.6313C9.91986 95.4101 10.2424 96.1876 10.5999 96.9413L11.4474 96.5413C11.1036 95.8138 10.7936 95.0663 10.5249 94.3151L9.64111 94.6313Z" fill="#868E99" />
            <path d="M11.7773 99.1475C12.2048 99.86 12.6711 100.56 13.1661 101.229L13.9198 100.671C13.4448 100.028 12.9936 99.3525 12.5823 98.6663L11.7773 99.1475Z" fill="#868E99" />
            <path d="M22.7339 109.106C23.4851 109.463 24.2639 109.785 25.0464 110.066L25.3626 109.184C24.6076 108.914 23.8589 108.603 23.1351 108.26L22.7339 109.106Z" fill="#868E99" />
            <path d="M14.75 103.164C15.305 103.78 15.8988 104.378 16.5163 104.938L17.1475 104.244C16.5538 103.704 15.9813 103.129 15.4463 102.536L14.75 103.164Z" fill="#868E99" />
            <path d="M9.08887 86.0649C9.08887 85.6587 9.09887 85.2512 9.11887 84.8412L8.18262 84.7949C8.16137 85.2199 8.15137 85.6424 8.15137 86.0649C8.15137 86.4774 8.16137 86.8887 8.18137 87.2974L9.11762 87.2524C9.09887 86.8574 9.08887 86.4612 9.08887 86.0649Z" fill="#868E99" />
            <path d="M9.35279 89.6475L8.42529 89.785C8.54779 90.6062 8.71154 91.4312 8.91404 92.2375L9.82404 92.0087C9.62904 91.2325 9.47029 90.4387 9.35279 89.6475Z" fill="#868E99" />
            <path d="M18.4473 106.53C19.116 107.027 19.816 107.496 20.526 107.924L21.0098 107.12C20.326 106.709 19.6523 106.257 19.0073 105.777L18.4473 106.53Z" fill="#868E99" />
            <path d="M9.36012 82.425C9.42012 82.0225 9.49012 81.6187 9.57012 81.2162L8.65137 81.0325C8.56762 81.4512 8.49512 81.8687 8.43262 82.2875L9.36012 82.425Z" fill="#868E99" />
            <path d="M111.323 96.2138L112.257 96.2988C112.295 95.8763 112.319 95.4488 112.328 95.0176L111.39 94.9976C111.382 95.4076 111.359 95.8126 111.323 96.2138Z" fill="#868E99" />
            <path d="M110.009 101.347L110.869 101.721C111.241 100.865 111.546 99.9725 111.779 99.0675L110.87 98.8337C110.651 99.6912 110.361 100.537 110.009 101.347Z" fill="#868E99" />
            <path d="M107.156 105.81L107.858 106.433C108.48 105.733 109.049 104.98 109.55 104.196L108.76 103.691C108.284 104.434 107.745 105.146 107.156 105.81Z" fill="#868E99" />
            <path d="M98.1226 111.079L98.3176 111.995C99.2288 111.801 100.134 111.531 101.005 111.192L100.666 110.319C99.8413 110.64 98.9851 110.895 98.1226 111.079Z" fill="#868E99" />
            <path d="M103.059 109.166L103.531 109.975C104.337 109.505 105.111 108.966 105.834 108.374L105.239 107.649C104.555 108.211 103.821 108.721 103.059 109.166Z" fill="#868E99" />
            <path d="M94.2637 111.444L94.2437 112.381C94.3649 112.384 94.4862 112.385 94.6074 112.385C94.9162 112.385 95.2224 112.378 95.5287 112.361L95.4799 111.425C95.0774 111.446 94.6724 111.455 94.2637 111.444Z" fill="#868E99" />
            <path d="M111.248 45.4712H110.31V47.9712H111.248V45.4712Z" fill="#868E99" />
            <path d="M111.248 40.4712H110.31V42.9712H111.248V40.4712Z" fill="#868E99" />
            <path d="M111.248 35.4712H110.31V37.9712H111.248V35.4712Z" fill="#868E99" />
            <path d="M111.248 50.4712H110.31V52.9712H111.248V50.4712Z" fill="#868E99" />
          </svg>
        </div>
        <div class="tc dib w-100 relative">
          <button class="btn">
            {{ $t('people.document_upload_zone_cta') }}
          </button>
          <input id="file" ref="file" type="file" class="absolute o-0 w-100 h-100 pointer" style="left:0;"
                 @change="handleFileUpload()"
          />
        </div>
      </div>
    </transition>

    <!-- LAST STEP OF DOCUMENT UPLOAD -->
    <div v-if="displayUploadProgress" class="ba br3 document-upload-zone mb3 pa3">
      <p class="tc mb1">
        {{ $t('people.document_upload_zone_progress') }}
      </p>
      <div class="tc mb1">
        <progress max="100" :value.prop="uploadPercentage"></progress>
      </div>
      <p class="tc f6 mb0">
        {{ uploadPercentage }}% uploaded
      </p>
    </div>

    <!-- ERROR STEP WHEN UPLOADING A DOCUMENT -->
    <transition name="fade">
      <div v-if="displayUploadError" class="ba br3 document-upload-zone mb3 pa3">
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

    <!-- LIST OF DOCUMENTS -->
    <div class="table">
      <div v-for="document in documents" :key="document.id" class="table-row">
        <div class="table-cell document-date f6">
          {{ formatTime(document.created_at) }}
        </div>
        <div class="table-cell document-type tc">
          <span class="document-type-icon">
            {{ document.type }}
          </span>
        </div>
        <div class="table-cell">
          {{ document.original_filename }}
        </div>
        <div class="table-cell document-action relative">
          <div class="document-action-wrapper pointer" @click="toggleActionsModal(document.id)">
            <svg class="document-action-button" width="24" height="6" viewBox="0 0 24 6" fill="none"
                 xmlns="http://www.w3.org/2000/svg"
            >
              <circle cx="4.5" cy="2.5" r="2.5" fill="#505473" fill-opacity="0.86" />
              <circle cx="11.5" cy="2.5" r="2.5" fill="#505473" fill-opacity="0.86" />
              <circle cx="18.5" cy="2.5" r="2.5" fill="#505473" fill-opacity="0.86" />
            </svg>
          </div>
          <ul v-if="modalToDisplay === document.id" class="absolute bg-white z-max pv1 document-action-menu">
            <li class="tc">
              <a class="pv2 pointer ph3 inline-flex items-center w-100 no-underline document-action-menu-item" :href="document.link" target="_blank" @click="downloadDocument(document)">
                {{ $t('app.download') }}
              </a>
            </li>
            <li class="tc">
              <a class="pv2 pointer ph3 inline-flex items-center no-underline w-100 document-action-menu-item delete" href="" @click.prevent="deleteDocument(document)">
                {{ $t('app.delete') }}
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
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
  },

  data() {
    return {
      documents: [],
      displayUploadZone: false,
      displayUploadProgress: false,
      displayUploadError: false,
      file: '',
      uploadPercentage: 0,
      modalToDisplay: null,
    };
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {

    prepareComponent() {
      this.getDocuments();
    },

    getDocuments() {
      axios.get('people/' + this.hash + '/documents')
        .then(response => {
          this.documents = response.data.data;
        });
    },

    showUploadZone() {
      this.displayUploadZone = true;
    },

    toggleActionsModal(id) {
      if (this.modalToDisplay === id) {
        this.modalToDisplay = null;
      } else {
        this.modalToDisplay = id;
      }
    },

    handleFileUpload(){
      this.file = this.$refs.file.files[0];
      this.submitFile();
    },

    formatTime(dateAsString) {
      var moment = require('moment-timezone');
      moment.locale(this._i18n.locale);

      var date = moment(dateAsString);
      return date.format('ll');
    },

    submitFile(){
      this.displayUploadZone = false;
      this.displayUploadProgress = true;

      const formData = new FormData();

      formData.append('document', this.file);

      axios.post( 'people/' + this.hash + '/documents',
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
        this.documents.push(response.data);
      })
        .catch(error => {
          this.displayUploadProgress = false;
          this.file = null;
          this.displayUploadError = true;
        });
    },

    downloadDocument(doc) {
      window.open(doc.link, '_blank');

      // Close the modal menu
      this.modalToDisplay = null;
    },

    deleteDocument(document) {
      axios.delete( 'people/' + this.hash + '/documents/' + document.id)
        .then(response => {
          this.documents.splice(this.documents.indexOf(document), 1);
        });
    },
  }
};
</script>
