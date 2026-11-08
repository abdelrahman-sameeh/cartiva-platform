# دليل مذاكرة Stripe لمشروع Cartiva

الدليل ده مرتب علشان يساعدك تفهم Stripe المستخدم في المشروع، وتعرف تشرح رحلة الدفع في الانترفيو من غير ما تحفظ الـ SDK أو أسماء الـ methods.

ابدأ دايمًا بفهم **رحلة الفلوس**:

```text
Customer
   ↓
Checkout Session
   ↓
PaymentIntent
   ↓
Charge
   ↓
BalanceTransaction
   ↓
Platform Stripe Balance
   ↓
Transfer
   ↓
Seller Connected Account
   ↓
Payout
   ↓
Seller Bank Account
```

## Phase 1 — أساسيات الدفع

ذاكر الفرق بين:

- `Checkout Session`: صفحة الدفع اللي Stripe بيستضيفها.
- `PaymentIntent`: محاولة الدفع وحالتها.
- `Charge`: عملية الخصم الفعلية من العميل.
- `BalanceTransaction`: الحركة المالية الفعلية، ورسوم Stripe، وعملة التسوية.
- `Refund`: إرجاع فلوس للعميل.

أهم علاقة تحفظها:

```text
Checkout Session → PaymentIntent → Charge → BalanceTransaction
```

الـ success URL مش دليل كافي إن الدفع نجح. الـ webhook هو مصدر الحقيقة، وStripe بيوصي بإتمام معالجة الطلب من أحداث زي `checkout.session.completed`.

راجع:

- `app/Services/Payments/OrderCheckoutService.php`
- `app/Services/Payments/Gateways/StripeCheckoutGateway.php`
- `app/Http/Controllers/Webhooks/StripeCheckoutWebhookController.php`
- [Stripe Checkout documentation](https://docs.stripe.com/payments/existing-customers?platform=web&ui=stripe-hosted)

## Phase 2 — Stripe Connect

ذاكر:

- الفرق بين Platform Account وConnected Account.
- Stripe-hosted onboarding.
- Account Links.
- متطلبات البائع `requirements`.
- إمتى الحساب يكون Ready أو Action Required أو Restricted.
- الفرق بين Stripe Express Dashboard وPlatform Dashboard.
- Accounts v2 وموضوع configurations و`stripe_transfers`.

حساب البائع مش مجرد `stripe_account_id`. النظام لازم يعرف هل البائع أكمل بياناته، وهل حسابه قادر يستقبل transfers ويعمل payouts.

راجع:

- `app/Services/Sellers/SellerOnboardingService.php`
- `app/Services/Payments/Gateways/StripeConnectGateway.php`
- `app/Http/Controllers/Webhooks/StripeConnectWebhookController.php`
- [Accounts v2 connected account configuration](https://docs.stripe.com/connect/accounts-v2/connected-account-configuration)

## Phase 3 — حركة الفلوس في Marketplace

Stripe Connect عنده 3 نماذج أساسية:

- Direct Charges.
- Destination Charges.
- Separate Charges and Transfers.

Cartiva بيستخدم `Separate Charges and Transfers` لأنه مناسب للـ multi-vendor order:

1. العميل يدفع للمنصة.
2. الفلوس تدخل Balance المنصة.
3. النظام يحسب مستحق كل seller.
4. النظام يعمل Transfer منفصل لكل seller.

ذاكر كويس:

- `source_transaction`.
- `transfer_group`.
- Platform balance.
- Seller net amount.
- Cartiva commission.
- Stripe processing fee.
- توزيع الرسوم في order فيه أكتر من seller.

راجع:

- `app/Services/Payments/SellerTransferService.php`
- `app/Services/Payments/Gateways/StripeTransferGateway.php`
- [Separate charges and transfers](https://docs.stripe.com/connect/separate-charges-and-transfers)

## Phase 4 — الفرق بين Transfer وPayout

دي من أهم النقاط في الانترفيو:

- `Transfer`: تحويل من حساب المنصة إلى Connected Account الخاص بالبائع.
- `Payout`: تحويل من Stripe Balance الخاص بالبائع إلى حسابه البنكي.

نجاح الـ Transfer لا يعني إن الفلوس وصلت البنك. معناه إنها وصلت Stripe balance بتاع البائع، وبعد كده Stripe يعمل Payout حسب إعدادات وجدول الحساب.

راجع [Stripe Payout API](https://docs.stripe.com/api/payouts/create).

## Phase 5 — العملات والرسوم

ذاكر:

- Minor units: تخزين المبلغ كرقم صحيح، زي القروش أو السنتات.
- Presentment currency: العملة اللي العميل دفع بيها.
- Settlement currency: العملة اللي Stripe سوّى بيها العملية.
- Currency conversion.
- الفرق بين estimated fees وactual Stripe fees.

في Cartiva ممكن العميل يدفع بـ EGP، لكن Stripe يسجل التسوية الفعلية بعملة مختلفة. علشان كده بناخد الرسوم الحقيقية من `BalanceTransaction` بدل ما نفترض نسبة ثابتة.

Stripe تقنيًا بيخصم رسومه من المنصة، لكن الـ business logic عندنا بيخصم قيمة الرسوم دي من صافي مستحق البائع بالإضافة لعمولة Cartiva.

راجع [Stripe currencies](https://docs.stripe.com/currencies).

## Phase 6 — Webhooks والأمان

ذاكر:

- التحقق من Webhook Signature.
- استخدام raw request body في التحقق.
- عدم الثقة في بيانات الـ frontend.
- عدم الثقة في السعر القادم من المستخدم.
- Idempotency keys.
- Duplicate webhook events.
- Out-of-order events.
- تسجيل Stripe event ID لمنع المعالجة مرتين.
- عدم وضع الـ secret keys في Git.
- الفرق بين test keys وlive keys.

لازم تفهم السيناريو ده:

```text
Stripe يرسل webhook
→ السيرفر يعالجه بنجاح
→ الرد يضيع
→ Stripe يعيد نفس الـ webhook
```

النظام لازم ما يعملش order paid أو seller transfer مرتين.

راجع:

- [Stripe webhooks](https://docs.stripe.com/webhooks)
- [Idempotent requests](https://docs.stripe.com/api/idempotent_requests)

## Phase 7 — Retry وEventual Consistency

أحيانًا `checkout.session.completed` يوصل، لكن `BalanceTransaction` والرسوم النهائية لسه مش جاهزين.

علشان كده النظام:

1. يسجل seller transfer كـ pending.
2. ينتظر الـ scheduler.
3. يحاول تاني.
4. يعمل Transfer لما بيانات التسوية تبقى جاهزة.

ذاكر:

- Queues وscheduled jobs.
- Retry policies.
- الفرق بين temporary failure وpermanent failure.
- تخزين حالة المحاولة والخطأ.
- ليه مينفعش نحول مبلغ تقريبي قبل ظهور التسوية الحقيقية.

## Phase 8 — Refunds وDisputes

دي من أهم الأجزاء المطلوبة قبل مستوى production الكامل:

- لو رجعت فلوس العميل، الـ Transfer السابق للبائع مش بيتعكس تلقائيًا في كل الحالات.
- محتاج تعمل `Transfer Reversal` عند الحاجة.
- لازم تدعم partial refunds.
- لازم توزع قيمة الاسترجاع والرسوم على البائعين.
- لازم تتعامل مع disputes وnegative balances.

راجع [Stripe Transfer Reversals](https://docs.stripe.com/api/transfer_reversals).

## خطة مذاكرة في 7 أيام

### اليوم 1

ارسم رحلة الفلوس، وافهم الفرق بين Stripe objects الأساسية.

### اليوم 2

ذاكر Checkout وPaymentIntent والـ payment webhooks.

### اليوم 3

ذاكر Connect onboarding وAccounts v2 وحالات حساب البائع.

### اليوم 4

ذاكر Separate Charges and Transfers وطريقة حساب العمولات.

### اليوم 5

ذاكر العملات والرسوم وBalanceTransaction.

### اليوم 6

ذاكر Idempotency وduplicate events والـ retry.

### اليوم 7

تتبع order حقيقي من الكود والداتابيز، واعرضه لنفسك كأنك في الانترفيو.

## أسئلة لازم تعرف تجاوب عليها

- ليه استخدمنا Stripe Checkout؟
- ليه الـ webhook هو مصدر الحقيقة وليس success URL؟
- إيه الفرق بين Charge وTransfer وPayout؟
- ليه اخترنا Separate Charges and Transfers؟
- إزاي بنمنع الدفع أو التحويل مرتين؟
- لو Stripe كرر نفس الـ webhook هيحصل إيه؟
- رسوم Stripe وعمولة Cartiva بيتحسبوا إزاي؟
- ليه التحويل ممكن يفضل pending؟
- لو حصل refund بعد تحويل فلوس البائع هنعمل إيه؟
- إيه الفرق بين الدفع الإلكتروني وCash on Delivery؟

## أهم تمرين عملي

تتبع order واحد من لحظة إنشاء Checkout Session لحد وصول الـ Transfer للبائع:

1. شوف الـ Checkout Session اتعملت فين.
2. تابع الـ webhook اللي أكد نجاح الدفع.
3. راجع تحديث حالة الـ order والـ payment في الداتابيز.
4. راجع حساب رسوم Stripe وعمولة Cartiva.
5. تابع إنشاء seller transfer.
6. راجع حالة الـ Transfer في Stripe Dashboard.
7. وضح إن الـ Payout للبنك مرحلة منفصلة عن الـ Transfer.

لو قدرت تشرح الرحلة دي من الكود والداتابيز، هتكون فاهم الجزء الأكبر المطلوب لشرح Stripe في المشروع والانترفيو.
