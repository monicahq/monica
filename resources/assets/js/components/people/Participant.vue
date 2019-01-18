<style scoped>
</style>

<template>
  <div>
    <div class="relative">
      <v-autocomplete :items="participants"
        v-model="participant"
        :get-label="getLabel"
        @update-items="updateItems"
        @item-selected="click"
        @blur="clearSearch"
        :wait="wait"
        :min-len="minLen"
        :keep-open="true"
        :input-attrs="input"
        :autoSelectOneItem="false"
        :keepOpen="true"
        >
      </v-autocomplete>
    </div>
  </div>
</template>

<script>
import vAutocomplete from 'v-autocomplete';

export default {

  props: {
    initialParticipants: {
      type: Array,
      default: function () {
        return [];
      }
    }
  },

  components: {
    vAutocomplete
  },

  data() {
    return {
      participants: [],
      chosenParticipants: [],
      menu: false,
      participant: null,
      input: {
          class: 'form-control header-search-input',
          placeholder: this.placeholder
      }
    };
  },

  mounted() {
    this.prepareComponent();
    this.chosenParticipants = this.initialParticipants;
  },

  created() {
    window.addEventListener('click', this.close);
  },

  beforeDestroy() {
    window.removeEventListener('click', this.close);
  },

  methods: {
    prepareComponent() {
    },

    close(e) {
      if (!this.$el.contains(e.target)) {
        this.menu = false;
      }
    },

    getLabel (item) {
      return item != null ? item.keyword : '';
    },

    updateParticipants (text) {
      this.getParticipants(text, this).then( (response) => {
        this.participants = response
      })
    },

    getParticipants: function (keyword, vm) {
      return axios.post('/people/search', {
          needle: keyword
      }).then(function(response) {
          let data = [];
          if (response.data.noResults != null) {
              data.push({
                  'item' : -1,
                  'message': response.data.noResults,
                  'keyword': keyword,
              });
          }
          else {
              response.data.data.forEach(function (contact) {
                  if (contact.id === vm.userContactId) {
                      return;
                  }
                  contact.keyword = keyword;
                  data.push(contact);
              });
          }
          return data;
      });
    },

    getEmotions(id) {
      axios.get('/emotions/primaries/' + this.selectedPrimaryEmotionId + '/secondaries/' + this.selectedSecondaryEmotionId + '/emotions')
        .then(response => {
          this.emotions = response.data.data;
        });
    },

    showEmotion(secondaryEmotion) {
      this.selectedSecondaryEmotionId = secondaryEmotion.id;
      this.getEmotions();
      this.emotionsMenu = 'emotions';
    },

    addEmotion(emotion) {
      this.menu = false;
      this.chosenParticipants.push(emotion);
      this.emotionsMenu = 'primary';
      this.$emit('updateEmotionsList', this.chosenParticipants);
    },

    removeEmotion(emotion) {
      this.chosenParticipants.splice(emotion, 1);
      this.$emit('updateEmotionsList', this.chosenParticipants);
    }
  }
};
</script>
