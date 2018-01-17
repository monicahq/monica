<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <!-- Title -->
    <div>
      <img src="/img/people/gifts.svg" class="icon-section icon-tasks">
      <h3>
        {{ trans('people.section_personal_tasks') }}

        <span class="fr f6 pt2" v-if="gifts.length != 0">

        </span>
      </h3>
    </div>

    <!-- Listing -->
    <div>
        <h2>Gifts ideas ({{ ideas(gifts).length }})</h2>
        <div v-for="gift in ideas(gifts)">
            <p class="mb1">
                <strong>{{ gift.name }}</strong>
                <span v-if="gift.does_value_exist">
                    <span class="mr1 black-50">•</span>
                    Value: {{ gift.value }}
                </span>

                <span v-if="gift.recipient_name">
                    <span class="mr1 black-50">•</span>
                    For: {{ gift.recipient_name }}
                </span>

                <span v-if="gift.url">
                    <span class="mr1 black-50">•</span>
                    <a :href="gift.url" target="_blank">Link</a>
                </span>
            </p>
            <p class="f6">
                Added {{ gift.created_at }}
                <a v-if="gift.comment" href="" class="ml1 mr1">View comment</a>
                <a href="" class="mr1">Mark as offered</a>
                <a href="" class="mr1">Edit</a>
                <a href="" class="mr1">Delete</a>
            </p>
        </div>

        <h2>Gifts received</h2>
        <div v-for="gift in received(gifts)">
            <p>
                {{ gift.name }}
                <span v-if="gift.value">Value: {{ gift.value }}</span>
                <span v-if="gift.recipient_name">For: {{ gift.recipient_name }}</span>
                <a v-if="gift.url" :href="gift.url" target="_blank">Link</a>
            </p>
            <p>
                Added {{ gift.created_at }}
                <a v-if="gift.comment" href="">View comment</a>
                <a href="">Mark as offered</a>
                <a href="">Edit</a>
                <a href="">Delete</a>
            </p>
        </div>

        <h2>Gifts offered</h2>
        <div v-for="gift in offered(gifts)">
            <p>
                {{ gift.name }}
                <span v-if="gift.value">Value: {{ gift.value }}</span>
                <span v-if="gift.recipient_name">For: {{ gift.recipient_name }}</span>
                <a v-if="gift.url" :href="gift.url" target="_blank">Link</a>
            </p>
            <p>
                Added {{ gift.created_at }}
                <a v-if="gift.comment" href="">View comment</a>
                <a href="">Mark as offered</a>
                <a href="">Edit</a>
                <a href="">Delete</a>
            </p>
        </div>
    </div>

  </div>
</template>

<script>

    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                gifts: [],
            };
        },

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent();
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        props: ['contactId'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.getGifts();
            },

            ideas: function (gifts) {
              return gifts.filter(function (note) {
                return note.is_an_idea === true
              })
            },

            received: function (gifts) {
              return gifts.filter(function (note) {
                return note.has_been_offered === true
              })
            },

            offered: function (gifts) {
              return gifts.filter(function (note) {
                return note.has_been_received === true
              })
            },

            getGifts() {
                axios.get('/people/' + this.contactId + '/gifts')
                        .then(response => {
                            this.gifts = response.data;
                        });
            },
        }
    }
</script>
