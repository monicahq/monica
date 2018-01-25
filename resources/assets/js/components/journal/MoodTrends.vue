<style scoped>

</style>

<template>
  <div class="mw12 center pa2">
    <div class="load-data" v-if="loadingMore"></div>
    <canvas id="mood-trends" width="100%"></canvas>
  </div>
</template>

<script>
    const Chart = require('chart.js');
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
              Chart.defaults.line.spanGaps = true;
              component.chart = new Chart(document.getElementById('mood-trends'), {
                  type: 'line',
                  data: {
                      labels: this.moods.data.map(function (data) {
                        return moment(data.date).toDate();
                      }),
                      datasets: [{
                          label: 'Mood',
                          data: this.moods.data.map(function (data) {
                            return data.rating;
                          }),
                          fill: false,
                          backgroundColor: 'rgba(134, 190, 253, 1)',
                          borderColor: 'rgba(134, 190, 253, 0.5)',
                          borderWidth: 1
                      }]
                  },
                  options: {
                    responsive: true,
                    title:{
                        display: true,
                        text:'Mood Over Time'
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(tooltipItems, data) {
                                return component.getMoodLabel(tooltipItems.yLabel);
                            }
                        }
                    },
                    hover: {
                        mode: 'nearest',
                        intersect: true
                    },
                    scales: {
                        xAxes: [{
                            type: "time",
                            display: true,
                            scaleLabel: {
                                display: false,
                                labelString: 'Date'
                            },
                            time: {
                                tooltipFormat: 'll'
                            }
                        }],
                        yAxes: [{
                            type: 'linear',
                            display: true,
                            scaleLabel: {
                                display: false,
                                labelString: 'Mood'
                            },
                            ticks: {
                                stepSize: 1,
                                min: 0,
                                max: 4,
                                beginAtZero:true,
                                callback: function(value, index, values) {
                                    return component.getMoodLabel(value);
                                }
                            }
                        }]
                    }
                  }
              });
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
