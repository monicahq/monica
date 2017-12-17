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
            <p class="mb2">You rated your day</p>
            <ul class="f7">
              <li class="di">
                <a href="">Edit</a>
              </li>
              <li class="di">
                <a href="">Delete</a>
              </li>
            </ul>
          </div>

          <!-- Attendees -->
          <div class="flex-none w-10">
            <div class="flex justify-center items-center h-100">
              <svg width="42px" height="41px" viewBox="0 0 42 41" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                  <defs></defs>
                  <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                      <g id="Desktop" transform="translate(-754.000000, -165.000000)">
                          <g id="Group-2" transform="translate(754.000000, 165.000000)">
                              <path d="M41.8643309,20.5475529 C41.8643309,31.8129081 32.5521151,40.9454502 21.0661727,40.9454502 C9.57992806,40.9454502 0.268618705,31.8129081 0.268618705,20.5475529 C0.268618705,9.28219757 9.57992806,0.149951854 21.0661727,0.149951854 C32.5521151,0.149951854 41.8643309,9.28219757 41.8643309,20.5475529" id="Fill-96" fill="#E5E5E5"></path>
                              <path d="M11.5989496,21.8043628 L11.5989496,21.8043628 C10.7680144,21.8043628 10.0881583,21.1375808 10.0881583,20.3226251 L10.0881583,16.7664546 C10.0881583,15.9514989 10.7680144,15.2847169 11.5989496,15.2847169 C12.4298849,15.2847169 13.109741,15.9514989 13.109741,16.7664546 L13.109741,20.3226251 C13.109741,21.1375808 12.4298849,21.8043628 11.5989496,21.8043628" id="Fill-97" fill="#36301D"></path>
                              <path d="M30.534,21.8043628 L30.534,21.8043628 C29.7030647,21.8043628 29.0232086,21.1375808 29.0232086,20.3226251 L29.0232086,16.7664546 C29.0232086,15.9514989 29.7030647,15.2847169 30.534,15.2847169 C31.3649353,15.2847169 32.0447914,15.9514989 32.0447914,16.7664546 L32.0447914,20.3226251 C32.0447914,21.1375808 31.3649353,21.8043628 30.534,21.8043628" id="Fill-98" fill="#36301D"></path>
                              <path d="M9.15690647,27.2639734 L32.9760432,27.2639734 C33.4767194,27.2639734 33.882518,26.8659787 33.882518,26.3749308 C33.882518,25.883883 33.4767194,25.4858882 32.9760432,25.4858882 L9.15690647,25.4858882 C8.65623022,25.4858882 8.25043165,25.883883 8.25043165,26.3749308 C8.25043165,26.8659787 8.65623022,27.2639734 9.15690647,27.2639734" id="Fill-99" fill="#36301D"></path>
                          </g>
                      </g>
                  </g>
              </svg>
            </div>
          </div>

          <!-- Show comment -->
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
