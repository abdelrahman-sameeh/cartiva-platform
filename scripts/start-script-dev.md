```bash

npm run dev

# إعادة محاولة تحويلات البائع المعلقة كل دقيقة
php artisan schedule:work

# Stripe Connect Webhook
stripe listen \
  --all-thin \
  --events-from @accounts \
  --forward-to http://127.0.0.1:8000/api/webhooks/stripe/connect


# Stripe Checkout Webhook
stripe listen \
  --events checkout.session.completed,checkout.session.async_payment_succeeded,checkout.session.async_payment_failed,checkout.session.expired \
  --forward-to http://127.0.0.1:8000/api/webhooks/stripe/checkout



php artisan config:clear



php artisan serve --host=127.0.0.1 --port=8000

# أو شغّل كل ما سبق من Terminal واحدة
composer dev:stripe

```
