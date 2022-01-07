<style scoped>
.participant {
  background: #E5F3F9;
  border-radius: 7px;
}
.participant-list {
  height: 150px;
}
input[type=text] {
  background-color: #f5f5f5;
}
input[type=text]:focus {
  background-color: #fff;
}
.potential-participant:hover {
  background-color: #f1f5fd;
}
</style>

<template>
  <div class="relative">
    <ul v-show="chosenParticipants.length !== 0" class="mr2 mb3">
      <li v-for="chosenParticipant in chosenParticipants"
          :key="chosenParticipant.id"
          class="dib participant br5 mr2"
      >
        <span class="ph2 pv1 dib">
          {{ chosenParticipant.name }}
        </span>
        <span class="bl ph2 pv1 f6 pointer" @click.prevent="remove(chosenParticipant)">
          ❌
        </span>
      </li>
    </ul>
    <div v-show="participants.length !== 0" class="ba b--gray-monica">
      <span class="db bb b--gray-monica pa2">
        <input v-model="search" type="text" :placeholder="$t('app.filter')" class="br2 f5 w-100 ba b--black-20 pa2 outline-0" />
      </span>
      <ul class="overflow-auto participant-list">
        <li v-for="fparticipant in filteredList"
            :key="fparticipant.id"
            class="bb b--gray-monica pa2 pointer potential-participant"
            @click.prevent="select(fparticipant)"
        >
          {{ fparticipant.name }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script>

export default {

  props: {
    initialParticipants: {
      type: Array,
      default: () => [],
    },
    hash: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      search: '',
      participants: [],
      chosenParticipants: [],
    };
  },

  computed: {
    filteredList() {
      // filter the list when searching
      // also, sort the list by name
      var list;
      list = this.participants.filter(participant => {
        return participant.name.toLowerCase().includes(this.search.toLowerCase())
               && this.chosenParticipants.find(p => p.id === participant.id) === undefined;
      });

      function compare(a, b) {
        if (a.name < b.name) {
          return -1;
        }
        if (a.name > b.name) {
          return 1;
        }
        return 0;
      }

      return list.sort(compare);
    }
  },

  mounted() {
    this.prepareComponent();
    this.chosenParticipants = this.initialParticipants;
  },

  methods: {
    prepareComponent() {
      this.getParticipants();
    },

    getParticipants: function () {
      axios.get('people/' + this.hash + '/activities/contacts')
        .then(response => {
          this.participants = _.toArray(response.data);
        });
    },

    select(participant) {
      this.chosenParticipants.push(participant);
      this.participants.splice(this.participants.indexOf(participant), 1);
      this.$emit('update', this.chosenParticipants);
    },

    remove(participant) {
      this.participants.push(participant);
      this.chosenParticipants.splice(this.chosenParticipants.indexOf(participant), 1);
    },
  }
};
</script>
