# Cartiva — ملخص التطبيق

هذا ملخص سريع لـ [دليل التطبيق الكامل](application.md). تفاصيل الدفع موجودة في [payments.md](payments.md).

## تعريف سريع

Cartiva منصة Marketplace متعددة البائعين مبنية بـ Laravel 10 وMySQL وBlade وBootstrap RTL. العميل يشتري من عدة بائعين في طلب واحد، والنظام يفصل الطلب والتسوية لكل بائع.

## الأدوار

- `admin`: الطلبات والتصنيفات وأدوار المستخدمين.
- `seller`: المنتجات والـ variants والمخزون والكوبونات والطلبات وStripe Connect.
- `user`: العناوين والسلة والشراء والطلبات والتقييمات.

## الفكرة الأساسية للداتا

```text
User ──► Cart ──► CartItems
Seller ──► Products ──► Variants ──► VariantOptions
Order ──► SubOrder per seller ──► OrderItems snapshots
Order ──► Payment ──► SellerTransfers / Refunds
Product ──► Reviews
```

- المخزون على `product_variants`.
- `product_attributes` مواصفات، و`product_variant_options` اختيارات النسخة المباعة.
- `order_items` تحفظ Snapshot للسعر والاسم والصور والاختيارات.
- كل بائع له `SubOrder` وتسوية مستقلة.

## رحلة الشراء

1. العميل يختار Variant ويضيفها للسلة.
2. السيرفر يتحقق من المخزون والكوبونات والأسعار.
3. داخل DB transaction يقفل المخزون، ويقسم العناصر حسب البائع.
4. ينشئ `Order` و`SubOrder` لكل بائع ويحفظ snapshots.
5. ينقص المخزون ويفرغ السلة.
6. العميل يدفع عبر Stripe أو يختار COD.

`lockForUpdate` يمنع overselling، والإجماليات تحسب على السيرفر فقط.

## الدفع باختصار

### Stripe

النموذج هو **Separate Charges and Transfers**:

- Checkout واحد يدفعه العميل للمنصة.
- Webhook موقع يؤكد الدفع.
- `SellerTransfer` مستقلة لكل `SubOrder`.
- لا يحول مستحق البائع إلا بعد اكتمال طلبه الفرعي.
- الإلغاء قبل التحويل ينشئ Refund جزئيًا للجزء الملغى.

### COD

- يدير البائع أو المدير حالة الطلب الفرعي من الواجهة.
- يصبح الطلب مدفوعًا عند اكتمال كل أجزائه غير الملغاة.
- آلية التحصيل والتسوية التشغيلية خارج نطاق التطبيق حاليًا.

## البحث والتقييمات

- البحث يدعم النص والتصنيف والبائع والسعر والمخزون والخصائص والـ variant options والترتيب.
- شروط option والمخزون والسعر تطبق على نفس Variant.
- لا يقيم المنتج إلا عميل اشتراه ضمن طلب مكتمل.
- تقييم واحد لكل مستخدم ومنتج، مع إعادة حساب متوسط المنتج.

## الأمان

- Validation وPolicies وownership checks.
- Transactions وrow locks وDB constraints.
- أسعار وحالات محسوبة ومتحقق منها على السيرفر.
- Stripe signatures وidempotency للـ webhooks والتحويلات والاستردادات.
- الأسرار في `.env` وليست داخل الكود.

## بيانات التجربة

```bash
php artisan migrate:fresh --seed
```

تنشئ 50 مستخدمًا، 100 منتج بصور محلية وGallery، بالإضافة إلى variants وتصنيفات وسلال و15 طلبًا ومدفوعات وشحنات وتقييمات. كلمة مرور كل حسابات الـ seed هي `123456`.

```text
Admin: admin@gmail.com
Buyer: buyer1@gmail.com
```

## التشغيل والاختبار

```bash
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed

php artisan test
./vendor/bin/pint --test
```

آخر تحقق كامل: 148 اختبارًا ناجحًا و1297 assertion.

## أهم جمل للشرح

1. استخدمت Order رئيسية وSubOrder لكل بائع لأن التنفيذ والتسوية مستقلان.
2. حفظت snapshots حتى لا تتغير الفاتورة القديمة عند تعديل المنتج.
3. وضعت المخزون على Variant واستخدمت row locking لمنع overselling.
4. استخدمت Separate Charges and Transfers لأن Checkout واحدة قد تضم عدة بائعين.
5. أخرت تحويل البائع حتى اكتمال الجزء الخاص به لتقليل مخاطر الاسترداد.
6. في COD يعتبر الطلب مدفوعًا عند اكتمال كل أجزائه غير الملغاة.
7. كل عملية مالية قابلة للتكرار بأمان باستخدام idempotency وUnique constraints.
