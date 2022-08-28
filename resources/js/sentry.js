import * as Sentry from '@sentry/vue';
import { BrowserTracing } from '@sentry/tracing';
import { createTransport } from '@sentry/core';
import { Inertia } from '@inertiajs/inertia';

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
      dsn: options.dsn,
      tunnel: '/sentry/tunnel',
      environment: options.environment || null,
      release: options.release || '',
      sendDefaultPii: options.sendDefaultPii || false,
      tracesSampleRate: options.tracesSampleRate || 0.0,
      integrations: [options.tracesSampleRate > 0 ? new BrowserTracing() : null],
      transport: myTransport,
    });
    app.mixin(Sentry.createTracingMixins({ trackComponents: true }));
    activated = true;
  }
};

const setContext = (vm) => {
  if (activated && typeof vm.$page !== 'undefined') {
    if (vm.$page.props.user) {
      Sentry.setUser({ id: vm.$page.props.user.id });
    }
    Sentry.setTag('page.component', vm.$page.component);
    vm.$once(
      'hook:destroyed',
      Inertia.on('success', (event) => {
        Sentry.setTag('page.component', event.detail.page.component);
      }),
    );
  }
};

export const sentry = {
  install,
  setContext,
};
