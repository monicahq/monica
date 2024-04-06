import * as Sentry from '@sentry/vue';
import { createTransport } from '@sentry/core';
import { router } from '@inertiajs/vue3';
import emitter from 'tiny-emitter/instance';
import axios from 'axios';

let activated = false;

const myTransport = (options) => {
  const makeRequest = async (request) => {
    const requestOptions = {
      data: request.body,
      url: options.url,
      method: 'POST',
      referrerPolicy: 'origin',
      headers: options.headers,
      ...options.fetchOptions,
    };
    return axios(requestOptions).then((response) => ({
      statusCode: response.status,
      headers: response.headers,
    }));
  };
  return createTransport({ bufferSize: options.bufferSize }, makeRequest);
};

const install = (app, options) => {
  if (options.dsn !== undefined) {
    Sentry.init({
      app,
      dsn: options.dsn,
      tunnel: options.tunnel,
      environment: options.environment || null,
      release: options.release || '',
      sendDefaultPii: options.sendDefaultPii || false,
      tracesSampleRate: options.tracesSampleRate || 0.0,
      integrations: options.tracesSampleRate > 0 ? [Sentry.browserTracingIntegration()] : [],
      transport: myTransport,
      ignoreTransactions: [options.tunnel],
    });
    app.mixin(Sentry.createTracingMixins({ trackComponents: true }));
    activated = true;
  }
};

const setContext = (vm) => {
  const setCtx = (page) => {
    if (page.props.auth.user) {
      Sentry.setUser({ id: page.props.auth.user.id });
    }
    Sentry.setTag('page.component', page.component);
  };

  if (activated && typeof vm.$page !== 'undefined') {
    setCtx(vm.$page);
    emitter.once(
      'hook:destroyed',
      router.on('success', (event) => {
        setCtx(event.detail.page);
      }),
    );
  }
};

export const sentry = {
  install,
  setContext,
};
