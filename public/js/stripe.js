/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 2);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./resources/assets/js/stripe.js":
/***/ (function(module, exports) {

var elements = stripe.elements();

// Custom styling can be passed to options when creating an Element.
// (Note that this demo uses a wider set of styles than the guide below.)
var style = {
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
var card = elements.create('card', {
  hidePostalCode: true,
  style: style
});

// Add an instance of the card Element into the `card-element` <div>
card.mount('#card-element');

// Handle real-time validation errors from the card Element.
card.addEventListener('change', function (event) {
  var displayError = document.getElementById('card-errors');
  if (event.error) {
    displayError.textContent = event.error.message;
  } else {
    displayError.textContent = '';
  }
});

// Handle form submission
var form = document.getElementById('payment-form');
form.addEventListener('submit', function (event) {
  event.preventDefault();

  var extraDetails = {
    name: this.querySelector('input[name=cardholder-name]').value,
    address_zip: this.querySelector('input[name=address-zip]').value
  };

  stripe.createToken(card, extraDetails).then(function (result) {
    if (result.error) {
      // Inform the user if there was an error
      var errorElement = document.getElementById('card-errors');
      errorElement.textContent = result.error.message;
    } else {
      // Send the token to your server
      stripeTokenHandler(result.token);
    }
  });
});

function stripeTokenHandler(token) {
  // Insert the token ID into the form so it gets submitted to the server
  var form = document.getElementById('payment-form');
  var hiddenInput = document.createElement('input');
  hiddenInput.setAttribute('type', 'hidden');
  hiddenInput.setAttribute('name', 'stripeToken');
  hiddenInput.setAttribute('value', token.id);
  form.appendChild(hiddenInput);

  // Submit the form
  form.submit();
}

/***/ }),

/***/ 2:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__("./resources/assets/js/stripe.js");


/***/ })

/******/ });
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vd2VicGFjay9ib290c3RyYXAgMWJlMDk4N2Q1YzY2YjI5M2UxYmEiLCJ3ZWJwYWNrOi8vLy4vcmVzb3VyY2VzL2Fzc2V0cy9qcy9zdHJpcGUuanMiXSwibmFtZXMiOlsiZWxlbWVudHMiLCJzdHJpcGUiLCJzdHlsZSIsImJhc2UiLCJjb2xvciIsImxpbmVIZWlnaHQiLCJmb250RmFtaWx5IiwiZm9udFNtb290aGluZyIsImZvbnRTaXplIiwiaW52YWxpZCIsImljb25Db2xvciIsImNhcmQiLCJjcmVhdGUiLCJoaWRlUG9zdGFsQ29kZSIsIm1vdW50IiwiYWRkRXZlbnRMaXN0ZW5lciIsImV2ZW50IiwiZGlzcGxheUVycm9yIiwiZG9jdW1lbnQiLCJnZXRFbGVtZW50QnlJZCIsImVycm9yIiwidGV4dENvbnRlbnQiLCJtZXNzYWdlIiwiZm9ybSIsInByZXZlbnREZWZhdWx0IiwiZXh0cmFEZXRhaWxzIiwibmFtZSIsInF1ZXJ5U2VsZWN0b3IiLCJ2YWx1ZSIsImFkZHJlc3NfemlwIiwiY3JlYXRlVG9rZW4iLCJ0aGVuIiwicmVzdWx0IiwiZXJyb3JFbGVtZW50Iiwic3RyaXBlVG9rZW5IYW5kbGVyIiwidG9rZW4iLCJoaWRkZW5JbnB1dCIsImNyZWF0ZUVsZW1lbnQiLCJzZXRBdHRyaWJ1dGUiLCJpZCIsImFwcGVuZENoaWxkIiwic3VibWl0Il0sIm1hcHBpbmdzIjoiO0FBQUE7QUFDQTs7QUFFQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7O0FBRUE7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7OztBQUdBO0FBQ0E7O0FBRUE7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLGFBQUs7QUFDTDtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBLG1DQUEyQiwwQkFBMEIsRUFBRTtBQUN2RCx5Q0FBaUMsZUFBZTtBQUNoRDtBQUNBO0FBQ0E7O0FBRUE7QUFDQSw4REFBc0QsK0RBQStEOztBQUVySDtBQUNBOztBQUVBO0FBQ0E7Ozs7Ozs7O0FDN0RBLElBQUlBLFdBQVdDLE9BQU9ELFFBQVAsRUFBZjs7QUFFQTtBQUNBO0FBQ0EsSUFBSUUsUUFBUTtBQUNWQyxRQUFNO0FBQ0pDLFdBQU8sU0FESDtBQUVKQyxnQkFBWSxNQUZSO0FBR0pDLGdCQUFZLHlDQUhSO0FBSUpDLG1CQUFlLGFBSlg7QUFLSkMsY0FBVSxNQUxOO0FBTUoscUJBQWlCO0FBQ2ZKLGFBQU87QUFEUTtBQU5iLEdBREk7QUFXVkssV0FBUztBQUNQTCxXQUFPLFNBREE7QUFFUE0sZUFBVztBQUZKO0FBWEMsQ0FBWjs7QUFpQkE7QUFDQSxJQUFJQyxPQUFPWCxTQUFTWSxNQUFULENBQWdCLE1BQWhCLEVBQXdCO0FBQ2pDQyxrQkFBZ0IsSUFEaUI7QUFFakNYLFNBQU9BO0FBRjBCLENBQXhCLENBQVg7O0FBS0E7QUFDQVMsS0FBS0csS0FBTCxDQUFXLGVBQVg7O0FBRUE7QUFDQUgsS0FBS0ksZ0JBQUwsQ0FBc0IsUUFBdEIsRUFBZ0MsVUFBU0MsS0FBVCxFQUFnQjtBQUM5QyxNQUFJQyxlQUFlQyxTQUFTQyxjQUFULENBQXdCLGFBQXhCLENBQW5CO0FBQ0EsTUFBSUgsTUFBTUksS0FBVixFQUFpQjtBQUNmSCxpQkFBYUksV0FBYixHQUEyQkwsTUFBTUksS0FBTixDQUFZRSxPQUF2QztBQUNELEdBRkQsTUFFTztBQUNMTCxpQkFBYUksV0FBYixHQUEyQixFQUEzQjtBQUNEO0FBQ0YsQ0FQRDs7QUFTQTtBQUNBLElBQUlFLE9BQU9MLFNBQVNDLGNBQVQsQ0FBd0IsY0FBeEIsQ0FBWDtBQUNBSSxLQUFLUixnQkFBTCxDQUFzQixRQUF0QixFQUFnQyxVQUFTQyxLQUFULEVBQWdCO0FBQzlDQSxRQUFNUSxjQUFOOztBQUVBLE1BQUlDLGVBQWU7QUFDakJDLFVBQU0sS0FBS0MsYUFBTCxDQUFtQiw2QkFBbkIsRUFBa0RDLEtBRHZDO0FBRWpCQyxpQkFBYSxLQUFLRixhQUFMLENBQW1CLHlCQUFuQixFQUE4Q0M7QUFGMUMsR0FBbkI7O0FBS0EzQixTQUFPNkIsV0FBUCxDQUFtQm5CLElBQW5CLEVBQXlCYyxZQUF6QixFQUF1Q00sSUFBdkMsQ0FBNEMsVUFBU0MsTUFBVCxFQUFpQjtBQUMzRCxRQUFJQSxPQUFPWixLQUFYLEVBQWtCO0FBQ2hCO0FBQ0EsVUFBSWEsZUFBZWYsU0FBU0MsY0FBVCxDQUF3QixhQUF4QixDQUFuQjtBQUNBYyxtQkFBYVosV0FBYixHQUEyQlcsT0FBT1osS0FBUCxDQUFhRSxPQUF4QztBQUNELEtBSkQsTUFJTztBQUNMO0FBQ0FZLHlCQUFtQkYsT0FBT0csS0FBMUI7QUFDRDtBQUNGLEdBVEQ7QUFVRCxDQWxCRDs7QUFvQkEsU0FBU0Qsa0JBQVQsQ0FBNEJDLEtBQTVCLEVBQW1DO0FBQ2pDO0FBQ0EsTUFBSVosT0FBT0wsU0FBU0MsY0FBVCxDQUF3QixjQUF4QixDQUFYO0FBQ0EsTUFBSWlCLGNBQWNsQixTQUFTbUIsYUFBVCxDQUF1QixPQUF2QixDQUFsQjtBQUNBRCxjQUFZRSxZQUFaLENBQXlCLE1BQXpCLEVBQWlDLFFBQWpDO0FBQ0FGLGNBQVlFLFlBQVosQ0FBeUIsTUFBekIsRUFBaUMsYUFBakM7QUFDQUYsY0FBWUUsWUFBWixDQUF5QixPQUF6QixFQUFrQ0gsTUFBTUksRUFBeEM7QUFDQWhCLE9BQUtpQixXQUFMLENBQWlCSixXQUFqQjs7QUFFQTtBQUNBYixPQUFLa0IsTUFBTDtBQUNELEMiLCJmaWxlIjoiL2pzL3N0cmlwZS5qcyIsInNvdXJjZXNDb250ZW50IjpbIiBcdC8vIFRoZSBtb2R1bGUgY2FjaGVcbiBcdHZhciBpbnN0YWxsZWRNb2R1bGVzID0ge307XG5cbiBcdC8vIFRoZSByZXF1aXJlIGZ1bmN0aW9uXG4gXHRmdW5jdGlvbiBfX3dlYnBhY2tfcmVxdWlyZV9fKG1vZHVsZUlkKSB7XG5cbiBcdFx0Ly8gQ2hlY2sgaWYgbW9kdWxlIGlzIGluIGNhY2hlXG4gXHRcdGlmKGluc3RhbGxlZE1vZHVsZXNbbW9kdWxlSWRdKSB7XG4gXHRcdFx0cmV0dXJuIGluc3RhbGxlZE1vZHVsZXNbbW9kdWxlSWRdLmV4cG9ydHM7XG4gXHRcdH1cbiBcdFx0Ly8gQ3JlYXRlIGEgbmV3IG1vZHVsZSAoYW5kIHB1dCBpdCBpbnRvIHRoZSBjYWNoZSlcbiBcdFx0dmFyIG1vZHVsZSA9IGluc3RhbGxlZE1vZHVsZXNbbW9kdWxlSWRdID0ge1xuIFx0XHRcdGk6IG1vZHVsZUlkLFxuIFx0XHRcdGw6IGZhbHNlLFxuIFx0XHRcdGV4cG9ydHM6IHt9XG4gXHRcdH07XG5cbiBcdFx0Ly8gRXhlY3V0ZSB0aGUgbW9kdWxlIGZ1bmN0aW9uXG4gXHRcdG1vZHVsZXNbbW9kdWxlSWRdLmNhbGwobW9kdWxlLmV4cG9ydHMsIG1vZHVsZSwgbW9kdWxlLmV4cG9ydHMsIF9fd2VicGFja19yZXF1aXJlX18pO1xuXG4gXHRcdC8vIEZsYWcgdGhlIG1vZHVsZSBhcyBsb2FkZWRcbiBcdFx0bW9kdWxlLmwgPSB0cnVlO1xuXG4gXHRcdC8vIFJldHVybiB0aGUgZXhwb3J0cyBvZiB0aGUgbW9kdWxlXG4gXHRcdHJldHVybiBtb2R1bGUuZXhwb3J0cztcbiBcdH1cblxuXG4gXHQvLyBleHBvc2UgdGhlIG1vZHVsZXMgb2JqZWN0IChfX3dlYnBhY2tfbW9kdWxlc19fKVxuIFx0X193ZWJwYWNrX3JlcXVpcmVfXy5tID0gbW9kdWxlcztcblxuIFx0Ly8gZXhwb3NlIHRoZSBtb2R1bGUgY2FjaGVcbiBcdF9fd2VicGFja19yZXF1aXJlX18uYyA9IGluc3RhbGxlZE1vZHVsZXM7XG5cbiBcdC8vIGRlZmluZSBnZXR0ZXIgZnVuY3Rpb24gZm9yIGhhcm1vbnkgZXhwb3J0c1xuIFx0X193ZWJwYWNrX3JlcXVpcmVfXy5kID0gZnVuY3Rpb24oZXhwb3J0cywgbmFtZSwgZ2V0dGVyKSB7XG4gXHRcdGlmKCFfX3dlYnBhY2tfcmVxdWlyZV9fLm8oZXhwb3J0cywgbmFtZSkpIHtcbiBcdFx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgbmFtZSwge1xuIFx0XHRcdFx0Y29uZmlndXJhYmxlOiBmYWxzZSxcbiBcdFx0XHRcdGVudW1lcmFibGU6IHRydWUsXG4gXHRcdFx0XHRnZXQ6IGdldHRlclxuIFx0XHRcdH0pO1xuIFx0XHR9XG4gXHR9O1xuXG4gXHQvLyBnZXREZWZhdWx0RXhwb3J0IGZ1bmN0aW9uIGZvciBjb21wYXRpYmlsaXR5IHdpdGggbm9uLWhhcm1vbnkgbW9kdWxlc1xuIFx0X193ZWJwYWNrX3JlcXVpcmVfXy5uID0gZnVuY3Rpb24obW9kdWxlKSB7XG4gXHRcdHZhciBnZXR0ZXIgPSBtb2R1bGUgJiYgbW9kdWxlLl9fZXNNb2R1bGUgP1xuIFx0XHRcdGZ1bmN0aW9uIGdldERlZmF1bHQoKSB7IHJldHVybiBtb2R1bGVbJ2RlZmF1bHQnXTsgfSA6XG4gXHRcdFx0ZnVuY3Rpb24gZ2V0TW9kdWxlRXhwb3J0cygpIHsgcmV0dXJuIG1vZHVsZTsgfTtcbiBcdFx0X193ZWJwYWNrX3JlcXVpcmVfXy5kKGdldHRlciwgJ2EnLCBnZXR0ZXIpO1xuIFx0XHRyZXR1cm4gZ2V0dGVyO1xuIFx0fTtcblxuIFx0Ly8gT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsXG4gXHRfX3dlYnBhY2tfcmVxdWlyZV9fLm8gPSBmdW5jdGlvbihvYmplY3QsIHByb3BlcnR5KSB7IHJldHVybiBPYmplY3QucHJvdG90eXBlLmhhc093blByb3BlcnR5LmNhbGwob2JqZWN0LCBwcm9wZXJ0eSk7IH07XG5cbiBcdC8vIF9fd2VicGFja19wdWJsaWNfcGF0aF9fXG4gXHRfX3dlYnBhY2tfcmVxdWlyZV9fLnAgPSBcIlwiO1xuXG4gXHQvLyBMb2FkIGVudHJ5IG1vZHVsZSBhbmQgcmV0dXJuIGV4cG9ydHNcbiBcdHJldHVybiBfX3dlYnBhY2tfcmVxdWlyZV9fKF9fd2VicGFja19yZXF1aXJlX18ucyA9IDIpO1xuXG5cblxuLy8gV0VCUEFDSyBGT09URVIgLy9cbi8vIHdlYnBhY2svYm9vdHN0cmFwIDFiZTA5ODdkNWM2NmIyOTNlMWJhIiwidmFyIGVsZW1lbnRzID0gc3RyaXBlLmVsZW1lbnRzKCk7XG5cbi8vIEN1c3RvbSBzdHlsaW5nIGNhbiBiZSBwYXNzZWQgdG8gb3B0aW9ucyB3aGVuIGNyZWF0aW5nIGFuIEVsZW1lbnQuXG4vLyAoTm90ZSB0aGF0IHRoaXMgZGVtbyB1c2VzIGEgd2lkZXIgc2V0IG9mIHN0eWxlcyB0aGFuIHRoZSBndWlkZSBiZWxvdy4pXG52YXIgc3R5bGUgPSB7XG4gIGJhc2U6IHtcbiAgICBjb2xvcjogJyMzMjMyNWQnLFxuICAgIGxpbmVIZWlnaHQ6ICcxOHB4JyxcbiAgICBmb250RmFtaWx5OiAnXCJIZWx2ZXRpY2EgTmV1ZVwiLCBIZWx2ZXRpY2EsIHNhbnMtc2VyaWYnLFxuICAgIGZvbnRTbW9vdGhpbmc6ICdhbnRpYWxpYXNlZCcsXG4gICAgZm9udFNpemU6ICcxNnB4JyxcbiAgICAnOjpwbGFjZWhvbGRlcic6IHtcbiAgICAgIGNvbG9yOiAnI2FhYjdjNCdcbiAgICB9XG4gIH0sXG4gIGludmFsaWQ6IHtcbiAgICBjb2xvcjogJyNmYTc1NWEnLFxuICAgIGljb25Db2xvcjogJyNmYTc1NWEnXG4gIH1cbn07XG5cbi8vIENyZWF0ZSBhbiBpbnN0YW5jZSBvZiB0aGUgY2FyZCBFbGVtZW50XG52YXIgY2FyZCA9IGVsZW1lbnRzLmNyZWF0ZSgnY2FyZCcsIHtcbiAgaGlkZVBvc3RhbENvZGU6IHRydWUsXG4gIHN0eWxlOiBzdHlsZVxufSk7XG5cbi8vIEFkZCBhbiBpbnN0YW5jZSBvZiB0aGUgY2FyZCBFbGVtZW50IGludG8gdGhlIGBjYXJkLWVsZW1lbnRgIDxkaXY+XG5jYXJkLm1vdW50KCcjY2FyZC1lbGVtZW50Jyk7XG5cbi8vIEhhbmRsZSByZWFsLXRpbWUgdmFsaWRhdGlvbiBlcnJvcnMgZnJvbSB0aGUgY2FyZCBFbGVtZW50LlxuY2FyZC5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCBmdW5jdGlvbihldmVudCkge1xuICB2YXIgZGlzcGxheUVycm9yID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2NhcmQtZXJyb3JzJyk7XG4gIGlmIChldmVudC5lcnJvcikge1xuICAgIGRpc3BsYXlFcnJvci50ZXh0Q29udGVudCA9IGV2ZW50LmVycm9yLm1lc3NhZ2U7XG4gIH0gZWxzZSB7XG4gICAgZGlzcGxheUVycm9yLnRleHRDb250ZW50ID0gJyc7XG4gIH1cbn0pO1xuXG4vLyBIYW5kbGUgZm9ybSBzdWJtaXNzaW9uXG52YXIgZm9ybSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdwYXltZW50LWZvcm0nKTtcbmZvcm0uYWRkRXZlbnRMaXN0ZW5lcignc3VibWl0JywgZnVuY3Rpb24oZXZlbnQpIHtcbiAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcblxuICB2YXIgZXh0cmFEZXRhaWxzID0ge1xuICAgIG5hbWU6IHRoaXMucXVlcnlTZWxlY3RvcignaW5wdXRbbmFtZT1jYXJkaG9sZGVyLW5hbWVdJykudmFsdWUsXG4gICAgYWRkcmVzc196aXA6IHRoaXMucXVlcnlTZWxlY3RvcignaW5wdXRbbmFtZT1hZGRyZXNzLXppcF0nKS52YWx1ZSxcbiAgfTtcblxuICBzdHJpcGUuY3JlYXRlVG9rZW4oY2FyZCwgZXh0cmFEZXRhaWxzKS50aGVuKGZ1bmN0aW9uKHJlc3VsdCkge1xuICAgIGlmIChyZXN1bHQuZXJyb3IpIHtcbiAgICAgIC8vIEluZm9ybSB0aGUgdXNlciBpZiB0aGVyZSB3YXMgYW4gZXJyb3JcbiAgICAgIHZhciBlcnJvckVsZW1lbnQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY2FyZC1lcnJvcnMnKTtcbiAgICAgIGVycm9yRWxlbWVudC50ZXh0Q29udGVudCA9IHJlc3VsdC5lcnJvci5tZXNzYWdlO1xuICAgIH0gZWxzZSB7XG4gICAgICAvLyBTZW5kIHRoZSB0b2tlbiB0byB5b3VyIHNlcnZlclxuICAgICAgc3RyaXBlVG9rZW5IYW5kbGVyKHJlc3VsdC50b2tlbik7XG4gICAgfVxuICB9KTtcbn0pO1xuXG5mdW5jdGlvbiBzdHJpcGVUb2tlbkhhbmRsZXIodG9rZW4pIHtcbiAgLy8gSW5zZXJ0IHRoZSB0b2tlbiBJRCBpbnRvIHRoZSBmb3JtIHNvIGl0IGdldHMgc3VibWl0dGVkIHRvIHRoZSBzZXJ2ZXJcbiAgdmFyIGZvcm0gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgncGF5bWVudC1mb3JtJyk7XG4gIHZhciBoaWRkZW5JbnB1dCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2lucHV0Jyk7XG4gIGhpZGRlbklucHV0LnNldEF0dHJpYnV0ZSgndHlwZScsICdoaWRkZW4nKTtcbiAgaGlkZGVuSW5wdXQuc2V0QXR0cmlidXRlKCduYW1lJywgJ3N0cmlwZVRva2VuJyk7XG4gIGhpZGRlbklucHV0LnNldEF0dHJpYnV0ZSgndmFsdWUnLCB0b2tlbi5pZCk7XG4gIGZvcm0uYXBwZW5kQ2hpbGQoaGlkZGVuSW5wdXQpO1xuXG4gIC8vIFN1Ym1pdCB0aGUgZm9ybVxuICBmb3JtLnN1Ym1pdCgpO1xufVxuXG5cblxuLy8gV0VCUEFDSyBGT09URVIgLy9cbi8vIC4vcmVzb3VyY2VzL2Fzc2V0cy9qcy9zdHJpcGUuanMiXSwic291cmNlUm9vdCI6IiJ9