<style scoped>
</style>

<template>
    <div class="calendar">
        <div class="calendar-header">
            <i class="fa fa-fw fa-chevron-left" @click="subtractMonth"></i>
            <h4>{{month + ' - ' + year}}</h4>
            <i class="fa fa-fw fa-chevron-right" @click="addMonth"></i>
        </div>
        <ul class="weekdays">
            <li v-for="day in days"></li>
        </ul>
        <ul class="dates">
            <li v-for="blank in firstDayOfMonth">&nbsp;</li>
            <li v-for="date in daysInMonth"
        		:class="{'current-day': date == initialDate &amp;&amp; month == initialMonth && year == initialYear}">;
                <span></span>
           </li>
        </ul>
    </div>
</template>

<script>
export default {
  data(){
    return{
      today: moment(),
      dateContext: moment(),
      days: ['S', 'M', 'T', 'W', 'T', 'F', 'S']
    }
  },

  mounted() {
    this.prepareComponent();
  },

  computed: {
    year: function () {
        var t = this;
        return t.dateContext.format('Y');
    },
    month: function () {
        var t = this;
        return t.dateContext.format('MMMM');
    },
    daysInMonth: function () {
        var t = this;
        return t.dateContext.daysInMonth();
    },
    currentDate: function () {
        var t = this;
        return t.dateContext.get('date');
    },
    firstDayOfMonth: function () {
        var t = this;
        var firstDay = moment(t.dateContext).subtract((t.currentDate - 1), 'days');
        return firstDay.weekday();
    },
    initialDate: function () {
        var t = this;
        return t.today.get('date');
    },
    initialMonth: function () {
        var t = this;
        return t.today.format('MMMM');
    },
    initialYear: function () {
        var t = this;
        return t.today.format('Y');
    }
  },

  methods: {
    addMonth: function () {
        var t = this;
        t.dateContext = moment(t.dateContext).add(1, 'month');
    },
    subtractMonth: function () {
        var t = this;
        t.dateContext = moment(t.dateContext).subtract(1, 'month');
    }
  }
};
</script>
