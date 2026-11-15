# دليل تطبيق Cartiva

هذا الملف هو المرجع الكامل للتطبيق الحالي: الهدف، المعمارية، نموذج البيانات، أهم الـ workflows، التشغيل والاختبار. تفاصيل الدفع موجودة في [payments.md](payments.md).

## 1. نظرة عامة

Cartiva منصة E-commerce متعددة البائعين مبنية بـ Laravel. يستطيع العميل تصفح منتجات عربية حقيقية، البحث والفلترة، اختيار نسخة المنتج، استخدام كوبون، إنشاء طلب واحد من عدة بائعين، ثم الدفع إلكترونيًا أو عند الاستلام. يدير كل بائع منتجاته وكوبوناته وطلباته، ويدير الـ Admin الطلبات والتصنيفات وأدوار المستخدمين.

### التقنيات

- PHP 8.1+ وLaravel 10.
- MySQL وEloquent ORM.
- Laravel Sanctum للـ API tokens.
- Blade وBootstrap 5 RTL وVite.
- Stripe PHP SDK للدفع وConnect.
- PHPUnit لاختبارات Feature وUnit.
- Docker Compose متاح لتشغيل التطبيق وقاعدة البيانات.

## 2. الأدوار والصلاحيات

الأدوار معرفة في `RoleName` ومربوطة بالمستخدمين عبر `role_user`:

| الدور | المسؤوليات الأساسية |
| --- | --- |
| `admin` | إدارة الطلبات والتصنيفات وتعديل أدوار المستخدمين |
| `seller` | المنتجات، النسخ والمخزون، الكوبونات، الطلبات الفرعية، Stripe onboarding |
| `user` | العناوين، السلة، الشراء، الطلبات والتقييمات |

التحقق من الـ role والملكية يتم على السيرفر. مجرد إخفاء زر في الواجهة ليس Authorization.

## 3. تنظيم الكود

```text
app/Http/Controllers/     HTTP وتهيئة responses/views
app/Http/Requests/        Validation وauthorization للطلبات
app/Models/               العلاقات، casts وسلوك الموديل المتماسك
app/Policies/             صلاحيات الموارد
app/Services/             workflows مثل الطلب والدفع والتقييم
app/Services/Payments/    Stripe checkout/transfers/refunds والحسابات المالية
database/migrations/      schema والقيود والفهارس
database/seeders/         بيانات العرض والاختبار
resources/views/          Blade web UI
routes/                   Web وAPI وwebhooks وconsole
tests/                    Feature وUnit tests
```

الـ Controllers تبقى مركزة على HTTP، بينما العمليات المركبة مثل إنشاء الطلب تتم داخل Service وDB transaction.

## 4. خريطة البيانات

### المستخدمون

- `users`: بيانات الحساب.
- `roles`: الأدوار المتاحة.
- `role_user`: الربط Many-to-Many بين المستخدم والدور.
- `addresses`: عناوين العميل، مع عنوان افتراضي اختياري.

### كتالوج المنتجات

- `products`: المنتج الأساسي، البائع، السعر، الخصم، الصورة، النشاط، المبيعات والتقييم المجمع.
- `product_variants`: النسخة القابلة للبيع والمخزون وSKU و`option_signature`.
- `product_variant_options`: اختيارات النسخة مثل اللون والسعة والمقاس.
- `product_attributes`: مواصفات وصفية مثل الخامة أو العلامة أو القدرة.
- `product_pictures`: Gallery المنتج.
- `categories` و`sub_category`: التصنيفات الرئيسية والفرعية.
- `product_sub_category`: ربط المنتج بأكثر من تصنيف فرعي.

المخزون في `product_variants` فقط. الـ attributes تصف المنتج، أما variant options فتحدد النسخة التي يشتريها العميل.

### السلة والكوبونات

- `carts`: سلة المستخدم.
- `cart_items`: المنتج والـ variant والكمية، مع Unique يمنع تكرار النسخة كسطرين.
- `coupons`: كوبون مملوك لبائع وله نسبة وصلاحية وحد استخدام.
- `cart_coupons`: الكوبونات المطبقة على السلة.

كوبون البائع يخص منتجات هذا البائع فقط، وليس إجمالي منتجات باقي البائعين.

### الطلبات

- `orders`: الطلب الرئيسي الخاص بالعميل وإجمالياته وحالة الدفع.
- `sub_orders`: جزء مستقل لكل بائع داخل الطلب.
- `order_items`: Snapshot للمنتج والنسخة والسعر وقت الشراء.
- `order_item_pictures`: Snapshot لصور العنصر.
- `order_coupon_allocations`: يربط خصم البائع بطلبه الفرعي ويسجل تحريره عند الإلغاء.

الـ snapshots تحافظ على صحة الطلب القديم حتى إذا عدل البائع المنتج أو حذفه لاحقًا.

### الدفع

- `seller_stripe_accounts`: حساب Stripe Connect واحد لكل بائع.
- `stripe_webhook_events`: منع معالجة webhook event مرتين.
- `payments`: محاولات الدفع للطلب.
- `seller_transfers`: حصة وتحويل كل طلب فرعي للبائع.
- `refunds`: الاسترداد الجزئي للطلب الفرعي الملغى.

### التقييمات

- `reviews`: تقييم واحد من المستخدم لكل منتج، من 1 إلى 5، مع تعليق اختياري.

التطبيق يسمح بالتقييم فقط بعد شراء المنتج ضمن طلب مكتمل. حذف المستخدم يحتفظ بالتقييم ويجعل `user_id` فارغًا.

### جداول Laravel

- `personal_access_tokens` لـ Sanctum.
- `password_reset_tokens` لإعادة كلمة المرور.
- `failed_jobs` للـ queue failures.

## 5. كتالوج المنتجات المرن

المنتج لا يفترض مقاسًا أو لونًا ثابتًا. كل Variant تملك مجموعة options ديناميكية، مثال:

```text
هاتف
├── storage=128 GB, color=أسود
└── storage=256 GB, color=أزرق
```

`option_signature` تمثيل موحد للاختيارات، ويمنع تكرار نفس التركيبة داخل المنتج. السعر الفعلي يمكن أن يأتي من variant price override عند وجوده، وإلا يستخدم سعر المنتج، ثم يطبق خصم المنتج.

## 6. البحث والفلترة

واجهة المتجر والـ API تدعمان البحث في المنتج والتصنيف والخصائص والاختيارات، مع فلاتر السعر والمخزون والبائع والتقييم والعروض والترتيب.

أهم parameters في `GET /api/products`:

| Parameter | الاستخدام |
| --- | --- |
| `search` | بحث نصي |
| `category_ids[]` | عدة تصنيفات رئيسية |
| `sub_category_ids[]` | عدة تصنيفات فرعية |
| `seller_ids[]` | بائع أو أكثر |
| `min_price`, `max_price` | نطاق السعر الفعلي |
| `in_stock` | النسخ المتوفرة فقط |
| `attributes[key][]` | قيم attribute محددة |
| `attribute_ranges[key][min/max/unit]` | نطاق رقمي لخاصية |
| `options[key][]` | اختيارات variant |
| `sort` | `latest`, `price_asc`, `price_desc` |
| `per_page` | من 1 إلى 100، والافتراضي 10 |

مجموعات الفلاتر المختلفة تعمل بـ AND، والقيم المتعددة داخل المفتاح نفسه تعمل بـ OR. شروط الاختيارات والسعر والمخزون يجب أن تنطبق على **نفس Variant** حتى لا يخلط النظام لون نسخة مع مخزون نسخة أخرى.

الـ Web pagination تستخدم Bootstrap 5 وتحفظ query string عند التنقل بين الصفحات.

## 7. دورة السلة وإنشاء الطلب

### السلة

1. يختار العميل Variant محددة وكمية.
2. يتحقق السيرفر من ملكية السلة وصحة المنتج والنسخة.
3. يمكن إضافة كوبون بائع صالح.
4. `validateCart` يعيد التحقق قبل Checkout.

### إنشاء الطلب

ينفذ `OrderService` العملية داخل DB transaction:

1. يقفل السلة والـ variants باستخدام `lockForUpdate`.
2. يتحقق أن المنتجات نشطة والمخزون يكفي.
3. يجمع العناصر حسب `seller_id`.
4. يحسب subtotal وخصم كل بائع على السيرفر.
5. ينشئ `Order` ثم `SubOrder` لكل بائع.
6. يحفظ snapshots للعناصر والصور والاختيارات والأسعار.
7. ينقص مخزون كل Variant ويزيد `sold_count`.
8. يزيد استخدام الكوبونات ثم يفرغ السلة.

استخدام transaction وrow locks يمنع طلبين متزامنين من بيع آخر قطعة بنجاح في الوقت نفسه.

## 8. دورة الطلب

الحالات العامة للطلب: `pending`, `processing`, `shipped`, `completed`, `cancelled`.

كل بائع يتعامل مع `SubOrder` الخاصة به فقط. حالة `Order` تجمع حالات الأجزاء:

- كل الأجزاء ملغاة → الطلب ملغى.
- كل الأجزاء مكتملة أو ملغاة → الطلب مكتمل.
- وجود جزء مشحون → الطلب مشحون.
- غير ذلك → قيد التنفيذ.

الإلغاء قبل الشحن يعيد المخزون، يصحح عداد المبيعات، يحرر الكوبون ويعيد حساب إجماليات الطلب. تفاصيل الدفع والتسويات في [payments.md](payments.md).

## 9. التقييمات

- العميل الذي اشترى منتجًا في طلب مكتمل يستطيع إضافة تقييم واحد.
- `rating` بين 1 و5، والتعليق اختياري ومحدود الطول.
- صاحب التقييم فقط يستطيع تعديله أو حذفه.
- بعد الإنشاء أو التعديل أو الحذف يعاد حساب `rating_average` و`rating_count` للمنتج.
- قوائم التقييمات paginated، وقائمة المستخدم الشخصية تتطلب Authentication.

## 10. واجهات التطبيق

يوجد 121 route حاليًا موزعة على:

- Storefront: الصفحة الرئيسية، المتاجر، البحث وصفحة المنتج.
- Auth: التسجيل والدخول والخروج.
- Customer: العناوين، السلة، الطلبات والدفع والتقييمات.
- Seller: المنتجات والكوبونات والطلبات وحاسبة الرسوم وStripe Connect.
- Admin: الطلبات والتصنيفات وأدوار المستخدمين.
- API: Auth، المنتجات، التصنيفات، العناوين، السلة، الكوبونات والطلبات.
- Webhooks: Checkout وConnect.

لعرض القائمة الفعلية دائمًا:

```bash
php artisan route:list --except-vendor
```

## 11. الأمان وسلامة البيانات

- Form Requests وvalidation لكل مدخل غير موثوق.
- Policies وownership checks للموارد المحمية.
- الأسعار والخصومات والإجماليات تحسب على السيرفر.
- DB transactions للعمليات التي تغير عدة جداول.
- `lockForUpdate` لحماية المخزون والكوبونات والحالات الحساسة.
- Foreign keys وUnique constraints لمنع البيانات المتعارضة.
- Passwords مخزنة كـ hashes.
- Stripe webhook signatures وevent idempotency.
- عدم حفظ secrets في Git؛ تقرأ من `.env` عن طريق config.

## 12. بيانات الـ Seeder

الأمر التالي يعيد بناء قاعدة البيانات ويضيف بيانات العرض:

```bash
php artisan migrate:fresh --seed
```

البيانات الحالية تشمل:

- 50 مستخدمًا: Admin واحد، 10 بائعين و39 عملاء.
- 100 منتج عربي حقيقي موزعة على البائعين.
- Variants وخصائص وتصنيفات وGallery لكل منتج.
- صور WebP محلية للمنتجات بدون الاعتماد على network وقت الـ seed.
- عناوين وكوبونات وسلال وعناصر سلة.
- 15 طلبًا متعددة البائعين، ومدفوعات وشحنات بحالات متنوعة.
- تقييمات مرتبطة بمشتريات مكتملة.

كل حسابات الـ seed تستخدم كلمة المرور:

```text
123456
```

حسابات سريعة:

```text
Admin:  admin@gmail.com
Buyer:  buyer1@gmail.com
Buyer:  buyer2@gmail.com
```

باقي حسابات البائعين والعملاء معرفة في `database/seeders/TestData/UserSeeder.php`.

## 13. التشغيل

### باستخدام Docker

```bash
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
```

### تشغيل Frontend أثناء التطوير

```bash
npm install
npm run dev
```

لبناء assets:

```bash
npm run build
```

### الخدمات الخلفية

في بيئة حقيقية يجب تشغيل queue worker والـ scheduler باستمرار. الـ scheduler يعيد محاولة Stripe transfers وrefunds كل دقيقة دون تداخل.

## 14. الاختبارات والجودة

```bash
php artisan test
./vendor/bin/pint --test
git diff --check
```

يغطي الاختبار التسجيل والصلاحيات والمنتجات الديناميكية والبحث والسلة والكوبونات والطلبات متعددة البائعين والمخزون وStripe webhooks والتحويلات والاستردادات وCOD والتقييمات والـ pagination.

آخر تشغيل كامل أثناء إعداد هذا الملف: 147 اختبارًا ناجحًا و1293 assertion، مع تحذير deprecation من PHP 8.5 للثابت `PDO::MYSQL_ATTR_SSL_CA` في إعداد MySQL.

## 15. قرارات التصميم المهمة

1. `Order` واحدة للعميل و`SubOrder` لكل بائع.
2. المخزون على مستوى Variant وليس Product.
3. الطلب يحفظ snapshots بدل الاعتماد على بيانات منتج قابلة للتعديل.
4. الكوبون يوزع على جزء البائع المالك له.
5. Stripe تستخدم Separate Charges and Transfers.
6. تحويل البائع مؤجل حتى اكتمال طلبه الفرعي.
7. طلب COD يصبح مدفوعًا عند اكتمال كل الأجزاء غير الملغاة.
8. المبالغ المتجهة إلى Stripe تحفظ بوحدة العملة الصغرى.
9. Webhooks وعمليات المال قابلة لإعادة المحاولة بأمان.
10. البحث في variant options يضمن تطابق كل الشروط مع نفس النسخة.

## 16. تحسينات مطلوبة قبل الإنتاج

- Monitoring وتنبيهات للمدفوعات والتحويلات والاستردادات.
- سياسة disputes وchargebacks وtransfer reversals.
- اختبارات browser/E2E لأهم رحلات العميل والبائع.
- تخزين الصور على object storage وCDN.
- معالجة تحذير PHP 8.5 الخاص بإعداد MySQL عند اعتماد PHP 8.5 رسميًا.
- مراجعة rate limiting وbackup/restore وsecret rotation وخطة النشر.
