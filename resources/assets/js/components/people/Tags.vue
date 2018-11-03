<style scoped>
.autocomplete-result.is-active,
  .autocomplete-result:hover {
    background-color: #4AAE9B;
    color: white;
  }
</style>

<template>
    <div class="">
        <div class="mt4">
            <a @click="editMode = true" v-show="!editMode">Edit tag</a>
            <a @click="editMode = false" v-show="editMode">Cancel</a>
            <a @click="store()" v-show="editMode">Save</a>
        </div>

        <ul>
            <li v-for="tag in contactTags" :key="tag.id" class="di mr2">
                <span class="bg-white ph2 pb1 pt0 dib br3 b--light-gray ba">
                    {{ tag.name }}
                    <span @click="removeTag(tag)" v-show="editMode" class="pointer">×</span>
                </span>
            </li>
        </ul>

        <input type="text"
                class=""
                v-show="editMode"
                v-model="search"
                @keydown.down="onArrowDown"
                @keydown.up="onArrowUp"
                @keydown.enter="onEnter"
                @input="onChange">

        <ul class="autocomplete-results" v-show="isOpen">
            <li class="autocomplete-result"
                v-for="(result, i) in results"
                :key="i"
                @click="setResult(result)"
                :class="{ 'is-active': i === arrowCounter }">
                {{ result.name }}
            </li>
        </ul>
    </div>
</template>

<script>
    import moment from 'moment'

    export default {
        data() {
            return {
                allTags: [],
                availableTags: [],
                contactTags: [],
                editMode: false,
                search: '',
                results: [],
                isOpen: false,
                arrowCounter: 0
            };
        },

        mounted() {
            this.prepareComponent();
        },

        props: {
            hash: {
                type: String,
            },
        },

        methods: {
            prepareComponent() {
                this.getExistingTags()
                this.getContactTags()
                //this.filterAllTags()
            },

            getExistingTags() {
                axios.get('/tags')
                    .then(response => {
                        this.allTags = response.data.data
                    })
            },

            getContactTags() {
                axios.get('/people/' + this.hash + '/tags')
                    .then(response => {
                        this.contactTags = response.data.data
                    })
            },

            removeTag(tag) {
                this.contactTags.splice(this.contactTags.indexOf(tag), 1)
            },

            onChange() {
                this.isOpen = true
                this.filterResults()
            },

            onEnter() {
                if (this.search != '') {
                    this.contactTags.push({
                        id: moment().format(),
                        name: this.search
                    })
                    this.arrowCounter = -1
                    this.isOpen = false
                    this.search = null
                }
            },

            onArrowDown() {
                if (this.arrowCounter < this.results.length) {
                    this.arrowCounter = this.arrowCounter + 1;
                    this.search = this.results[this.arrowCounter].name
                }
            },

            onArrowUp() {
                if (this.arrowCounter > 0) {
                    this.arrowCounter = this.arrowCounter - 1;
                    this.search = this.results[this.arrowCounter].name
                }
            },

            setResult(result) {
                this.search = null
                this.isOpen = false
                this.contactTags.push(result)
                //this.filterAllTags()
            },

            filterResults() {
                this.results = this.allTags.filter(item => item.name.toLowerCase().indexOf(this.search.toLowerCase()) > -1)
            },

            filterAllTags() {
                var me = this.contactTags
                this.availableTags = this.allTags.filter((item) => {
                    return !me.includes(item)
                })
            },

            store() {
                this.editMode = false
                axios.post('/people/' + this.hash + '/tags/update', this.contactTags)
                        .then(response => {
                            console.log('df')
                        })
            }
        }
    }
</script>
