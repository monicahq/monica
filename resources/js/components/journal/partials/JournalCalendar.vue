<template>
  <div :class="[ dirltr ? 'fl' : 'fr' ]" class="journal-calendar-box relative">
    <template v-if="journalEntry.show_calendar">
      <div class="journal-calendar-text absolute tc">
        <span class="db w-100">
          {{ journalEntry.object.month_name }}
        </span>
        <span class="db w-100">
          {{ journalEntry.object.year }}
        </span>
      </div>
      <div class="journal-calendar-svg">
        <svg width="62px" height="61px" viewBox="0 0 62 61" version="1.1" xmlns="http://www.w3.org/2000/svg"
             xmlns:xlink="http://www.w3.org/1999/xlink"
        >
          <defs>
            <rect id="path-1" x="0" y="8" width="62" height="53"
                  rx="4"
            />
            <rect id="path-2" x="0" y="6" width="62" height="53"
                  rx="4"
            />
            <rect id="path-3" x="0" y="4" width="62" height="53"
                  rx="4"
            />
            <rect id="path-4" x="12" y="0" width="5" height="12"
                  rx="2.5"
            />
            <rect id="path-5" x="30" y="0" width="5" height="12"
                  rx="2.5"
            />
            <rect id="path-6" x="48" y="0" width="5" height="12"
                  rx="2.5"
            />
          </defs>
          <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
            <g id="Desktop" transform="translate(-87.000000, -243.000000)">
              <g id="Group-6" transform="translate(87.000000, 243.000000)">
                <g id="Rectangle">
                  <use fill="#FFFFFF" fill-rule="evenodd" xlink:href="#path-1" />
                  <rect stroke="#DDE2E9" stroke-width="1" x="0.5" y="8.5" width="61"
                        height="52" rx="4"
                  />
                </g>
                <g id="Rectangle">
                  <use fill="#FFFFFF" fill-rule="evenodd" xlink:href="#path-2" />
                  <rect stroke="#DDE2E9" stroke-width="1" x="0.5" y="6.5" width="61"
                        height="52" rx="4"
                  />
                </g>
                <g id="Rectangle">
                  <use fill="#FFFFFF" fill-rule="evenodd" xlink:href="#path-3" />
                  <rect stroke="#DDE2E9" stroke-width="1" x="0.5" y="4.5" width="61"
                        height="52" rx="4"
                  />
                </g>
                <g id="Rectangle-2">
                  <use fill="#DDE2E9" fill-rule="evenodd" xlink:href="#path-4" />
                  <rect stroke="#DDE2E9" stroke-width="1" x="12.5" y="0.5" width="4"
                        height="11" rx="2"
                  />
                </g>
                <g id="Rectangle-2">
                  <use fill="#DDE2E9" fill-rule="evenodd" xlink:href="#path-5" />
                  <rect stroke="#DDE2E9" stroke-width="1" x="30.5" y="0.5" width="4"
                        height="11" rx="2"
                  />
                </g>
                <g id="Rectangle-2">
                  <use fill="#DDE2E9" fill-rule="evenodd" xlink:href="#path-6" />
                  <rect stroke="#DDE2E9" stroke-width="1" x="48.5" y="0.5" width="4"
                        height="11" rx="2"
                  />
                </g>
              </g>
            </g>
          </g>
        </svg>
      </div>
    </template>

    <template v-else>
      &nbsp;
    </template>
  </div>
</template>

<script>
export default {

  props: {
    journalEntry: {
      type: Object,
      default: null,
    },
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    }
  },
};
</script>
