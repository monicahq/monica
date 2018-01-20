<style scoped>

</style>

<template>
  <div class="mw12 center pa2">
    <div class="load-data" v-if="loadingMore"></div>
    <div id="mood-trends"></div>
  </div>
</template>

<script>
    // const Highcharts = require('highcharts');
    const charts = require('chart.js');
    const moment = require('moment');

    export default {
        /*
         * The component's data.
         */
        data() {
            return {
              chart: null,
              moods: {
                data: [],
                range: 12,
                unit: 'months',
              },
              loadingMore: false,
            };
        },

        computed: {
            moodsLength() {
                return this.moods.data.length;
            }
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

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.getMoods();
            },

            drawChart() {
              var component = this;
              // component.chart = Highcharts.stockChart('mood-trends', {
              //     credits: {
              //       enabled: false
              //     },
              //     chart: {
              //         type: 'line',
              //         borderRadius: 5
              //     },
              //     rangeSelector: {
              //         allButtonsEnabled: true,
              //         selected: 2
              //     },
              //     title: {
              //         text: 'Mood Over Time'
              //     },
              //     subtitle: {
              //       text: ['Last', component.moods.range, component.moods.unit].join(' ')
              //     },
              //     xAxis: {
              //       type: 'datetime',
              //       dateTimeLabelFormats: {
              //          day: '%d %b %Y'
              //       }
              //     },
              //     yAxis: {
              //         title: {
              //             text: 'Mood'
              //         },
              //         labels: {
              //             formatter: function () {
              //                 return component.getMoodLabel(this.value);
              //             }
              //         }
              //     },
              //     tooltip: {
              //       formatter: function() {
              //           return '<b>' + component.getMoodLabel(this.y) + '</b> on ' + moment(this.x).format('MMMM Do YYYY');
              //       }
              //     },
              //     plotOptions: {
              //         area: {
              //             marker: {
              //                 enabled: false,
              //                 symbol: 'circle',
              //                 radius: 2,
              //                 states: {
              //                     hover: {
              //                         enabled: true
              //                     }
              //                 }
              //             }
              //         }
              //     },
              //     series: [{
              //         name: 'Mood',
              //         data: this.moods.data.map(function (data) {
              //           return [moment(data.date).valueOf(), data.rating];
              //         })
              //     }]
              // });
            },

            getMoodLabel(moodValue) {
              switch (moodValue) {
                case 1:
                  return 'Sad';
                case 2:
                  return 'Neutral';
                case 3:
                  return 'Happy';
              }
            },

            getMoods() {
                var component = this;
                component.loadingMore = true;

                try {
                  component.chart.destroy();
                } catch (e) {}

                axios.get('/journal/moods')
                        .then(response => {
                            this.moods = response.data;
                            component.loadingMore = false;
                            this.drawChart();
                        });
            },
        }
    }
</script>
