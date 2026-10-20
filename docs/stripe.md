# Stripe — خطة التنفيذ المعتمدة للـ Multi-Vendor E-Commerce

> **الحالة:** قرار هندسي معتمد — 20 أكتوبر 2026  
> **الغرض:** ملف عمل واضح لـ Codex، ونشتغل منه مرحلة بمرحلة.  
> **النطاق الحالي:** تطبيق Laravel للتجربة والتعلّم باستخدام Stripe Sandbox/Test Mode فقط؛ لا معاملات مالية حقيقية.

## 1) الصورة الكبيرة

التطبيق Marketplace فيه عميل بيشتري منتجات من بائع واحد أو أكثر في **طلب واحد**. الطلب بيتقسم داخليًا لأجزاء تخص كل بائع (`sub-orders` إن كانت موجودة في المشروع). التدفق المالي المطلوب:

```text
                    CUSTOMER
                       |
                  يدفع الطلب
                       v
              STRIPE CHECKOUT
                       |
                       v
          PLATFORM STRIPE ACCOUNT
             (الفلوس تتحصل هنا)
                       |
           بعد نجاح الدفع والتحقق
                  /          \
                 v            v
           Transfer A      Transfer B
                 |            |
             SELLER A      SELLER B
           Stripe Express connected accounts
                 |            |
            Bank payouts يديرها Stripe حسب إعدادات الحساب

  وبشكل منفصل تمامًا:
  SELLER -> Subscription (Monthly / Yearly) -> PLATFORM
                 باستخدام Stripe Billing
```

**مهم:** العميل ما بيدفعش مباشرة لحساب البائع. المنصة هي اللي بتحصّل المدفوعات، وبعدها تعمل تحويل داخلي على Stripe إلى حساب كل بائع. `Transfer` بين حسابات Stripe غير `Payout` من Stripe للبنك. واشتراك البائع في المنصة غير الاتنين دول.

## 2) القرارات التقنية النهائية (Decision Record)

| الموضوع | القرار المعتمد | السبب |
|---|---|---|
| إنشاء وإدارة حسابات البائعين | **Stripe Accounts v2** | الاختيار اللي Stripe بتوصي بيه للتكاملات الجديدة؛ حساب واحد ممكن يبقى مستقبل تحويلات ومشتري اشتراك |
| صلاحية استقبال الأرباح | `configuration.recipient` مع طلب `stripe_balance.stripe_transfers` | البائع يستقبل تحويلات من المنصة من غير ما نطلب منه قبول دفعات العملاء مباشرة |
| تحصيل الاشتراك من البائع | نضيف `configuration.customer` **لنفس Account v2** | مفيش `Customer` تاني منفصل للبائع |
| لوحة تحكم البائع | `dashboard: express` | واجهة أخف من Dashboard الكامل، وStripe تدير أجزاء التحقق |
| مسؤوليات الحساب المتصل | للـ Express نستخدم `fees_collector: application` و`losses_collector: application` عند الحاجة لتهيئة المسؤوليات، وفق قيود Stripe | لا نختار Stripe-liable مع Express بالمخالفة للقيود؛ المنصة تتحمل مسؤولية المدفوعات المنشأة عليها |
| تسجيل البائع | **Stripe-hosted onboarding** باستخدام Account Link | أقل تعقيد؛ Stripe تعرض المتطلبات المناسبة للبلد والإعدادات |
| دفع العميل | **Stripe-hosted Checkout**، `mode=payment` | صفحة دفع جاهزة وآمنة وأقل كود |
| طريقة توزيع الأموال | **Separate Charges and Transfers** | طلب واحد ممكن يحتوي منتجات من بائعين متعددين |
| اشتراكات البائع | **Stripe Billing** + سعر شهري وسنوي لنفس المنتج | تجديدات تلقائية وفواتير وحالات واضحة |
| الاتصال بـ Stripe من Laravel | حزمة **`stripe/stripe-php`** الرسمية | دعم العمليات المطلوبة بدون طبقات إضافية |
| Laravel Cashier | **مش هنستخدمه دلوقتي** | التعامل المباشر مع Stripe SDK أنسب لتجميع Connect v2 والتحويلات والاشتراكات، وتجنّب افتراض وجود `cus_...` منفصل لكل بائع |
| البيانات | MySQL + Eloquent + Laravel Queues عند الحاجة | نستغل الموجود بدل تركيب بنية جديدة |
| تحديث الحالات | **Webhooks** بعد التحقق من التوقيع + Idempotency | الـ Redirect مش إثبات دفع أو إتمام Onboarding |
| مزود دفع لاحق | Paymob **ممكن بعدين، غير منفذ الآن** | نفصل كود تحصيل الدفع عن Stripe قدر الحاجة بدون بناء Framework وهمي |

### توضيح مهم عن v1 وv2

القرار يخص **Accounts v2 لإدارة الحسابات**، مش معناه منع endpoints التي تبدأ بـ `/v1`. Stripe لسه بتستخدم واجهات `/v1` لعمليات زي Checkout وTransfers وSubscriptions وAccount Links حسب العملية. **ممنوع** الرجوع إلى إنشاء حساب البائع عبر Legacy `type=express` أو الاعتماد على Accounts v1 لإنشاء البائعين الجدد، لمجرد إن بعض الأمثلة القديمة بتعمل كده.

> `dashboard=express` في Accounts v2 **مش** هو Legacy `type=express` في Accounts v1.

> **قيد معروف:** لو Stripe رفض إعداد Recipient أو طلب `merchant` capability إضافية تبعًا للبلد/التفعيل، ما نطلبش `merchant` أو `card_payments` عشوائيًا. نسجل الخطأ، نراجع وثائق Stripe الخاصة بنفس التكوين والبلد، ونعرض العائق قبل تعديل القرار. بلاش نقلّد Payload قديم أو نبدّل إلى Accounts v1 من نفسنا. كذلك بعض الميزات مثل `recipient service agreement` الخاص تحتاج Accounts v1؛ **مش داخل نطاقنا**.

## 3) حدود المشروع وقواعد الشغل

1. المشروع **Test/Sandbox فقط**: مفاتيح `sk_test_...` و`pk_test_...`، وممنوع استخدام مفاتيح Live أو أي بيانات بنكية/هوية حقيقية لغرض التجربة.
2. البائع عبارة عن `User` موجود في التطبيق بصلاحية Seller. **ما تعملش Seller model/table جديدة** من غير سبب مثبت من الـ Repository.
3. **افحص المشروع قبل ما تغيّر:** نسخة Laravel، طريقة إدارة Order/Sub-orders، ملفات الدفع الموجودة، الـ migrations والـ models. ما تفترضش اسم جدول أو هيكل مش موجود.
4. طبّق **KISS، SOLID بشكل عملي، DRY بحكمة**. Controllers رفيعة، والمنطق المالي في Services مفهومة، والـ SDK معزول داخل Integration واضحة.
5. ما تعملش Interfaces أو Repositories أو Factory Patterns لمجرد احتمال إضافة Paymob. نفصل الحدود الطبيعية بس، ونستخرج Contract صغير لاحقًا عند احتياج فعلي.
6. كل عملية حساسة تتحقق من Auth وRole وOwnership على السيرفر. ما نثقش بـ `seller_id` أو أسعار أو حالة دفع جاية من Frontend.
7. كل المبالغ داخل التطبيق والـ Stripe API تتعامل بـ **integer minor units** مع مراعاة قواعد كل عملة، من غير `float` لحساب الفلوس.
8. البيانات اللي تتحسب (الإجمالي، نصيب البائع، الخصم، الشحن، الضريبة) تتحسب على السيرفر وتثبت وقت إنشاء الطلب. ممنوع الاعتماد على السعر المرسل من المستخدم.
9. Webhook أو Stripe API ممكن يعيد الحدث/الطلب. نحمي المعاملات من التكرار بمفتاح Idempotency وDB unique constraints وTransactions عند الحاجة.
10. اعرض الخطة والتغييرات المقترحة قبل تعديل المشروع، واشتغل **Phase واحدة فقط** في كل مرة. ما تعملش Commit أو Push من غير طلب صريح.

## 4) خطة التنفيذ — Phases

### Phase 0 — التجهيز وفحص الموجود

**الهدف:** نتأكد إن البيئة والـ Repository جاهزين من غير تغيير غير ضروري.

**Tasks**
- [ ] قراءة `AGENTS.md` والـ Models/Routes/Migrations الخاصة بالمستخدمين والطلبات والمدفوعات.
- [ ] مراجعة أي Stripe integration موجود بالفعل وتحديد اللي يُعاد استخدامه واللي محتاج استبدال. **ممنوع إنشاء حساب متصل جديد لو فيه حساب متصل بالفعل** قبل تقييم ترحيله/الإبقاء عليه في Sandbox.
- [ ] إعداد Stripe **Sandbox/Test Mode** والتأكد إن Connect متاح ومُفعّل فيه.
- [ ] تثبيت `stripe/stripe-php` بإصدار مناسب للـ PHP الحالي ويدعم APIs المطلوبة، مع تثبيت Version في `composer.lock`.
- [ ] ضبط الأسرار في `.env` وربطها بـ `config/services.php` بدل `env()` مباشرة داخل Services.
- [ ] تجهيز Stripe CLI لاستقبال Webhooks محليًا، بمفاتيح webhook منفصلة حسب الـ endpoint / الحدث عند الحاجة.
- [ ] تثبيت إصدار Stripe API مدعوم بالفعل من الحساب وSDK؛ **لا تنسخ إصدار `.preview` أو `.endive` من الوثائق بشكل أعمى**.

**مخرجات المرحلة:** تكوين البيئة، وجرد واضح للموجود، وطلب تجريبي مصادَق عليه من Stripe، واختبارات اتصال أساسية بدون تعديل Order business logic.

**شرط القبول:** مفاتيح Test فقط؛ Config سليم؛ مفيش بيانات حساسة متسربة؛ ولا تغييرات معمارية غير لازمة.

---

### Phase 1 — Seller Onboarding (نبدأ بيها)

**الهدف:** البائع يربط حسابه بStripe ويبقى مؤهلًا لاستقبال Transfers من المنصة.

**Tasks**
- [ ] عند ضغط البائع «ربط حساب Stripe»: نتحقق من ملكيته وصلاحياته.
- [ ] نعمل **Account v2 واحد فقط** للبائع عند عدم وجوده، بإعدادات `dashboard=express` و`configuration.recipient` مع طلب `stripe_balance.stripe_transfers` وفق قدرة الحساب/البلد.
- [ ] إضافة `configuration.customer` لنفس الحساب من البداية أو قبل Phase 4، **مش إنشاء Account تاني**.
- [ ] حفظ `stripe_account_id` في جدول مرتبط بالبائع بـ unique index.
- [ ] إنشاء Stripe-hosted **Account Link** للـ onboarding، وإرجاع URL مؤقت للبائع المسجل دخول.
- [ ] التعامل مع `refresh_url` بإنشاء Account Link جديد، و`return_url` بمزامنة حالة الحساب من Stripe.
- [ ] متابعة `requirements` وحالة `configuration.recipient.capabilities.stripe_balance.stripe_transfers` من Stripe، ومزامنة Webhook مناسب من أحداث Accounts v2.
- [ ] عرض حالة واضحة: `not_connected`، `pending`، `action_required`، `ready`، `restricted` (حالات محلية معبرة مش ادّعاء إنها نفس قيم Stripe).
- [ ] دعم إعادة فتح onboarding لو فيه Requirements ناقصة، بدون إنشاء حساب جديد كل مرة.
- [ ] اختيارياً: رابط Express Dashboard من Stripe بعد اكتمال الربط.

**البيانات المقترحة** — عدّلها حسب الموجود بدل تكرار الأعمدة:

```text
seller_stripe_accounts
  id
  user_id                  UNIQUE, FK -> users
  stripe_account_id        UNIQUE
  onboarding_status        LOCAL ENUM/STRING
  transfers_status         STRING/NULL  (حالة capability الحقيقية)
  requirements_due         JSON/NULL
  disabled_reason          STRING/NULL (إن كان متاحًا)
  last_synced_at           TIMESTAMP/NULL
  created_at, updated_at
```

**ملاحظات:**
- ما نخليش شرط النجاح `charges_enabled` لأن البائع **مش بيقبل Direct Charges**. الأهم eligibility لاستقبال Transfers وربط الـ payout بحسب صلاحيات البلد وحالة Stripe.
- `payouts_enabled` معناه إمكانية صرف الرصيد خارجيًا، وده مختلف عن استقبال Transfer؛ يعرض للمستخدم كحالة منفصلة لو متاح.
- الرد بعد العودة من Stripe **مش** دليل إن التحقق اكتمل.
- أقل onboarding steps ممكنة عن طريق طلب Capabilities الضرورية فقط؛ لكن مش هنلغي KYC اللي Stripe بتفرضه.

**اختبارات القبول:**
- [ ] بائع جديد: يتعمل له Account واحد.
- [ ] ضغط الزر 3 مرات: يفضل Account واحد.
- [ ] User عادي ما يقدرش يعمل Onboarding كبائع.
- [ ] بائع ما يقدرش يشوف أو يعيد تهيئة حساب بائع آخر.
- [ ] لينك منتهي/مستخدم قبل كده: إعادة توليد صحيحة.
- [ ] Return قبل استكمال البيانات: الحالة لا تصبح `ready`.
- [ ] بعد اكتمال requirements وتفعيل capability المطلوبة: الحالة تتحدث.
- [ ] فشل Stripe API: يظهر خطأ مفهوم بدون حسابات محلية مكررة.
- [ ] إعادة نفس Webhook: لا تكسر الحالة.

**شرط انتهاء Phase 1:** نقدر نختبر Seller onboarding كامل ببيانات Test ونتأكد إن الحساب مناسب لاستقبال Transfers؛ **من غير** تنفيذ الدفع أو الاشتراكات.

---

### Phase 2 — Customer Order Checkout

**الهدف:** العميل يدفع ثمن **Order واحد** على حساب المنصة.

**Tasks**
- [ ] عند Pay: نتأكد إن العميل مالك الطلب وإن الطلب صالح للدفع وإن إجماليه محسوب على السيرفر.
- [ ] إنشاء Stripe Checkout Session بـ `mode=payment` على **Platform account**، وليس على Connected Account.
- [ ] ربط Checkout Session محليًا بـ `order_id`، وحفظ Provider ID، المبلغ، العملة والحالة.
- [ ] منع جلسات Checkout المتوازية غير اللازمة لنفس محاولة الطلب، واستخدام Idempotency مناسب لإنشاء الجلسات وإعادة المحاولات.
- [ ] التعامل مع `checkout.session.completed` مع التحقق من حالة الدفع الحقيقية؛ ولوسائل الدفع المتأخرة نتعامل مع `checkout.session.async_payment_succeeded/failed` عند الحاجة.
- [ ] ما نعتبرش `success_url` نجاح دفع؛ المصدر الموثوق هو Webhook موثّق أو جلب حالة Stripe من السيرفر.
- [ ] التأكد من مطابقة المبلغ والعملة والطلب قبل الانتقال إلى `paid`.
- [ ] تجهيز معالجة Retry والـ out-of-order events وعدم دفع نفس الطلب مرتين.

**التخزين المقترح:** استخدم `payments` الموجود أو أنشئه عند الحاجة، بفصل `provider` (`stripe`) و`provider_payment_id` و`checkout_session_id` و`order_id` و`amount_minor` و`currency` و`status`، مع قيود unique مناسبة. ما تعملش جدولين لنفس الوظيفة.

**اختبارات القبول:** دفع ناجح؛ كارت مرفوض؛ جلسة منتهية؛ Webhook مكرر؛ Redirect مزيف؛ مبلغ مختلف؛ طلب مدفوع بالفعل؛ إعادة محاولة بشكل آمن.

**شرط انتهاء Phase 2:** الطلب بيتحوّل إلى `paid` مرة واحدة بعد تأكيد موثوق، وفلوسه على حساب **المنصة**. لسه ما عملناش Transfers.

---

### Phase 3 — Seller Earnings & Transfers

**الهدف:** بعد تأكيد دفع الطلب، المنصة توزع أرباح كل بائع بشكل مستقل.

**Tasks**
- [ ] استخدام تقسيم الطلب الموجود (`sub-orders` أو المعادل الفعلي) لحساب نصيب كل بائع.
- [ ] تحديد سياسة المبالغ بوضوح: مبدئيًا **عمولة المنصة = صفر في نسخة التجربة** إلى أن يُعتمد تسعير تجاري؛ مصاريف Stripe على المنصة، وأي فروق/خصومات/شحن/ضرائب محسوبة على السيرفر.
- [ ] إنشاء سجل `seller_transfers` أو المقابل الموجود: `sub_order_id`, `seller_user_id`, `stripe_transfer_id`, `amount_minor`, `currency`, `status`, `failure_reason`.
- [ ] عند نجاح الدفع وحين يسمح منطق الطلب بالصرف، نعمل **Transfer واحد لكل بائع/جزء طلب** إلى `stripe_account_id` الخاص به. في MVP افترض إن الصرف بعد تأكيد الدفع مباشرة، ولا تربطه بـ UI callback.
- [ ] استخدام `transfer_group` لتتبع ارتباط Transfers بالطلب، و`source_transaction` مع Charge الصحيح لو مدعوم ومناسب، لتفادي مشكلة توافر الرصيد عند التحويل.
- [ ] عند فشل التحويل نخزّن الفشل ونسمح بـ Retry آمن، **بدون** إنشاء Transfer مكرر.
- [ ] نعطل التحويل لحساب غير جاهز ونظهر `blocked`/`pending` بدل ما نسقط الفلوس من السجلات.
- [ ] مراعاة Refund/Dispute: استرداد العميل من حساب المنصة **مش بيرجع تلقائيًا** التحويلات السابقة؛ نحتاج آلية Transfer Reversal وتسوية متناسبة قبل إعلان الجاهزية للإنتاج.
- [ ] لا ننفذ Payout إلى البنك بنفسنا في MVP؛ Stripe تدير الصرف الخارجي لحساب Express وفق الإعدادات.

**مثال توضيحي (قبل رسوم Stripe):** العميل دفع 150؛ بائع A له 100، وبائع B له 50. المنصة تعمل Transfer بقيمة 100 إلى A و50 إلى B وفق سياسة الصرف وحالة الرصيد. Stripe processing fees تتحملها المنصة في سيناريو الاختبار؛ لازم نتأكد من توفر الرصيد الكافي وعدم التسبب في عجز عند التفكير في الإنتاج.

**اختبارات القبول:** طلب فيه بائعين؛ بائع جاهز وآخر غير جاهز؛ نقص الرصيد؛ فشل مؤقت؛ retry؛ Webhook مكرر؛ اختلاف العملة؛ Refund جزئي؛ انتقالات الحالة الآمنة.

**شرط انتهاء Phase 3:** نقدر نتتبع لكل جزء من الطلب: مبلغ مدفوع للمنصة، مستحقات البائع، Transfer متنفذ/معلق/فاشل، من غير ازدواج.

---

### Phase 4 — Seller Monthly / Yearly Subscriptions

**الهدف:** البائع يدفع للمنصة اشتراك متكرر مقابل استخدام الخدمة، وده **تدفق مستقل** عن مبيعات العملاء.

**Tasks**
- [ ] إنشاء Product واحد للخطة الأساسية وسعرين Recurring في Stripe: `monthly` و`yearly` (قيم الأسعار من Dashboard/config وليس كود ثابت).
- [ ] استخدام **نفس حساب البائع Accounts v2** مع `configuration.customer`، وتمرير `customer_account=acct_...` في العمليات الداعمة بدل إنشاء `cus_...` منفصل.
- [ ] إنشاء Checkout Session للاشتراك بـ `mode=subscription` على حساب المنصة (تحقق من دعم `customer_account` في إصدار Checkout المستعمل، أو استخدم Subscription API الموثّق مع تدفق تحصيل متوافق؛ **ممنوع** الالتفاف بإنشاء عميل منفصل دون قرار صريح).
- [ ] حفظ `stripe_subscription_id`, `stripe_price_id`, `status`, `current_period_end` بحسب حقول إصدار Stripe الفعلي و`cancel_at_period_end` لما يتوفر.
- [ ] متابعة `invoice.paid`, `invoice.payment_failed`, `customer.subscription.created/updated/deleted` وأي أحداث لازمة حسب تنفيذ الاشتراك.
- [ ] دعم تغيير الخطة أو إلغاء التجديد باستخدام واجهة موثّقة أو Billing Portal إذا تأكد توافقها مع `customer_account`، مع توضيح سياسة الـ proration.
- [ ] صلاحيات البيع/فتح المتجر تعتمد على الاشتراك **فقط بعد اعتماد سياسة المنتج**، وما نخلطش `subscription_status` بـ `onboarding_status` أو `transfer_status`.
- [ ] الاشتراك يُدفع بوسيلة دفع يقدّمها البائع للمنصة. **مش هنخصم تلقائيًا من رصيد أرباحه في Stripe**؛ `Pay with Stripe balance` ميزة منفصلة لا تدخل في MVP.

**اختبارات القبول:** شهري ناجح؛ سنوي ناجح؛ فشل تجديد؛ إلغاء مع نهاية الفترة؛ تغيير سعر؛ Webhook مكرر؛ Subscription لا تخص البائع؛ ولا إنشاء Subscription تانية بالغلط.

**شرط انتهاء Phase 4:** البائع عنده اشتراك واحد فعال للخطة الأساسية، وحالته مستقلة عن الأموال المستحقة من الطلبات.

## 5) قواعد تنفيذ مشتركة لكل المراحل

### A) حدود طبقات الكود

- Controllers: HTTP/Auth/Validation/Responses فقط.
- Services: Onboarding وCheckout وTransfers وSubscriptions حسب الحاجة الفعلية.
- Stripe client / Gateway: نقطة واحدة لطلبات Stripe ومتابعة الأخطاء، بدون مشروع Framework للمدفوعات.
- Models: Relationships وCasts وحالات محلية؛ **لا** تستدعي Stripe API داخل Model.
- Webhook Controller: يتأكد من Signature ويصنّف الحدث؛ المعالجة الفعلية في Handler/Service؛ Jobs عند الحاجة لإعادة المحاولة.
- استخدم الـ classes/paths الموجودة في Repository؛ الأسماء المقترحة مش إلزامية.

### B) فصل الأحداث الخاصة بالحسابات عن أحداث الدفع

- حسابات v2 بتصدر **thin events**؛ استقبلها مع التحقق من التوقيع، وجيب تفاصيل الحساب/التكوين من Stripe عند الحاجة بدل افتراض إن كل التفاصيل داخل Payload.
- فواتير واشتراكات وCheckout وTransfers قد تظل أحداث/واجهات من v1؛ متفترضش إن كل الأحداث صارت v2.
- اضبط Webhook destinations وScopes الصح (`Your account` لأحداث v2 لحسابات البائعين في منصة Connect، وأحداث الحسابات/الاشتراكات/المدفوعات حسب نطاقها الحقيقي).
- لكل حدث: deduplication بواسطة Event ID، logging بدون أسرار، ومعالجة idempotent، وآلية متابعة حالات الفشل.

### C) التحقق من الفلوس والأرصدة

- ما تخلطش `Order = paid` مع `Transfer = succeeded` أو `Payout = paid`.
- لازم يدعم الكود حالات فشل شبكة Stripe، Webhook مكرر/متأخر، ورصيد غير كاف.
- تحصيل الفلوس على المنصة معناه إن رسوم الدفع وRefunds/Disputes تخص المنصة؛ ده قرار مالي حقيقي مش مجرد اختيار API.
- انتقالات الحالة تكون صريحة ومحمية داخل DB Transactions حيث يلزم، لكن **ممنوع** استدعاء Stripe API داخل Transaction طويلة ماسكة Locks إن أمكن.

## 6) تجربة وهمية 100%: بيانات Sandbox

**ممنوع** طلب بطاقة شخصية حقيقية أو بيانات بنك حقيقي أو مفاتيح Live. نستعمل بيانات الاختبار الرسمية المتاحة لكل حقل وبلد، مثل:

| السيناريو | بيانات Test |
|---|---|
| نجاح دفع بطاقة | `4242 4242 4242 4242` مع تاريخ مستقبل وCVC تجريبي |
| رفض الدفع | `4000 0000 0000 9995` |
| تأكيد SMS لحساب Test | `000-000` عندما تطلب Stripe رمز SMS الخاص بالاختبار |
| اختبار تحقق هوية | Stripe verification test tokens وصور الاختبار، مش مستندات شخصية حقيقية |
| بريد البائع | بريد تجريبي غير شخصي، **لكن** لو Stripe طالبت فعلًا بإثبات امتلاك البريد نستخدم صندوق اختبار يمكن الوصول له أو بديل Sandbox موثّق؛ مفيش كود عام لتجاوز تحقق الإيميل |
| حساب بنكي | بيانات/رموز اختبار متوافقة مع البلد والسيناريو طبقًا لصفحة Stripe الرسمية |

**مهم:** كلمة "Sandbox" لا تعني إن Stripe هتتجاوز تلقائيًا متطلبات الـ UI. استجابة التحقق والحقول بتختلف حسب بلد الحساب، والـ dashboard، والـ capabilities. ما ننفّذش أي تخطي غير مدعوم رسميًا. ونجاح API في Sandbox مش ضمان إن نفس الحساب مؤهل للإنتاج.

## 7) نطاق التنفيذ الحالي (STOP RULE)

**ابدأ بـ Phase 0 ثم Phase 1 فقط.** لا تنفذ Checkout أو Transfers أو Billing قبل مراجعة نتائج المرحلة الأولى.

لما نطلب من Codex «نفذ Phase N»:
1. اقرأ `AGENTS.md` و`stripe.md` وافحص الملفات المتأثرة في المشروع.
2. اعرض خطة قصيرة بأسماء الملفات المتوقع تعديلها والسبب، وأي فجوة بين الوثائق والمشروع.
3. نفذ الـ Phase المطلوبة فقط بعد الموافقة بحسب قواعد `AGENTS.md`.
4. اعمل اختبارات النجاح والفشل والأمان والتكرار.
5. شغّل الاختبارات والأدوات المتاحة (`php artisan test`، `./vendor/bin/pint --test`، `git diff --check`) وسجل نتائج فعلية فقط.
6. راجع `git diff`، واشرح للمطور: إيه اتغير، ليه، الملفات، الاختبارات، المخاطر، والخطوة التالية.
7. ما تعملش Commit/Push، وما تبدأش Phase تانية تلقائيًا.

## 8) حاجات برة النطاق حاليًا

- Live activation، صرف فلوس حقيقية، معالجة الالتزامات القانونية والضريبية الفعلية.
- إنشاء بوابة KYC خاصة بنا أو تخزين هويات وحسابات بنكية.
- Paymob أو أي Provider آخر في الوقت الحالي.
- تقسيم مبالغ متقدم جدًا أو Refund/Dispute automation كاملة قبل مرحلة مستقلة واختبارات كفاية.
- خصم اشتراك البائع تلقائيًا من رصيد أرباحه (`Pay with Stripe balance`).
- نظام محاسبي عام أو Microservices أو Events/Interfaces كتير من غير داعٍ.

## 9) شرط مهم قبل أي تشغيل حقيقي

**مصر ليست ضمن الدول المدرجة حاليًا في Stripe Global Availability للحسابات التجارية المعتادة.** اختبار Sandbox شيء، وقبول دفعات حقيقية من شركة/منصة مصرية شيء تاني. ما نستخدمش بيانات بلد أو شركة وهمية لتفعيل Live. قبل الإنتاج لازم التأكد من الكيان القانوني والبلد المدعوم، وبلدان البائعين، ودعم Separate Charges & Transfers عبر الحدود، والضرائب، والرسوم، والمرتجعات، والـ chargebacks. ده ممكن يغيّر خطة التشغيل التجاري، **مش القرار التقني للتجربة**.

## 10) مصادر Stripe الرسمية (راجَعها عند التنفيذ)

1. [Accounts v2](https://docs.stripe.com/connect/accounts-v2)
2. [Recommended Connect integrations — Stripe recommends v2 for new integrations](https://docs.stripe.com/connect/integration-recommendations)
3. [Accounts v2 configuration: Dashboard, liability, requirements](https://docs.stripe.com/connect/accounts-v2/connected-account-configuration)
4. [Accounts v2 as customers: `customer_account`](https://docs.stripe.com/accounts-v2/use-accounts-as-customers)
5. [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding)
6. [Separate charges and transfers](https://docs.stripe.com/connect/separate-charges-and-transfers)
7. [Accounts v2 migration / webhook scopes / v1 interoperability](https://docs.stripe.com/connect/accounts-v2/migrate-integration)
8. [Testing Connect](https://docs.stripe.com/connect/testing)
9. [Stripe PHP official SDK](https://github.com/stripe/stripe-php)
10. [Stripe Global Availability](https://stripe.com/global)

> **المبدأ النهائي:** Accounts v2 + Express dashboard + recipient/customer configurations + Stripe-hosted onboarding + Checkout + Separate Charges & Transfers + Stripe Billing. الاختيار ثابت للمشروع التجريبي. ما نغيّرش الأدوات لمجرد ظهور Tutorial مختلف؛ نعدّل فقط لو توثيق رسمي أو اختبار فعلي أثبت مانع مؤثر.
