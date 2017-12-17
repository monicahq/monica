<style scoped>
</style>

<template>
  <div>

    <!-- Left columns: showing calendar -->
    <journal-calendar></journal-calendar>

    <!-- Right column: showing logs -->
    <div class="fl journal-calendar-content">
      <div class="br3 ba b--gray-monica bg-white pr3 pb3 pt3 mb3">
        <div class="flex">

          <!-- Day -->
          <div class="flex-none w-10 tc">
            <h3 class="mb0 normal">10</h3>
            <p class="mb0">Mon</p>
          </div>

          <!-- Log content -->
          <div class="flex-auto">
            <p class="mb1">Dinner at the restaurant</p>
            <p class="mb1">Le Melbourne</p>
            <p class="">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor. Praesent et diam eget libero egestas mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur. Donec ut libero sed arcu vehicula ultricies a non tortor.</p>
            <ul class="f7">
              <li class="di">
                <a href="">Edit</a>
              </li>
              <li class="di">
                <a href="">Delete</a>
              </li>
            </ul>
          </div>

          <div class="flex-none w-20">
            <div class="flex justify-center items-center h-100 journal-avatars-container">
              <img src="https://randomuser.me/api/portraits/women/8.jpg" class="br-100 pa1 ba b--black-10 h3 w3">
              <img src="https://randomuser.me/api/portraits/men/29.jpg" class="br-100 pa1 ba b--black-10 h3 w3">
            </div>
          </div>

          <!-- Comment -->
          <div class="flex-none w-5">
            <div class="flex justify-center items-center h-100">
              <svg width="16px" height="13px" viewBox="0 0 16 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" class="flex-none">
                <defs></defs>
                <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="square">
                    <g id="Desktop" transform="translate(-839.000000, -279.000000)" stroke="#979797">
                        <g id="Group-4" transform="translate(839.000000, 278.000000)">
                            <path d="M0.5,1.5 L15.5,1.5" id="Line-2"></path>
                            <path d="M0.5,9.5 L15.5,9.5" id="Line-2"></path>
                            <path d="M0.5,5.5 L13.5,5.5" id="Line-2"></path>
                            <path d="M0.5,13.5 L10.5,13.5" id="Line-2"></path>
                        </g>
                    </g>
                </g>
              </svg>
            </div>
          </div>

        </div>
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
            },
        }
    }
</script>
