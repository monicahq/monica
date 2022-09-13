/**
 * WebAuthn client.
 *
 * This file is part of asbiin/laravel-webauthn project.
 *
 * @copyright Alexis SAETTLER © 2019
 * @license MIT
 */

'use strict';

/**
 * Create a new instance of WebAuthn.
 *
 * @param {function(string, bool)} notifyCallback
 * @constructor
 */
function WebAuthn(notifyCallback = null) {
  if (notifyCallback) {
    this.setNotify(notifyCallback);
  }
}

/**
 * Register a new key.
 *
 * @param {PublicKeyCredentialCreationOptions} publicKey  - see https://www.w3.org/TR/webauthn/#dictdef-publickeycredentialcreationoptions
 * @param {function(PublicKeyCredential)} callback  User callback
 */
WebAuthn.prototype.register = function (publicKey, callback) {
  const publicKeyCredential = Object.assign({}, publicKey);
  publicKeyCredential.user.id = this._bufferDecode(publicKey.user.id);
  publicKeyCredential.challenge = this._bufferDecode(this._base64Decode(publicKey.challenge));
  if (publicKey.excludeCredentials) {
    publicKeyCredential.excludeCredentials = this._credentialDecode(publicKey.excludeCredentials);
  }

  const self = this;
  navigator.credentials
    .create({
      publicKey: publicKeyCredential,
    })
    .then(
      (data) => {
        self._registerCallback(data, callback);
      },
      (error) => {
        self._notify(error.name, error.message, false);
      },
    );
};

/**
 * Register callback on register key.
 *
 * @param {PublicKeyCredential} publicKey @see https://www.w3.org/TR/webauthn/#publickeycredential
 * @param {function(PublicKeyCredential)} callback  User callback
 */
WebAuthn.prototype._registerCallback = function (publicKey, callback) {
  const publicKeyCredential = {
    id: publicKey.id,
    type: publicKey.type,
    rawId: this._bufferEncode(publicKey.rawId),
    response: {
      /** @see https://www.w3.org/TR/webauthn/#authenticatorattestationresponse */
      clientDataJSON: this._bufferEncode(publicKey.response.clientDataJSON),
      attestationObject: this._bufferEncode(publicKey.response.attestationObject),
    },
  };

  callback(publicKeyCredential);
};

/**
 * Authenticate a user.
 *
 * @param {PublicKeyCredentialRequestOptions} publicKey  - see https://www.w3.org/TR/webauthn/#dictdef-publickeycredentialrequestoptions
 * @param {function(PublicKeyCredential)} callback  User callback
 */
WebAuthn.prototype.sign = function (publicKey, callback) {
  const publicKeyCredential = Object.assign({}, publicKey);
  publicKeyCredential.challenge = this._bufferDecode(this._base64Decode(publicKey.challenge));
  if (publicKey.allowCredentials) {
    publicKeyCredential.allowCredentials = this._credentialDecode(publicKey.allowCredentials);
  }

  const self = this;
  navigator.credentials
    .get({
      publicKey: publicKeyCredential,
    })
    .then(
      (data) => {
        self._signCallback(data, callback);
      },
      (error) => {
        self._notify(error.name, error.message, false);
      },
    );
};

/**
 * Sign callback on authenticate.
 *
 * @param {PublicKeyCredential} publicKey @see https://www.w3.org/TR/webauthn/#publickeycredential
 * @param {function(PublicKeyCredential)} callback  User callback
 */
WebAuthn.prototype._signCallback = function (publicKey, callback) {
  const publicKeyCredential = {
    id: publicKey.id,
    type: publicKey.type,
    rawId: this._bufferEncode(publicKey.rawId),
    response: {
      /** @see https://www.w3.org/TR/webauthn/#iface-authenticatorassertionresponse */
      authenticatorData: this._bufferEncode(publicKey.response.authenticatorData),
      clientDataJSON: this._bufferEncode(publicKey.response.clientDataJSON),
      signature: this._bufferEncode(publicKey.response.signature),
      userHandle: publicKey.response.userHandle ? this._bufferEncode(publicKey.response.userHandle) : null,
    },
  };

  callback(publicKeyCredential);
};

/**
 * Buffer encode.
 *
 * @param {ArrayBuffer} value
 * @return {string}
 */
WebAuthn.prototype._bufferEncode = function (value) {
  return window.btoa(String.fromCharCode.apply(null, new Uint8Array(value)));
};

/**
 * Buffer decode.
 *
 * @param {ArrayBuffer} value
 * @return {string}
 */
WebAuthn.prototype._bufferDecode = function (value) {
  const t = window.atob(value);
  return Uint8Array.from(t, (c) => c.charCodeAt(0));
};

/**
 * Convert a base64url to a base64 string.
 *
 * @param {string} input
 * @return {string}
 */
WebAuthn.prototype._base64Decode = function (input) {
  // Replace non-url compatible chars with base64 standard chars
  input = input.replace(/-/g, '+').replace(/_/g, '/');

  // Pad out with standard base64 required padding characters
  const pad = input.length % 4;
  if (pad) {
    if (pad === 1) {
      throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
    }
    input += Array.from({ length: 5 - pad }).join('=');
  }

  return input;
};

/**
 * Credential decode.
 *
 * @param {PublicKeyCredentialDescriptor} credentials
 * @return {PublicKeyCredentialDescriptor}
 */
WebAuthn.prototype._credentialDecode = function (credentials) {
  const self = this;
  return credentials.map(function (data) {
    return {
      id: self._bufferDecode(self._base64Decode(data.id)),
      type: data.type,
      transports: data.transports,
    };
  });
};

/**
 * Test is WebAuthn is supported by this navigator.
 *
 * @return {bool}
 */
WebAuthn.prototype.webAuthnSupport = function () {
  return !(
    window.PublicKeyCredential === undefined ||
    typeof window.PublicKeyCredential !== 'function' ||
    typeof window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable !== 'function'
  );
};

/**
 * Get the message in case WebAuthn is not supported.
 *
 * @return {string}
 */
WebAuthn.prototype.notSupportedMessage = function () {
  if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    return 'not_secured';
  }
  return 'not_supported';
};

/**
 * Call the notify callback.
 *
 * @param {string} message
 * @param {bool} isError
 */
WebAuthn.prototype._notify = function (message, isError) {
  if (this._notifyCallback) {
    this._notifyCallback(message, isError);
  }
};

/**
 * Set the notify callback.
 *
 * @param {function(name: string, message: string, isError: bool)} callback
 */
WebAuthn.prototype.setNotify = function (callback) {
  this._notifyCallback = callback;
};

export default WebAuthn;
