import * as Sentry from '@sentry/browser';
import { Vue as VueIntegration } from '@sentry/integrations';
import { Integrations } from '@sentry/tracing';
import { Inertia } from '@inertiajs/inertia';

export default {
  _activated: false,

  init(app, release) {
    if (typeof SentryConfig !== 'undefined' && SentryConfig.dsn !== '') {
      Sentry.init({
        dsn: SentryConfig.dsn,
        environment: SentryConfig.environment || null,
        release: release || '',
        sendDefaultPii: SentryConfig.sendDefaultPii || false,
        tracesSampleRate: SentryConfig.tracesSampleRate || 0.0,
        integrations: [
          new VueIntegration({ Vue: app, attachProps: true }),
          SentryConfig.tracesSampleRate > 0 ? new Integrations.BrowserTracing() : null,
        ],
      });
      this._activated = true;
    }
  },

  setContext(vm, locale) {
    if (this._activated && typeof vm.$page !== 'undefined') {
      if (vm.$page.props.auth) {
        if (vm.$page.props.auth.user)
          Sentry.setUser({ id: vm.$page.props.auth.user.id });
        if (vm.$page.props.auth.company)
          Sentry.setTag('company.id', vm.$page.props.auth.company.id);
        if (vm.$page.props.auth.employee)
          Sentry.setTag('employee.id', vm.$page.props.auth.employee.id);
      }
      Sentry.setTag('locale', locale);
      Sentry.setTag('page.component', vm.$page.component);
      vm.$once(
        'hook:destroyed',
        Inertia.on('success', (event) => {
          Sentry.setTag('page.component', event.detail.page.component);
        })
      );
    }
  },
};
