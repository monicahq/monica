<style lang="scss" scoped>
  .avatar-new {
    background-color: #fdb660;
  }
  .item-search-result {
    position: relative;
    background: transparent;
    border: 1px solid #c4cdd5;

    .item-search-result-result {
      color: inherit;
      vertical-align: middle;
      background: transparent;

      span {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;

        z-index: 1;
      }
    }

    .avatar {
      border-radius: 3px;
      display: inline-block;
      height: 36px;
      margin: 10px;
      width: 36px;
    }

    .avatar-initials {
      text-align: center;
      padding-top: 6px;
      font-size: 15px;
      color: #fff;
    }

    .avatar-no-results {
      color: #fdb660;
    }

    &:first-child {
      border-top: initial;
    }
  }

</style>

<template>
  <div v-if="item.id > 0" class="item-search-result" :data-contact="item.id" :data-name="item.name">
    <div class="item-search-result-result pointer">
      <img v-if="check"
           class="avatar"
           :src="item.information.avatar.url"
           :alt="item.complete_name"
           @error="check=false"
      />
      <div v-else
           class="avatar avatar-initials"
           :style="'background-color: '+item.information.avatar.default_avatar_color"
      >
        {{ item.initials }}
      </div>
      {{ item.complete_name }}
      <span></span>
    </div>
  </div>
  <div v-else class="item-search-result">
    <div class="item-search-result-result pointer">
      <div class="avatar avatar-initials avatar-no-results">
        .
      </div>
      {{ $t('people.people_search_no_results') }}
      <span></span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    item: {
      type: Object,
      required: true,
      default: null,
    },
  },
  data() {
    return {
      check: true,
    };
  },
};
</script>
