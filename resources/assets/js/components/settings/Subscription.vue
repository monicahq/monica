<style scoped>
</style>

<template>
  <form ref="form" class="mb4" :action="callback" method="post" @submit.prevent="subscribe()">
    <div class="form-group">
      <div class="b--gray-monica ba pa4 br2 mb3 bg-black-05">
        <div class="form-row">
          <div class="mb3">
            <form-input
              :id="'cardholder-name'"
              v-model="name"
              :input-type="'text'"
              :iclass="'br3 b--black-30 ba pa3 w-100 f4'"
              :required="true"
              :title="$t('settings.subscriptions_upgrade_name')"
            />
          </div>

          <div class="mb3">
            <form-input
              :id="'address-zip'"
              v-model="zip"
              :input-type="'text'"
              :iclass="'br3 b--black-30 ba pa3 w-100 f4'"
              :title="$t('settings.subscriptions_upgrade_zip')"
            />
          </div>

          <div class="mb3">
            <label for="card-element">
              {{ $t('settings.subscriptions_upgrade_credit') }}
            </label>
          </div>

          <div id="card-element" ref="card-element">
            <!-- a Stripe Element will be inserted here. -->
          </div>

          <!-- Used to display Element errors -->
          <div v-if="errors" role="alert" class="alert alert-danger w-100">
            {{ errors }}
          </div>
        </div>
      </div>

      <button id="card-button" class="btn btn-primary w-100" @click.prevent="subscribe()">
        {{ $t('settings.subscriptions_upgrade_submit') }}
      </button>
    </div>
    <input type="hidden" name="_token" :value="token" />
    <input type="hidden" name="plan" :value="plan" />
    <input type="hidden" name="payment_method" :value="paymentMethod" />
  </form>
</template>

<script>
import { setTimeout } from 'timers';
export default {

  props: {
    name: {
      type: String,
      default: '',
    },
    clientSecret: {
      type: String,
      default: '',
    },
    plan: {
      type: String,
      default: '',
    },
    callback: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      zip: '',
      errors: '',
      card: null,
      paymentMethod: '',
      token: '',
    };
  },

  mounted() {
    this.start();
    this.token = document.head.querySelector('meta[name="csrf-token"]').content;
  },

  methods: {
    start() {
      const elements = stripe.elements();

      // Custom styling can be passed to options when creating an Element.
      // (Note that this demo uses a wider set of styles than the guide below.)
      const style = {
        base: {
          color: '#32325d',
          lineHeight: '18px',
          fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
          fontSmoothing: 'antialiased',
          fontSize: '16px',
          '::placeholder': {
            color: '#aab7c4'
          }
        },
        invalid: {
          color: '#fa755a',
          iconColor: '#fa755a'
        }
      };

      // Create an instance of the card Element
      this.card = elements.create('card', {
        hidePostalCode: true,
        style: style
      });

      // Add an instance of the card Element into the `card-element` <div>
      this.card.mount('#card-element');

      // Handle real-time validation errors from the card Element.
      var self = this;
      this.card.addEventListener('change', function(event) {
        if (event.error) {
          self.errors = event.error.message;
        } else {
          self.errors = '';
        }
      });
    },

    subscribe() {
      this.errors = '';
      var self = this;
      stripe.handleCardSetup(
        self.clientSecret,
        self.card,
        {
          payment_method_data: {
            billing_details: {
              name: self.name,
              address: {
                postal_code: self.zip,
              }
            }
          }
        }
      ).then(function (result) {
        if (result.error) {
          self.errors = result.error.message;
        } else {
          // The card has been verified successfully...
          self.processPayment(result.setupIntent);
        }
      });
    },

    processPayment(setupIntent) {
      var self = this;
      this.paymentMethod = setupIntent.payment_method;
      setTimeout(function () {
        self.$refs.form.submit();
      });
    }
  }
};
</script>
