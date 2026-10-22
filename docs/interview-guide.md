# Cativa — Project & Interview Guide

الملف ده مذكرة بالمصري تساعدك تفهم مشروع **Cativa** وتعرف تعرضه في الـinterview. متحفظش أسماء الـtables وخلاص؛ افهم الداتا بتمشي إزاي، وليه كل قرار اتاخد، وإيه الـtrade-offs بتاعته.

## خطة المذاكرة بالـPhases

| Phase | هتذاكر إيه؟ | المطلوب منك في الآخر |
| --- | --- | --- |
| Phase 0 | قصة المشروع والـArchitecture | تعرف تقدم المشروع في دقيقة وتشرح تقسيم الكود |
| Phase 1 | Database foundation | ترسم العلاقات الأساسية وتشرح Users وRoles |
| Phase 2 | Flexible Product Catalog | تشرح Product وVariant وAttribute وOption والفرق بينهم |
| Phase 3 | Cart وOrders وStock | تشرح رحلة الشراء، Sub-orders، snapshots والـlocking |
| Phase 4 | Payments وStripe | تشرح الدفع، Connect onboarding، webhooks والـidempotency |
| Phase 5 | Search والتحديات والـInterview | تجاوب عن القرارات، العيوب، التحسينات والأسئلة المتوقعة |

ذاكر Phase واحدة كل مرة. في آخر كل Phase حاول تشرحها بصوتك من غير ما تبص في الملف، وبعدها ارجع شوف إيه اللي نسيته.

---

## Phase 0 — قصة المشروع والـArchitecture

**هدف الـPhase:** تعرف تقول المشروع بيحل مشكلة إيه، وتشرح تقسيم الكود من غير ما تدخل في تفاصيل الداتا بيز لسه.

## 1. تقديم المشروع في دقيقة

تقدر تقدم المشروع بالإنجليزي كده:

> Cativa is a multi-vendor e-commerce platform built with Laravel. It supports multiple roles such as customers, sellers, admins, and delivery users. Sellers can create generic products with flexible attributes and variants, so the catalog is not limited to clothing. Customers can search, filter, add exact variants to their carts, apply seller-specific coupons, and place orders containing products from multiple sellers. Each order is split into sub-orders per seller. The project also integrates Stripe Checkout for payments and Stripe Connect for seller onboarding, with secure webhook processing, idempotency, database transactions, and concurrency protection.

وبالمصري:

> Cativa هو Marketplace متعدد البائعين. يستطيع كل بائع إضافة منتجات عامة بخصائص ونسخ مختلفة، ويستطيع العميل الشراء من أكثر من بائع داخل طلب واحد. يقوم النظام بتقسيم الطلب داخليًا حسب البائع، ويحمي المخزون والدفع باستخدام database transactions وrow locking وStripe webhooks موثقة.

## 2. معمارية المشروع

المسار المعتاد للـrequest هو:

```text
Request
   ↓
Route + Middleware
   ↓
Form Request
Validation + Authorization
   ↓
Controller
HTTP concerns only
   ↓
Service
Business workflow
   ↓
Models + Database
```

أما التكامل مع Stripe فيمر بالشكل التالي:

```text
Service
   ↓
Stripe Gateway
   ↓
Stripe SDK/API
```

مسؤولية كل طبقة ببساطة:

- **Routes and Middleware:** توجيه الطلب والتأكد من تسجيل الدخول والـrole المناسب.
- **Form Requests:** التحقق من المدخلات وعمل authorization للـresource.
- **Controllers:** التعامل مع HTTP request وresponse فقط.
- **Services:** تنفيذ العمليات التي تحتوي على business workflow حقيقي، مثل إنشاء الطلب.
- **Models:** العلاقات والـcasts والسلوك البسيط المتعلق بالداتا.
- **Gateways:** عزل تفاصيل Stripe SDK عن بقية النظام.

إجابة سؤال: لماذا استخدمت Services؟

> عمليات مثل إنشاء Order أو بدء Stripe onboarding تحتوي على validation وtransactions وتعديل أكثر من table. وضع ذلك في Controller سيجعله كبيرًا وصعب الاختبار. في الوقت نفسه لم أستخدم Service لكل عملية صغيرة حتى لا يتحول التصميم إلى over-engineering.

### راجع نفسك بعد Phase 0

- هل تقدر تقدم المشروع في دقيقة من غير ما تقرأ؟
- هل تقدر تشرح ليه الـController مش شايل الـbusiness logic كلها؟
- هل عارف الفرق بين دور Form Request وService وGateway؟

---

## Phase 1 — Database Foundation

**هدف الـPhase:** تفهم شكل الداتا بيز العام، والعلاقات الأساسية، وليه نظام الـroles معمول Many-to-Many.

## 3. خريطة قاعدة البيانات

```text
users ──< role_user >── roles

users (seller)
   └──< products
          ├──< product_variants
          │      └──< product_variant_options
          ├──< product_attributes
          ├──< product_pictures
          └──< product_sub_category >── sub_categories ──> categories

users (buyer)
   ├──1 cart
   │    ├──< cart_items ──> product_variants
   │    └──< cart_coupons >── coupons
   ├──< addresses
   └──< orders
          ├──< sub_orders ──> users (seller)
          │      └──< order_items
          │             └──< order_item_pictures
          └──< payments

users (seller)
   └──1 seller_stripe_accounts

stripe_webhook_events
```

## 4. المستخدمون والأدوار

### `users`

يخزن بيانات المستخدم الأساسية:

- الاسم الأول والأخير.
- البريد الإلكتروني.
- رقم الهاتف.
- كلمة المرور المشفرة.
- وقت تأكيد البريد.

### `roles`

يحتوي على أدوار النظام، مثل:

- `user`
- `seller`
- `admin`
- `delivery`

### `role_user`

Pivot table تنفذ علاقة Many-to-Many بين المستخدمين والأدوار:

```text
users M:N roles
```

هذا يسمح لنفس المستخدم بأن يكون Seller وCustomer في الوقت نفسه. يوجد Composite Primary Key على `role_id + user_id` لمنع إضافة الدور نفسه للمستخدم مرتين.

وجود Pivot table أفضل من عمود `role` داخل `users` لأن المستخدم قد يحتاج إلى أكثر من دور، ولأن إضافة أدوار جديدة لا تتطلب تغيير تصميم جدول المستخدمين.

### راجع نفسك بعد Phase 1

- ارسم الـdatabase map من الذاكرة حتى لو بشكل مبسط.
- اشرح الفرق بين One-to-One وOne-to-Many وMany-to-Many بمثال من المشروع.
- اشرح ليه `role_user` أحسن من عمود `role` واحد داخل `users`.

---

## Phase 2 — Flexible Product Catalog

**هدف الـPhase:** دي أهم Phase في جزء الداتا بيز. لازم تقدر تشرح إزاي المنتج بقى ينفع موبايل أو هدوم أو تكييف، مش هدوم بس.

## 5. تصميم المنتجات المرن

النظام كان في البداية قريبًا من الملابس باستخدام `size` و`color`. تم تحويله إلى catalog عام يقبل الملابس والموبايلات والتكييفات والأجهزة المنزلية وغيرها.

### `products`

يمثل المنتج الأساسي، مثل `iPhone 15 Pro`، ويحتوي على:

- `title`
- `slug`
- `description`
- `cover_image`
- `price`
- `discount_amount`
- `seller_id`
- `is_active`
- `sold_count`
- `rating_avg`
- `rating_count`

يوجد Unique Constraint على `seller_id + slug`. لذلك لا يمكن للبائع نفسه إنشاء منتجين بنفس الـslug، بينما يمكن لبائع آخر استخدام الاسم نفسه.

### `product_variants`

الـVariant هو الوحدة الفعلية التي يتم بيعها والتي تمتلك سعرًا ومخزونًا.

مثال:

| Variant | SKU | Price | Stock |
| --- | --- | ---: | ---: |
| Black / 128 GB | IP15-BLK-128 | 45,000 | 5 |
| Black / 256 GB | IP15-BLK-256 | 50,000 | 3 |
| Blue / 128 GB | IP15-BLU-128 | 46,000 | 4 |

أهم الحقول:

- `product_id`
- `sku`
- `price`
- `stock`
- `option_signature`

سعر الـvariant يمكن أن يكون `null`. في هذه الحالة يستخدم النظام سعر المنتج الأساسي:

```php
$variant->price ?? $product->price
```

هذا يمنع تكرار السعر نفسه داخل جميع الـvariants دون داعٍ.

### ما هو SKU؟

SKU اختصار لـ **Stock Keeping Unit**. هو كود تشغيلي يساعد البائع أو المخزن أو أي نظام خارجي على التعرف على نسخة محددة من المنتج، مثل:

```text
IP15-BLK-256
```

الفرق بينه وبين `id`:

- `id`: مفتاح داخلي تستخدمه قاعدة البيانات والعلاقات.
- `SKU`: كود business يستخدمه البائع والمخزن والتكاملات الخارجية.

حاليًا SKU اختياري ومفهرس. إذا أصبح عدم التكرار requirement رسميًا، يمكن إضافة unique constraint على مستوى البائع.

### `product_variant_options`

بدل ربط كل المنتجات دائمًا بـ`size` و`color`، يمتلك كل variant مجموعة dynamic options.

مثال موبايل:

| key | value |
| --- | --- |
| color | Black |
| storage | 256 GB |

مثال ملابس:

| key | value |
| --- | --- |
| color | Blue |
| size | XL |

مثال تكييف:

| key | value |
| --- | --- |
| horsepower | 2.25 HP |
| type | Inverter |

العلاقة هي:

```text
product_variant 1:N product_variant_options
```

يوجد Unique Constraint على `product_variant_id + key`، ولذلك لا يمكن أن يحتوي الـvariant نفسه على قيمتين للمفتاح نفسه.

### `option_signature`

التركيبتان التاليتان تمثلان نفس الـvariant حتى لو اختلف ترتيب الإدخال:

```text
color=Black, storage=256GB
storage=256GB, color=Black
```

لحل المشكلة يقوم النظام بـ:

1. Normalize للمفاتيح والقيم.
2. ترتيب الـoptions.
3. تكوين representation ثابت.
4. إنشاء SHA-256 hash.
5. تخزينه في `option_signature`.

ثم يفرض Unique Constraint على `product_id + option_signature`، وبذلك لا يمكن إنشاء نفس تركيبة الاختيارات مرتين.

إجابة جاهزة:

> I created a deterministic option signature by normalizing and sorting the options before hashing them. This guarantees variant uniqueness independently of input order.

### `product_attributes`

الـAttributes تصف المنتج بالكامل، وليست اختيارًا يحدده العميل عند الشراء.

مثال:

| key | value |
| --- | --- |
| processor | A17 Pro |
| warranty | 1 Year |
| screen_type | OLED |

الخصائص الرقمية تستخدم `numeric_value` و`unit`:

| key | numeric_value | unit |
| --- | ---: | --- |
| screen_size | 6.1 | inch |
| weight | 187 | g |

هذا يسمح بعمل range filtering، مثل البحث عن شاشة بين 6 و7 بوصات.

### الفرق بين Attributes وVariant Options

- `product_attributes`: معلومات تصف المنتج كله ولا تحدد وحدة مخزون مستقلة.
- `product_variant_options`: اختيارات تحدد النسخة التي سيشتريها العميل، ولكل نسخة stock وسعر وSKU محتمل.

مثال كامل:

```text
Product: iPhone 15 Pro

Attributes:
- processor = A17 Pro
- screen_type = OLED

Variant 1:
- color = Black
- storage = 128 GB
- stock = 5

Variant 2:
- color = Blue
- storage = 256 GB
- stock = 3
```

### لماذا لم نستخدم JSON لكل شيء؟

تم استخدام relational tables للداتا التي يتم البحث والفلترة فيها، لأن ذلك يسمح بـ:

- إضافة indexes.
- استخدام database constraints.
- البحث والفلترة بكفاءة أوضح.
- منع تكرار المفاتيح.
- بناء علاقات واستعلامات يمكن صيانتها.

يستخدم JSON فقط عندما تكون البيانات snapshot أو لا تحتاج إلى فلترة متكررة، مثل خيارات المنتج المحفوظة تاريخيًا داخل order item.

عيب التصميم المرن أن المفاتيح free-form قد تنتج قيمًا مختلفة مثل `RAM` و`ram`. أحد التحسينات المستقبلية هو إنشاء attribute definitions لكل category لتوحيد الأسماء والوحدات والقيم المقبولة.

## 6. التصنيفات والصور

### `categories`

يمثل التصنيف الرئيسي، مثل:

- Electronics
- Fashion
- Home Appliances

### `sub_category`

يمثل التصنيف الفرعي، مثل:

```text
Electronics
  ├── Mobile Phones
  └── Laptops
```

كل Subcategory تنتمي إلى Category واحدة.

### `product_sub_category`

Pivot table تنفذ علاقة Many-to-Many:

```text
products M:N sub_categories
```

وبذلك يمكن أن يظهر المنتج في أكثر من تصنيف فرعي عند الحاجة.

### `product_pictures`

يخزن صور المنتج الإضافية، بينما توجد الصورة الأساسية في `products.cover_image`. عند حذف المنتج، تحذف العلاقات والصور التابعة له وفق سياسة الـcascade المستخدمة.

### راجع نفسك بعد Phase 2

- اعمل مثال واحد لموبايل ومثال واحد لتيشيرت باستخدام نفس الـtables.
- اشرح الفرق بين Product Attribute وVariant Option من غير ما تحفظ التعريف.
- اشرح فايدة SKU و`option_signature`.
- جاوب: ليه مخزناش كل الخصائص والاختيارات في JSON؟

---

## Phase 3 — Cart وOrders وStock

**هدف الـPhase:** تفهم رحلة الداتا من عنوان العميل والـCart لحد إنشاء Order وتقسيمها على البائعين، وإزاي النظام بيحافظ على السعر والمخزون.

## 7. العناوين

### `addresses`

يحتوي على:

- `user_id`
- `country`
- `city`
- `street`
- `is_default`
- `note`
- `lat`
- `long`

يوجد index على `user_id + is_default` لتسهيل الوصول إلى عنوان المستخدم الافتراضي. التطبيق مسئول حاليًا عن الحفاظ على عنوان افتراضي واحد؛ ويمكن تحسين ضمان ذلك على مستوى قاعدة البيانات حسب الـdatabase engine المستخدم.

## 8. Cart والـCoupons

### `carts`

كل Cart مرتبطة بمستخدم:

```text
user 1:1 cart
```

العلاقة في التطبيق One-to-One، لكن من التحسينات الممكنة إضافة Unique Constraint إلى `carts.user_id` لضمان ذلك أيضًا على مستوى قاعدة البيانات.

### `cart_items`

كل row تحتوي على:

- `cart_id`
- `product_id`
- `product_variant_id`
- `quantity`

ترتبط الـcart بالـvariant المحدد، وليس بالمنتج فقط، لأن السعر والمخزون والاختيارات موجودة على الـvariant.

يوجد Unique Constraint على `cart_id + product_variant_id`. إذا أضاف العميل الـvariant نفسه مرة أخرى، تزيد الكمية بدل إنشاء row جديدة.

### `coupons`

كل Coupon تابع إلى Seller، ويحتوي على:

- `code`
- `percentage`
- `expire_date`
- `max_usage`
- `used_count`
- `seller_id`
- `is_active`

يوجد Unique Constraint على `code + seller_id`، وبالتالي يمكن أن يستخدم بائعان الكود نفسه، لكن لا يكرره البائع نفسه.

### `cart_coupons`

Pivot table بين Cart وCoupons. ارتباط الـcoupon بالبائع مهم لأن Cart واحدة قد تحتوي على منتجات من بائعين مختلفين، وكل بائع مسئول عن الخصم الخاص بمنتجاته.

## 9. تصميم الطلب متعدد البائعين

### `orders`

يمثل عملية الشراء الكاملة التي يراها العميل، ويحتوي على:

- `user_id`
- `address_id`
- `subtotal`
- `discount`
- `total_price`
- `phone`
- `status`
- `payment_status`
- `payment_method`

مثال:

```text
Order total = 3,000 EGP

Seller A items = 2,000 EGP
Seller B items = 1,000 EGP
```

### `sub_orders`

يقسم النظام الطلب إلى Sub-order لكل Seller:

```text
Order #100
  ├── SubOrder: Seller A → 2,000 EGP
  └── SubOrder: Seller B → 1,000 EGP
```

كل `sub_order` يحتوي على:

- `order_id`
- `seller_id`
- `subtotal`
- `discount`
- `total`
- `status`

يوجد Unique Constraint على `order_id + seller_id` لضمان وجود Sub-order واحدة فقط لكل بائع داخل الطلب.

هذا التصميم يسمح لكل Seller برؤية منتجاته وتحديث حالتها بشكل مستقل، مع بقاء checkout وOrder واحدة بسيطة بالنسبة للعميل.

### `order_items`

كل Order item مرتبطة بـSub-order، وتحتفظ بـSnapshot من بيانات المنتج وقت الشراء:

- عنوان المنتج.
- الـslug والوصف.
- الصورة.
- السعر وقت الشراء.
- SKU وقت الشراء.
- Variant options وقت الشراء.
- الكمية.

الـSnapshot مهم لأن البائع قد يغير اسم المنتج أو سعره أو يحذفه بعد إتمام الطلب. يجب أن يعرض الطلب القديم البيانات التي اشترى بها العميل، وليس بيانات الـcatalog الحالية.

لذلك يمكن أن يصبح `product_id` بقيمة `null` عند حذف المنتج، بينما تظل بيانات الطلب التاريخية محفوظة.

إجابة جاهزة:

> Order items are immutable historical records. I snapshot the product, variant options, and purchase price so later catalog changes do not corrupt old orders.

### `order_item_pictures`

يحتفظ بصور المنتج التي تخص الـorder item كجزء من الـsnapshot التاريخي.

## 10. إنشاء الطلب وحماية المخزون

ينفذ إنشاء الطلب داخل Database Transaction:

```text
Lock cart and variants
        ↓
Revalidate active products and stock
        ↓
Calculate prices server-side
        ↓
Group items by seller
        ↓
Create order
        ↓
Create one sub-order per seller
        ↓
Create snapshot order items
        ↓
Decrease stock
        ↓
Clear cart
        ↓
Commit
```

### لماذا نستخدم `lockForUpdate`؟

إذا كان المخزون يساوي 1 وحاول مستخدمان الشراء في اللحظة نفسها، فقد يقرأ الاثنان القيمة 1 ويشتريان الوحدة نفسها. باستخدام transaction وrow-level lock:

1. أول request تقفل صف الـvariant.
2. الثانية تنتظر انتهاء الأولى.
3. بعد خصم الأولى للمخزون، تقرأ الثانية القيمة الجديدة.
4. ترفض الثانية الطلب إذا أصبح المخزون صفرًا.

إجابة جاهزة:

> Validation before checkout is not sufficient because stock can change concurrently. I revalidate inside the transaction and lock the variant rows before decrementing stock.

كما أن الأسعار والخصومات تحسب على الخادم. لا يثق النظام في السعر أو الإجمالي المرسل من Frontend، لأن المستخدم يستطيع تعديل الـrequest.

### راجع نفسك بعد Phase 3

- اشرح ليه Cart item مرتبطة بـvariant مش product بس.
- ارسم Order فيها منتجات من بائعين واتقسمت لـSub-orders.
- اشرح ليه Order items فيها snapshots.
- اشرح race condition المخزون وإزاي transaction و`lockForUpdate` بيحلوها.

---

## Phase 4 — Payments وStripe

**هدف الـPhase:** تعرف تشرح رحلة الدفع بأمان، والفرق بين Stripe Checkout وStripe Connect، وليه الـwebhook أهم من success URL.

## 11. المدفوعات

### `payments`

تم فصل Payments عن Orders لأن Order واحدة قد تمر بأكثر من محاولة دفع:

- محاولة انتهت صلاحيتها.
- محاولة فشلت.
- محاولة جديدة نجحت.

أهم الحقول:

- `order_id`
- `provider`
- `provider_payment_id`
- `checkout_session_id`
- `idempotency_key`
- `amount_minor`
- `currency`
- `status`
- `paid_at`

### لماذا `amount_minor`؟

يخزن المبلغ بأصغر وحدة للعملة:

```text
100.50 EGP → 10050
```

هذا يمنع مشاكل الدقة التي تنتج عن استخدام floating-point في القيم المالية.

### ما هي Idempotency؟

تعني أن تكرار العملية نفسها لا ينشئ نتيجة مالية مكررة. يستخدم `idempotency_key` للحماية من:

- Double click.
- Network retry.
- Timeout بعد وصول الطلب إلى Stripe.
- إعادة نفس request.

## 12. Stripe Connect Seller Onboarding

### `seller_stripe_accounts`

علاقة One-to-One بين Seller وحساب Stripe المتصل، ويحتوي الجدول على:

- `seller_id`
- `stripe_account_id`
- `onboarding_status`
- `payouts_status`
- `transfers_status`
- `requirements_due`
- `disabled_reason`
- `last_synced_at`

رحلة البائع:

```text
Seller starts onboarding
        ↓
Create or reuse Stripe connected account
        ↓
Store Stripe account ID locally
        ↓
Generate Stripe account link
        ↓
Seller completes required information
        ↓
Stripe sends webhook
        ↓
Update local account status
```

الـwebhook ضروري لأن Seller يكمل بعض البيانات داخل Stripe خارج التطبيق، ولذلك لا يمكن الاعتماد على redirect وحده لتحديث الحالة.

النظام يطلب المعلومات المطلوبة حاليًا بدل جمع المتطلبات المستقبلية مقدمًا، لتقليل خطوات onboarding وعدم إرهاق البائع.

### `stripe_webhook_events`

يحتفظ بـStripe event IDs التي تمت معالجتها. قد تعيد Stripe إرسال الحدث نفسه، ولذلك يوجد Unique Constraint على `event_id` لمنع معالجته مرتين.

## 13. تأمين Stripe Webhooks

عند استقبال Stripe webhook يقوم النظام بـ:

1. التحقق من Stripe signature باستخدام الـwebhook secret الصحيح.
2. رفض event types غير المطلوبة.
3. التأكد من أن الحدث لم تتم معالجته من قبل.
4. مطابقة Payment وOrder والـmetadata.
5. مقارنة amount وcurrency والـidentifiers.
6. تحديث البيانات داخل transaction.
7. منع حدث فشل متأخر من تحويل Payment ناجحة إلى failed.

صفحة success أو return URL لا تحول الطلب إلى Paid؛ لأن المستخدم يستطيع فتحها يدويًا. الـverified Stripe webhook هو مصدر الحقيقة لحالة الدفع.

وصف الحالة الحالية بدقة:

> Stripe Connect onboarding and account synchronization are implemented, and Checkout uses a transfer group, but the final seller transfer and distribution workflow is a separate settlement phase.

لا يجب الادعاء بأن تحويل أرباح البائعين مكتمل إذا لم يتم تنفيذ settlement الفعلي بعد.

### راجع نفسك بعد Phase 4

- اشرح ليه Order واحدة ممكن يبقى ليها أكتر من Payment attempt.
- اشرح `amount_minor` وidempotency بمثال بسيط.
- وضح الفرق بين Stripe Checkout وStripe Connect.
- جاوب: ليه مينفعش نعتبر إن الدفع نجح من success URL؟
- متقولش إن seller transfers مكتملة؛ وضح إن الـsettlement لسه Phase منفصلة.

---

## Phase 5 — Search والتحديات والاستعداد للـInterview

**هدف الـPhase:** تربط كل القرارات ببعض، وتكون جاهز للأسئلة الصعبة، وتعرض المميزات والعيوب بصدق.

## 14. البحث والفلترة

`ProductFilter` يدعم:

- البحث النصي.
- Category وSubcategory.
- Seller.
- حد أدنى وأقصى للسعر.
- المنتجات المتوفرة فقط.
- Product attributes.
- Numeric attribute ranges.
- Variant options.
- Sorting.
- Pagination.

مثال:

```text
search = samsung
attribute[brand] = Samsung
numeric[screen_size][min] = 6
option[color] = Black
in_stock = true
```

إذا بحث العميل عن:

```text
color = Black
storage = 256 GB
in_stock = true
```

يجب أن توجد الشروط الثلاثة داخل الـvariant نفسها. الخطأ الشائع هو مطابقة اللون من variant والمساحة من variant أخرى والـstock من ثالثة. لذلك تجمع شروط الـvariant داخل نفس `whereHas('variants', ...)`.

يستخدم النظام eager loading للعلاقات المطلوبة لتجنب N+1 Queries، مع pagination وindexes مناسبة لمسارات البحث الشائعة.

## 15. أهم التحديات وكيفية شرحها

### تحويل المنتج من ملابس إلى منتج عام

> The original schema had clothing-specific size and color fields. I introduced generic variant options and product attributes, backfilled existing data, generated stable option signatures, and kept the old nullable columns temporarily for safe backward compatibility.

### بناء Order متعدد البائعين

> I separated the customer-facing order from seller-facing sub-orders. This allows each seller to manage their own items and status while the customer still sees one checkout.

### الحفاظ على بيانات الطلب القديمة

> I stored snapshots on order items because catalog data is mutable, but financial and order history must remain immutable.

### منع بيع مخزون غير متاح

> I used transactions, row-level locks, and server-side revalidation at checkout time to protect against concurrent purchases.

### تأمين الدفع

> Payment state changes only through verified Stripe webhooks. I validate signatures, amounts, currency, metadata, and identifiers, and I deduplicate webhook events.

### تقليل خطوات Seller onboarding

> I requested only currently due requirements instead of collecting future requirements upfront, reducing onboarding friction while keeping Stripe as the source of compliance requirements.

### البحث داخل Catalog مرن

> I normalized filterable attributes and options instead of putting everything in JSON, added suitable indexes, and ensured multiple option conditions resolve against the same variant.

### تجنب N+1 Queries

> I used eager loading for variants, options, pictures, and category relations, with pagination for product listing pages.

## 16. أسئلة مقابلات متوقعة

### لماذا اخترت Laravel؟

يوفر Laravel authentication وauthorization وvalidation وEloquent relationships وmigrations وtesting tools وtransactions داخل ecosystem متكامل. لذلك كان مناسبًا لبناء Marketplace بسرعة مع الحفاظ على تنظيم المشروع.

### ما الفرق بين Authentication وAuthorization؟

- **Authentication:** تحديد هوية المستخدم.
- **Authorization:** تحديد ما إذا كان المستخدم يملك صلاحية تنفيذ العملية.

Session أو Sanctum يحددان المستخدم، ثم Middleware أو Policy أو Form Request يحدد هل يستطيع تعديل المنتج أو الوصول إلى العملية.

### لماذا لا تثق في السعر القادم من Frontend؟

يستطيع المستخدم تعديل الـrequest. لذلك يعيد النظام قراءة سعر المنتج والـvariant والخصومات والـcoupon من قاعدة البيانات، ثم يحسب الإجمالي على الخادم.

### لماذا تستخدم Database Transaction؟

إنشاء الطلب يغير عدة tables. يجب أن تنجح جميع التغييرات أو يحدث rollback لها جميعًا، حتى لا يظهر Order بلا items أو يخصم المخزون بلا Order.

### ما الفرق بين `cascadeOnDelete` و`nullOnDelete` و`restrictOnDelete`؟

- `cascadeOnDelete`: حذف الأب يحذف السجلات التابعة، مثل حذف variants التابعة للمنتج.
- `nullOnDelete`: يحافظ على السجل التابع ويجعل الـforeign key بقيمة null، مثل product reference داخل order item.
- `restrictOnDelete`: يمنع حذف السجل الأب حتى يتم التعامل مع العلاقة الحساسة.

### لماذا نضيف Indexes؟

تقلل الـindexes عدد الصفوف التي تفحصها قاعدة البيانات في عمليات البحث والـjoins. توجد indexes على الحقول المستخدمة في مسارات شائعة، مثل seller وactive status وattribute key/value.

لكن الـindex ليست مجانية؛ فهي تستهلك مساحة وتزيد تكلفة الكتابة، ولذلك يجب إضافتها حسب query patterns الحقيقية.

### ما هي مشكلة N+1؟

تحدث عندما يجلب التطبيق قائمة منتجات ثم ينفذ query إضافية لكل منتج لجلب علاقاته. يتم حلها باستخدام eager loading مثل `with()` للعلاقات المطلوبة.

### لماذا استخدمت Sanctum؟

Sanctum مناسب لتأمين REST API لتطبيق first-party دون تعقيد OAuth الكامل، بينما تستخدم واجهة الويب session authentication.

### كيف تختبر Stripe؟

باستخدام mocks أو fakes للـgateway وwebhook payloads دون real network calls داخل الاختبارات المعتادة، مع تغطية حالات مثل:

- Invalid signature.
- Duplicate event.
- Amount mismatch.
- Successful payment.
- Failed or expired payment.
- Unauthorized order access.

### كيف يمكن توسيع المشروع؟

يبدأ القرار بقياس الأداء. عند ظهور الحاجة يمكن إضافة:

- Cache للـcatalog.
- Queues للـwebhooks والمهام الثانوية.
- Object storage وCDN للصور.
- Read replicas.
- Search engine مثل Meilisearch.

لا تتم إضافة هذه المكونات قبل وجود حاجة وقياسات واضحة حتى لا يحدث over-engineering.

## 17. تحسينات مستقبلية يمكن مناقشتها

إذا سأل المحاور: **What would you improve?** يمكن ذكر:

- إضافة Unique Constraint إلى `carts.user_id`.
- استخدام decimal بدل float لنسبة الـcoupon.
- إضافة stock reservation لها expiry بدل الخصم المبكر للمخزون.
- إعادة المخزون تلقائيًا عند إلغاء Order غير مدفوعة.
- إكمال seller settlement وtransfers.
- إضافة attribute definitions حسب Category لتوحيد المفاتيح والوحدات.
- إزالة أعمدة `size` و`color` القديمة بعد التأكد من نجاح الانتقال بالكامل.
- جعل SKU unique حسب Seller إذا أصبح ذلك business requirement.
- استخدام Full-text Search أو Meilisearch عند نمو الـcatalog.
- إضافة Review system كامل بدل الاعتماد فقط على rating counters.

ذكر هذه النقاط لا يضعف المشروع؛ بل يوضح فهم الـtrade-offs والفرق بين المطلوب حاليًا والتطوير المستقبلي.

## 18. ترتيب العرض العملي

يفضل تنفيذ الـdemo بهذا الترتيب:

1. تسجيل Seller.
2. بدء Stripe onboarding ورؤية حالة الحساب.
3. إنشاء منتج عام، مثل موبايل.
4. إضافة attributes مثل processor وwarranty.
5. إضافة variants مثل Black/128 GB وBlue/256 GB.
6. تجربة البحث والفلترة بالـattributes والـoptions.
7. إضافة exact variant إلى Cart.
8. تطبيق Coupon خاص بالـSeller.
9. إنشاء Order يحتوي على منتجات من أكثر من Seller.
10. توضيح تقسيمه إلى Sub-orders.
11. بدء Stripe Checkout.
12. توضيح أن webhook، وليس success URL، هي التي تؤكد الدفع.
13. عرض الاختبارات في النهاية.

## 19. ملخص للحفظ

الفكرة الأساسية للمشروع:

> المنتج قابل للتغيير، لكن الطلب والدفع يمثلان تاريخًا ماليًا يجب أن يظل ثابتًا. لذلك الـcatalog مرن باستخدام attributes وvariants وoptions، بينما الطلبات تستخدم snapshots، والدفع يعتمد على server-side calculations وverified webhooks وidempotency.

أهم أربع نقاط للتركيز عليها أثناء العرض:

1. الفرق بين Product Attribute وVariant Option.
2. سبب تقسيم Order إلى Sub-orders.
3. أهمية snapshots وtransactions وrow locking.
4. تأمين Stripe باستخدام signature verification وidempotency وعدم الثقة في return URL.

## 20. حالة الاختبارات

المشروع يحتوي على Feature وUnit tests تغطي الـcatalog والطلبات والدفع وStripe. آخر Full Suite مسجل قبل آخر تعديل للبحث كان:

```text
123 tests / 651 assertions
```

واختبارات البحث المستهدفة بعد تعديل البحث كانت:

```text
14 tests / 105 assertions
```

عند عرض المشروع فعليًا، يفضل تشغيل الاختبارات مرة أخرى وذكر نتيجة التشغيل الحالية فقط.

### راجع نفسك بعد Phase 5

- اختار ثلاثة تحديات واحكي كل تحدي بالشكل ده: المشكلة، الاختيارات، الحل، والـtrade-off.
- جاوب الأسئلة المتوقعة بصوتك بدل حفظ الإجابات حرفيًا.
- نفذ ترتيب الـdemo مرة كاملة قبل المقابلة.
- راجع النسخة المختصرة قبل المقابلة مباشرة.
