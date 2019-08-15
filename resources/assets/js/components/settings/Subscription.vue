<style scoped>
</style>

<template>
  <form ref="form" class="mb4" :action="callback" method="post" @submit.prevent="subscribe()">
    <notifications group="subscription" position="top middle" :duration="5000" width="400" />

    <div class="form-group">
      <div v-if="errors" role="alert" class="alert alert-danger w-100">
        {{ errors }}
      </div>

      <div v-if="paymentSucceeded">
        <h1 class="text-xl mt-2 mb-4 text-gray-700">
          {{ $t('settings.subscriptions_payment_succeeded_title') }}
        </h1>
        <p v-if="successMessage" class="mb-6">
          {{ successMessage }}
        </p>
        <p v-else class="mb-6">
          {{ $t('settings.subscriptions_payment_succeeded') }}
        </p>
      </div>

      <div v-else-if="paymentCancelled">
        <h1 class="text-xl mt-2 mb-4 text-gray-700">
          {{ $t('settings.subscriptions_payment_cancelled_title') }}
        </h1>

        <p class="mb-6">
          {{ $t('settings.subscriptions_payment_cancelled') }}
        </p>
      </div>

      <div v-else-if="! paymentProcessed" id="payment-elements" class="b--gray-monica ba pa4 br2 mb3 bg-black-05">
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

          <label for="card-element">
            {{ $t('settings.subscriptions_upgrade_credit') }}
          </label>
          <div id="card-element">
            <!-- a Stripe Element will be inserted here. -->
          </div>
        </div>

        <button
          id="card-button"
          class="btn btn-primary w-100 mt3"
          :disabled="paymentProcessing"
          @click.prevent="confirm ? confirmPayment() : subscribe()"
          v-html="$t('settings.subscriptions_upgrade_submit', { amount: amount })"
        >
        </button>
      </div>
      <a v-if="paymentProcessed" :href="callback"
         class="btn btn-secondary w-100 tc"
      >
        {{ $t('app.go_back') }}
      </a>
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
    stripeKey: {
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
    amount: {
      type: String,
      default: '',
    },
    callback: {
      type: String,
      default: '',
    },
    confirm: {
      type: Boolean,
      default: false,
    },
    paymentSucceeded: {
      type: Boolean,
      default: false,
    },
    paymentCancelled: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      stripe: null,
      zip: '',
      errors: '',
      successMessage: '',
      cardElement: null,
      paymentMethod: '',
      token: '',
      paymentProcessing: false,
      paymentProcessed: false,
    };
  },

  mounted() {
    this.token = document.head.querySelector('meta[name="csrf-token"]').content;
    if (this.paymentSucceeded || this.paymentCancelled) {
      this.paymentProcessed = true;
    }
    if (! this.paymentProcessed) {
      this.start();
    }
  },

  methods: {
    start() {
      this.stripe = Stripe(this.stripeKey);

      const elements = this.stripe.elements();

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
      this.cardElement = elements.create('card', {
        hidePostalCode: true,
        style: style
      });

      // Add an instance of the card Element into the `card-element` <div>
      this.cardElement.mount('#card-element');

      // Handle real-time validation errors from the card Element.
      var self = this;
      this.cardElement.addEventListener('change', function(event) {
        if (event.error) {
          self.errors = event.error.message;
        } else {
          self.errors = '';
        }
      });
    },

    handleError(error) {
      if (error.code === 'parameter_invalid_empty' &&
            error.param === 'payment_method_data[billing_details][name]') {
        this.errors = this.$t('settings.subscriptions_payment_error_name');
      } else {
        this.errors = error.message;
      }
    },

    subscribe() {
      var self = this;

      this.errors = '';
      this.paymentProcessing = true;
      this.paymentProcessed = false;

      this.stripe.handleCardSetup(
        self.clientSecret,
        self.cardElement,
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
        self.paymentProcessing = false;
        if (result.error) {
          self.handleError(result.error);
        } else {
          // The card has been verified successfully...
          self.paymentProcessed = true;
          self.paymentSucceeded = true;
          self.successMessage = self.$t('settings.subscriptions_payment_success');
          self.notify(self.successMessage, true);
          self.processPayment(result.setupIntent);
        }
      });
    },

    processPayment(setupIntent) {
      var self = this;
      this.paymentMethod = setupIntent.payment_method;
      setTimeout(function () {
        self.$refs.form.submit();
      }, 10);
    },

    confirmPayment() {
      var self = this;

      this.paymentProcessing = true;
      this.paymentProcessed = false;
      this.errorMessage = '';

      this.stripe.handleCardPayment(
        self.clientSecret, self.cardElement, {
          payment_method_data: {
            billing_details: { name: this.name }
          }
        }
      ).then(function (result) {
        self.paymentProcessing = false;

        if (result.error) {
          self.handleError(result.error);
        } else {
          self.paymentProcessed = true;
          self.paymentSucceeded = true;
          self.successMessage = self.$t('settings.subscriptions_payment_success');
          self.notify(self.successMessage, true);
          setTimeout(function () {
            window.location = self.callback;
          }, 3000);
        }
      });
    },

    notify(text, success) {
      this.$notify({
        group: 'subscription',
        title: text,
        text: '',
        type: success ? 'success' : 'error'
      });
    }
  }
};
</script>
