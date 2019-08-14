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
const card = elements.create('card', {
  hidePostalCode: true,
  style: style
});

// Add an instance of the card Element into the `card-element` <div>
card.mount('#card-element');

// Handle real-time validation errors from the card Element.
card.addEventListener('change', function(event) {
  const displayError = document.getElementById('card-errors');
  if (event.error) {
    displayError.textContent = event.error.message;
  } else {
    displayError.textContent = '';
  }
});

// Handle form submission
const cardButton = document.getElementById('card-button');
const clientSecret = cardButton.dataset.secret;

cardButton.addEventListener('click', async (e) => {
  e.preventDefault();

  const extraDetails = {
    name: this.querySelector('input[name=cardholder-name]').value,
    address_zip: this.querySelector('input[name=address-zip]').value,
  };
  const { setupIntent, error } = await stripe.handleCardSetup(
    clientSecret, card, {
      payment_method_data: {
        billing_details: extraDetails
      }
    }
  );

  if (error) {
    // Inform the user if there was an error
    const displayError = document.getElementById('card-errors');
    displayError.textContent = error;
  } else {
    // The card has been verified successfully...
    stripeSubmitForm(setupIntent);
  }
});

function stripeSubmitForm(setupIntent) {
  // Insert the token ID into the form so it gets submitted to the server
  const form = document.getElementById('payment-form');
  const hiddenInput = document.createElement('input');
  hiddenInput.setAttribute('type', 'hidden');
  hiddenInput.setAttribute('name', 'payment_method');
  hiddenInput.setAttribute('value', setupIntent.payment_method);
  form.appendChild(hiddenInput);

  // Submit the form
  form.submit();
}
