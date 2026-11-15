# الدفع والتسويات في Cartiva

هذا الملف هو المرجع الموحد للدفع الإلكتروني عبر Stripe والدفع عند الاستلام (COD). يصف التنفيذ الحالي في المشروع، وليس تصورًا مستقبليًا.

> التكامل الحالي مخصص لـ Stripe Test/Sandbox. لا تستخدم مفاتيح أو بيانات بنكية حقيقية في بيئة التطوير.

## 1. لماذا الدفع معقد في Marketplace؟

العميل ينشئ `Order` واحدًا، لكن الطلب قد يحتوي على منتجات من بائعين مختلفين. لذلك يقسمه النظام إلى `SubOrder` مستقلة لكل بائع:

```text
Order العميل
├── SubOrder للبائع A ── مستحقات A
├── SubOrder للبائع B ── مستحقات B
└── SubOrder للبائع C ── مستحقات C
```

الفصل ضروري لأن كل بائع قد يجهز أو يشحن أو يلغي في وقت مختلف، ولا يصح دفع مستحقات بائع بسبب اكتمال طلب بائع آخر.

## 2. نموذج Stripe المستخدم

يستخدم المشروع Stripe Connect بنموذج **Separate Charges and Transfers**:

1. العميل يدفع إجمالي `Order` مرة واحدة من خلال Stripe-hosted Checkout.
2. الـ Charge والـ `PaymentIntent` ينشآن على حساب المنصة.
3. يرتبط الدفع بـ `transfer_group` خاص بالطلب، مثل `ORDER_123`.
4. يؤكد Webhook نجاح الدفع؛ صفحة الرجوع وحدها ليست إثباتًا للدفع.
5. ينشئ النظام `SellerTransfer` لكل `SubOrder`.
6. لا يرسل مستحق البائع قبل اكتمال طلبه الفرعي.
7. بعد اكتمال `SubOrder`، يحول النظام صافي المستحق إلى Stripe Connected Account الخاص بالبائع.
8. Stripe تدير الـ Payout من رصيد الحساب المتصل إلى بنك البائع طبقًا لإعداداته.

```text
Customer
   │ Checkout واحد
   ▼
Platform Stripe balance
   ├── Transfer A ──► Seller A connected account
   ├── Transfer B ──► Seller B connected account
   └── Transfer C ──► Seller C connected account
```

الـ **Transfer** من المنصة لحساب البائع داخل Stripe، أما الـ **Payout** فمن حساب البائع في Stripe إلى البنك. التطبيق ينفذ الأول، وStripe تدير الثاني.

### لماذا هذا النموذج؟

- يدعم طلبًا واحدًا يضم عدة بائعين.
- يسمح بتأخير مستحق كل بائع حتى اكتمال طلبه الفرعي.
- يسمح باسترداد جزء بائع واحد دون إلغاء الطلب كله.
- يعطي المنصة سجلًا واضحًا لكل حصة ورسوم وتحويل.

## 3. ربط البائع بـ Stripe Connect

لكل بائع سجل واحد في `seller_stripe_accounts` وحساب Stripe واحد:

- إدارة الحساب تستخدم Stripe Accounts v2.
- الحساب يطلب `configuration.recipient` ليستقبل Transfers.
- لوحة البائع من نوع Express.
- التسجيل يتم من خلال Stripe-hosted onboarding باستخدام Account Link مؤقت.
- الرجوع من Stripe لا يعني أن الحساب أصبح جاهزًا؛ التطبيق يزامن requirements وtransfer capability.
- تكرار طلب onboarding لا ينشئ حسابًا جديدًا للبائع نفسه.

الحالات المحلية توضح هل الحساب `pending` أو يحتاج إجراء أو جاهزًا. أهلية استقبال Transfers أهم من `charges_enabled` لأن البائع لا يستقبل Direct Charges من العميل.

## 4. إنشاء Checkout وتأكيد الدفع

عند طلب الدفع الإلكتروني:

1. يتحقق السيرفر أن الطلب يخص العميل وأن قيمته وحالته تسمحان بالدفع.
2. ينشئ سجل `payments` بمبلغ الطلب بوحدة العملة الصغرى.
3. ينشئ Checkout Session باستخدام Stripe SDK الرسمي.
4. يحفظ `checkout_session_id` و`provider_payment_id` عند توفرهما.
5. ينتقل العميل إلى صفحة Stripe.
6. عند العودة، تعرض المنصة حالة العملية فقط؛ لا تعتبرها ناجحة من الـ URL.
7. Webhook موقع من Stripe يثبت النجاح ويحدث `Payment` و`Order.payment_status`.

مثال: `125.50 EGP` تحفظ `12550` في `amount_minor`. استخدام integer يمنع أخطاء `float` في الحسابات المالية.

## 5. Webhooks وIdempotency

يوجد endpoint منفصل لأحداث Checkout وآخر لأحداث Connect:

```text
POST /api/webhooks/stripe/checkout
POST /api/webhooks/stripe/connect
```

قواعد الأمان:

- التحقق من Stripe signature باستخدام webhook secret المناسب.
- حفظ Stripe event ID في `stripe_webhook_events` كقيمة Unique.
- تجاهل المعالجة المكررة للحدث نفسه بأمان.
- استخدام `idempotency_key` في الدفع والتحويل والاسترداد.
- عدم الثقة في `seller_id` أو الإجمالي أو حالة الدفع القادمة من المتصفح.

## 6. حساب مستحق البائع في Stripe

بعد أن تصبح بيانات التسوية الفعلية متاحة، يحسب صافي التحويل:

```text
نصيب البائع من مبلغ التسوية
- نصيبه النسبي من رسوم Stripe الفعلية
- عمولة المنصة
= صافي Stripe Transfer
```

يحفظ `seller_transfers` القيم التالية منفصلة للحفاظ على أثر محاسبي واضح:

- حصة البائع الأصلية وعملتها.
- حصة البائع بعملة تسوية Stripe.
- نصيبه من رسوم Stripe.
- عمولة المنصة والـ basis points المستخدمة.
- صافي مبلغ التحويل.
- Stripe transfer ID والحالة وسبب الفشل.

إذا قبضت Stripe بالجنيه وسوت رصيد المنصة بعملة أخرى، يستخدم التحويل عملة التسوية الفعلية بدل افتراض أن العملتين متطابقتان.

الحالات المتوقعة للتحويل تشمل `pending`, `processing`, `succeeded`, `failed`, `blocked`, و`skipped`. يوجد Unique على `sub_order_id` ومفتاح idempotency لمنع تحويل حصة البائع مرتين.

إعادة معالجة التحويلات المعلقة أو الفاشلة:

```bash
php artisan stripe:retry-seller-transfers
php artisan stripe:retry-seller-transfers --order=123
```

يشغل Laravel Scheduler الأمر كل دقيقة مع `withoutOverlapping`.

## 7. الإلغاء والاسترداد الإلكتروني

لأن التحويل مؤجل حتى اكتمال `SubOrder`:

- يمكن إلغاء طلب فرعي قبل التسليم دون سحب المال من البائع.
- يعيد النظام مخزون عناصر الطلب الفرعي ويصحح `sold_count`.
- يحرر استخدام الكوبون الخاص بهذا الجزء.
- يعيد حساب إجماليات الطلب من الأجزاء غير الملغاة.
- إذا كان الدفع ناجحًا، ينشئ `Refund` جزئيًا بقيمة `SubOrder` الملغاة.

كل استرداد مرتبط بطلب فرعي واحد، وله `idempotency_key` وحالة وStripe refund ID. إذا تم تحويل مستحق البائع بالفعل، لا ينفذ النظام الإلغاء التلقائي لأن الحالة تحتاج Transfer Reversal ومراجعة تشغيلية.

معالجة الاستردادات المعلقة أو الفاشلة:

```bash
php artisan stripe:process-refunds
```

يشغله Scheduler كل دقيقة أيضًا.

## 8. حالات الطلب والدفع عند الاستلام

يدير البائع أو المدير حالة كل `SubOrder` من الواجهة بالتسلسل التالي:

```text
pending → processing → shipped → completed
    └──────────┴────────► cancelled
```

- الإلغاء متاح قبل الشحن فقط، ويعيد المخزون والكوبون ويعيد حساب إجماليات الطلب.
- تظهر كل الحالات في الواجهة، ويمكن القفز إلى أي حالة لاحقة دون الرجوع إلى حالة أقدم.
- يصبح طلب COD `paid` بعد اكتمال كل أجزائه غير الملغاة.
- لا توجد حاليًا شركة شحن أو تتبع أو تسوية COD منفصلة داخل التطبيق.

## 9. الإعدادات المطلوبة

```dotenv
STRIPE_SECRET=sk_test_...
STRIPE_CURRENCY=egp
STRIPE_CHECKOUT_WEBHOOK_SECRET=whsec_...
STRIPE_CONNECT_COUNTRY=...
STRIPE_CONNECT_WEBHOOK_SECRET=whsec_...

PLATFORM_COMMISSION_BPS=500
```

`500` basis points تعني 5%. توجد إعدادات تقديرية إضافية لعرض حاسبة رسوم البائع، لكن التسوية الفعلية تعتمد على بيانات Stripe الحقيقية.

لتشغيل بيئة Stripe المحلية المجهزة بالمشروع:

```bash
composer dev:stripe
```

## 10. مسؤوليات التشغيل قبل الإنتاج

- تحديد آلية تشغيلية لتحصيل وتسوية COD قبل الإنتاج.
- تشغيل Queue workers وLaravel Scheduler تحت process manager.
- تسجيل وتنبيه حالات transfer/refund الفاشلة أو المعلقة.
- وضع سياسة واضحة للمرتجعات بعد تحويل مستحق البائع وStripe disputes.
- استخدام Stripe live keys وwebhook secrets من secret manager، وليس من المستودع.
- مراجعة الدول والعملات وConnect capabilities المتاحة لحساب المنصة قبل الإطلاق.
