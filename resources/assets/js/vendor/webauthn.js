/**
 * WebAuthn client.
 *
 * @copyright Alexis SAETTLER (c) 2019
 * @license MIT
 */

'use strict';

class WebAuthn {

  /**
   * Create a new instance of WebAuthn.
   *
   * @param {function(string, bool)} notifyCallback
   * @constructor
   */
  constructor(notifyCallback = null) {
    if (notifyCallback) {
      this.setNotify(notifyCallback);
    }
  };

  /**
   * Register a new key.
   *
   * @param {PublicKeyCredentialCreationOptions} publicKey  - see https://www.w3.org/TR/webauthn/#dictdef-publickeycredentialcreationoptions
   * @param {function(PublicKeyCredential)} callback  User callback
   */
  register(publicKey, callback) {
    var self = this;

    let publicKeyCredential = {
      rp: publicKey.rp,
      user: {
        id: this._bufferDecode(publicKey.user.id),
        displayName: publicKey.user.displayName,
      },
      challenge: this._bufferDecode(publicKey.challenge),
      pubKeyCredParams: publicKey.pubKeyCredParams,
      timeout: publicKey.timeout,
      excludeCredentials : this._credentialDecode(publicKey.excludeCredentials),
      authenticatorSelection: publicKey.authenticatorSelection,
      attestation: publicKey.attestation,
      extensions: publicKey.extensions,
    };

    navigator.credentials.create({
      publicKey: publicKeyCredential
    }).then((data) => {
      self._registerCallback(data, callback);
    }, (error) => {
      self._notify(error.name, error.message, false);
    });
  }

  /**
   * Register callback on register key.
   *
   * @param {PublicKeyCredential} publicKey @see https://www.w3.org/TR/webauthn/#publickeycredential
   * @param {function(PublicKeyCredential)} callback  User callback
   */
  _registerCallback(publicKey, callback) {
    let publicKeyCredential = {
      id: publicKey.id,
      type: publicKey.type,
      rawId: this._bufferEncode(publicKey.rawId),
      response: {
        /** @see https://www.w3.org/TR/webauthn/#authenticatorattestationresponse */
        clientDataJSON: this._bufferEncode(publicKey.response.clientDataJSON),
        attestationObject: this._bufferEncode(publicKey.response.attestationObject)
      }
    };

    callback(publicKeyCredential);
  }

  /**
   * Authenticate a user.
   *
   * @param {PublicKeyCredentialRequestOptions} publicKey  - see https://www.w3.org/TR/webauthn/#dictdef-publickeycredentialrequestoptions
   * @param {function(PublicKeyCredential)} callback  User callback
   */
  sign(publicKey, callback) {
    var self = this;

    let publicKeyCredential = {
      challenge: this._bufferDecode(publicKey.challenge),
      timeout : publicKey.timeout,
      rpId : publicKey.rpId,
      allowCredentials : this._credentialDecode(publicKey.allowCredentials),
      userVerification : publicKey.userVerification,
      extensions : publicKey.extensions,
    };

    navigator.credentials.get({
      publicKey: publicKeyCredential
    }).then((data) => {
      self._signCallback(data, callback);
    }, (error) => {
      self._notify(error.name, error.message, false);
    }
    );
  }

  /**
   * Signa callback on authenticate.
   *
   * @param {PublicKeyCredential} publicKey @see https://www.w3.org/TR/webauthn/#publickeycredential
   * @param {function(PublicKeyCredential)} callback  User callback
   */
  _signCallback(publicKey, callback) {
    let publicKeyCredential = {
      id: publicKey.id,
      type: publicKey.type,
      rawId: this._bufferEncode(publicKey.rawId),
      response: {
        /** @see https://www.w3.org/TR/webauthn/#iface-authenticatorassertionresponse */
        authenticatorData: this._bufferEncode(publicKey.response.authenticatorData),
        clientDataJSON: this._bufferEncode(publicKey.response.clientDataJSON),
        signature: this._bufferEncode(publicKey.response.signature),
        userHandle: (publicKey.response.userHandle ? this._bufferEncode(publicKey.response.userHandle) : null),
      }
    };

    callback(publicKeyCredential);
  }

  /**
   * Buffer encode.
   *
   * @param {ArrayBuffer} value
   * @return {string}
   */
  _bufferEncode(value) {
    return window.btoa(String.fromCharCode.apply(null, new Uint8Array(value)));
  }

  /**
   * Buffer decode.
   *
   * @param {ArrayBuffer} value
   * @return {string}
   */
  _bufferDecode(value) {
    return Uint8Array.from(window.atob(value), c => c.charCodeAt(0));
  }

  /**
   * Credential decode.
   *
   * @param {PublicKeyCredentialDescriptor} credentials
   * @return {PublicKeyCredentialDescriptor}
   */
  _credentialDecode(credentials) {
    var self = this;
    return credentials.map(function(data) {
      return {
        id: self._bufferDecode(data.id),
        type: data.type,
        transports: data.transports, 
      };
    });
  }

  /**
   * Test is WebAuthn is supported by this navigator.
   *
   * @return {bool}
   */
  webAuthnSupport() {
    return ! (window.PublicKeyCredential === undefined ||
      typeof window.PublicKeyCredential !== 'function' ||
      typeof window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable !== 'function');
  }

  /**
   * Get the message in case WebAuthn is not supported.
   *
   * @return {string}
   */
  notSupportedMessage() {
    if (! window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
      return 'webauthn_not_secured';
    }
    return 'webauthn_not_supported';
  }

  /**
   * Call the notify callback.
   *
   * @param {string} message
   * @param {bool} isError
   */
  _notify(message, isError) {
    if (this._notifyCallback) {
      this._notifyCallback(message, isError);
    }
  }

  /**
   * Set the notify callback.
   *
   * @param {function(name: string, message: string, isError: bool)} callback
   */
  setNotify(callback) {
    this._notifyCallback = callback;
  }
}

module.exports = WebAuthn;
