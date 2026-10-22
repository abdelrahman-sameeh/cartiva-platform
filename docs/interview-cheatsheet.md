# Cativa — مراجعة سريعة قبل الـInterview

الملف ده النسخة المختصرة من [دليل المشروع الكامل](./interview-guide.md). استخدمه بعد ما تفهم الـPhases من الملف الكبير، مش بدلها.

## Phase 0 — أقدم المشروع إزاي؟

### تقديم في 30 ثانية

> Cativa is a multi-vendor e-commerce platform built with Laravel. It has a flexible product catalog, multi-seller orders, stock protection, Stripe Checkout for customer payments, and Stripe Connect for seller onboarding. I focused on data integrity, secure webhooks, idempotency, and keeping the code organized using Form Requests, Services, and Gateways.

بالمصري:

> Cativa عبارة عن Marketplace متعدد البائعين. البائع يقدر يضيف أي نوع منتج بخصائص وVariants مرنة، والعميل يقدر يشتري من أكتر من بائع في Order واحدة. النظام بيقسمها لـSub-orders، وبيحمي المخزون والدفع باستخدام transactions وlocking وStripe webhooks.

### تقسيم الكود

```text
Route/Middleware
    ↓
Form Request: validation + authorization
    ↓
Controller: HTTP فقط
    ↓
Service: business workflow
    ↓
Model/Database

Stripe Service → Stripe Gateway → Stripe API
```

ليه Service؟ عشان الـworkflow المعقدة متبقاش جوه Controller، وتبقى أسهل في الاختبار. لكن مش كل function صغيرة محتاجة Service.

---

## Phase 1 — خريطة الداتا بيز

```text
users <── role_user ──> roles

seller ──< products
product ──< variants ──< variant_options
product ──< attributes
product >──< sub_categories ──> categories

buyer ──1 cart ──< cart_items
buyer ──< orders ──< sub_orders ──< order_items
order ──< payments

seller ──1 seller_stripe_account
```

أهم العلاقات:

- User وRole: Many-to-Many، عشان نفس الشخص ينفع Buyer وSeller.
- Seller وProduct: One-to-Many.
- Product وVariant: One-to-Many.
- Variant وOption: One-to-Many.
- Product وSubcategory: Many-to-Many.
- Order وSub-order: One-to-Many.
- Seller وStripe account: One-to-One.

مصطلحات لازم تبقى فاهمها:

- **Primary key:** بيعرف الـrow داخليًا.
- **Foreign key:** بيربط table بجدول تاني ويحافظ على referential integrity.
- **Unique constraint:** بيمنع تكرار business rule مهم.
- **Index:** بيسرع القراءة والبحث، لكنه بيزود تكلفة الكتابة والمساحة.
- **Pivot table:** بيحقق علاقة Many-to-Many.

---

## Phase 2 — المنتجات المرنة

### الفرق اللي لازم تحفظ معناه

```text
Product = المنتج الأساسي
Variant = النسخة اللي ليها سعر وstock
Attribute = معلومة بتوصف المنتج كله
Option = اختيار بيحدد الـvariant
SKU = كود تشغيلي للـvariant
```

مثال موبايل:

```text
Product: iPhone 15 Pro

Attributes:
- processor = A17 Pro
- screen = OLED

Variant 1:
- color = Black
- storage = 128 GB
- stock = 5
- sku = IP15-BLK-128

Variant 2:
- color = Blue
- storage = 256 GB
- stock = 3
```

نفس التصميم ينفع هدوم:

```text
Attributes: material = Cotton
Variant options: color = Black, size = XL
```

### `option_signature`

بيمنع تكرار نفس تركيبة الـoptions لو ترتيبها اختلف. النظام بيعمل normalize، يرتب القيم، وبعدها يعمل SHA-256 hash. فيه unique constraint على المنتج والـsignature.

### ليه مش JSON؟

الـattributes والـoptions بنفلتر ونبحث فيهم، فعشان كده normalized tables أفضل للـindexes والـconstraints والاستعلامات. JSON مناسب أكتر للـsnapshots أو data مش محتاجة filtering مستمر.

### سعر الـvariant

```php
$variant->price ?? $product->price
```

لو الـvariant ملهاش سعر خاص، بنستخدم سعر المنتج الأساسي بدل تكراره.

---

## Phase 3 — Cart وOrders وStock

### Cart

Cart item لازم تشير للـvariant المحددة، مش للمنتج بس، لأن الـstock والسعر والاختيارات ممكن تختلف.

الـunique constraint على:

```text
cart_id + product_variant_id
```

بيخلي إضافة نفس الـvariant تزود quantity بدل row جديدة.

### Multi-vendor Order

```text
Order واحدة للعميل
  ├── Sub-order للبائع A
  └── Sub-order للبائع B
```

ليه Sub-orders؟ عشان كل Seller يشوف منتجاته وحسابه ويحدث حالته بشكل مستقل، والعميل يفضل شايف Order واحدة.

### Order snapshots

`order_items` بتحفظ الاسم والصورة والسعر والـSKU والـoptions وقت الشراء. لو Seller غير المنتج أو حذفه، الطلب القديم يفضل صحيح.

الجملة المهمة:

> Catalog data is mutable, but order and financial history must remain immutable.

### منع Overselling

```text
DB transaction
    ↓
lockForUpdate للـvariants
    ↓
إعادة فحص stock والسعر على السيرفر
    ↓
إنشاء order وsub-orders وitems
    ↓
خصم stock وتنظيف cart
    ↓
commit
```

الفحص قبل الـtransaction مش كفاية لأن مستخدم تاني ممكن يشتري في نفس اللحظة.

---

## Phase 4 — Payments وStripe

### ليه Payment table منفصلة؟

Order واحدة ممكن يحصل عليها كذا payment attempt: واحدة expired، واحدة failed، وواحدة successful.

### `amount_minor`

```text
100.50 EGP = 10050 minor units
```

بنستخدم integer بدل float عشان الدقة المالية.

### Idempotency

لو العميل ضغط مرتين أو حصل network retry، نفس العملية متتعملش مرتين. فيه `idempotency_key` لكل attempt، وStripe events نفسها بيتم deduplicate ليها.

### Checkout vs Connect

- **Stripe Checkout:** دفع العميل للـOrder.
- **Stripe Connect:** تسجيل وربط Seller بالمنصة وتجهيز حسابه للاستلام.

### Webhook security

النظام بيتأكد من:

- Stripe signature.
- نوع الـevent.
- إن الـevent متعالجتش قبل كده.
- Payment وOrder IDs والـmetadata.
- Amount وcurrency.
- إن event متأخرة متنزلش حالة Payment ناجحة.

مهم جدًا:

> Success URL مش دليل دفع؛ الـverified webhook هي مصدر الحقيقة.

الحالة الحالية بصدق: Connect onboarding ومزامنة حالة الحساب موجودين، لكن توزيع وتحويل أرباح كل Seller فعليًا لسه settlement phase منفصلة.

---

## Phase 5 — Search والتحديات

### البحث

البحث بيدعم text وcategory وseller والسعر والـstock والـattributes والـvariant options والـnumeric ranges.

لو المطلوب:

```text
Black + 256 GB + in stock
```

لازم الشروط كلها تتحقق في نفس الـvariant. مينفعش اللون من variant والمساحة من واحدة تانية. عشان كده شروط الـvariant بتكون جوه نفس `whereHas`.

### أهم تحديات تقولها

1. حولت schema من ملابس بس لـgeneric catalog من غير فقد الداتا القديمة.
2. قسمت Multi-seller order لـOrder وSub-orders.
3. استخدمت snapshots عشان الطلبات القديمة متتغيرش.
4. منعت overselling بـtransaction وrow locking.
5. أمنت Stripe بـsignatures وidempotency والتحقق من المبلغ والعملة.
6. خليت فلترة الاختيارات تطابق نفس الـvariant.

احكي أي تحدي بالترتيب ده:

```text
Problem → Options considered → Chosen solution → Trade-off
```

### تحسينات مستقبلية

- Unique constraint على `carts.user_id`.
- استخدام decimal بدل float في coupon percentage.
- Stock reservation لها expiry وإرجاع المخزون عند الإلغاء.
- إكمال seller transfers والـsettlement.
- Attribute definitions لكل Category.
- إزالة legacy `size` و`color` بعد التأكد من الـmigration.
- Seller-scoped unique SKU لو بقت business requirement.
- Meilisearch أو full-text search لما حجم البيانات يحتاج.
- Review system كامل.

---

## أسئلة سريعة وإجاباتها

### ليه Laravel؟

عشان بيوفر ecosystem متكامل للـauth والـvalidation والـORM والـmigrations والـtransactions والـtesting، ومناسب لتطوير Marketplace منظم بسرعة.

### Authentication vs Authorization؟

Authentication يعني مين المستخدم، وAuthorization يعني مسموح له يعمل العملية دي ولا لأ.

### ليه متاخدش السعر من Frontend؟

لأن الـrequest ممكن تتعدل. السعر والخصومات والإجمالي بيتحسبوا من الداتا بيز على السيرفر.

### ليه Transaction؟

عشان إنشاء الطلب بيعدل كذا table؛ يا إما كلهم ينجحوا يا إما كلهم يعملوا rollback.

### `cascadeOnDelete` vs `nullOnDelete`؟

- Cascade: يحذف السجلات التابعة.
- Null: يحافظ على السجل التاريخي ويصفر الـforeign key.
- Restrict: يمنع الحذف لو لسه فيه علاقة حساسة.

### إيه N+1؟

إنك تعمل query للقائمة وبعدها query لكل item. الحل eager loading باستخدام `with()`.

### اختبرت Stripe إزاي؟

بـmocks/fakes من غير network calls حقيقية، واختبارات success وfailure وduplicate event وinvalid signature وamount mismatch والـauthorization.

---

## ترتيب الـDemo

1. Seller يسجل ويفتح onboarding.
2. يعمل منتج عام بـattributes وvariants.
3. تعرض search وfilters.
4. Buyer يضيف exact variant للـCart.
5. يطبق Seller coupon.
6. يعمل Order من أكتر من Seller.
7. تعرض تقسيمها لـSub-orders والـsnapshots.
8. تبدأ Stripe Checkout.
9. تشرح إن webhook هي اللي بتأكد الدفع.
10. تعرض الاختبارات.

## آخر 8 جمل تراجعهم

1. Cativa Multi-vendor Marketplace مش متجر Seller واحد.
2. Product Attribute بتوصف المنتج، وVariant Option بتحدد النسخة اللي هتتباع.
3. الـvariant هي اللي عندها stock وSKU وسعر اختياري.
4. Order واحدة بتتقسم Sub-order لكل Seller.
5. Order items snapshots عشان تاريخ الشراء ميتغيرش.
6. Transaction و`lockForUpdate` بيمنعوا overselling.
7. الـserver هو اللي بيحسب السعر، والـwebhook هي مصدر حقيقة الدفع.
8. Connect onboarding جاهز، لكن seller settlement النهائي لسه خطوة مستقلة.

## حالة الاختبارات المسجلة

```text
Full suite قبل آخر تعديل بحث: 123 tests / 651 assertions
Targeted search tests بعد التعديل: 14 tests / 105 assertions
```

قبل ما تعرض المشروع شغل الاختبارات من جديد، ومتذكرش غير النتيجة اللي شفتها فعلًا.
