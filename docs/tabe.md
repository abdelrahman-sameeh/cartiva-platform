# قاموس جداول قاعدة بيانات Cartiva

يوثّق هذا الملف الـschema الفعلي الناتج عن migrations المشروع. يشمل جداول التطبيق وجداول Laravel الداخلية، ويوضح وظيفة كل جدول وكل عمود فيه.

## ملاحظات عامة

- الأعمدة المنتهية بـ`_id` هي مفاتيح تربط السجل بجدول آخر، ما لم يذكر غير ذلك.
- `created_at` و`updated_at` يسجلان وقت إنشاء السجل وآخر تعديل عليه.
- المبالغ المنتهية بـ`_minor` تحفظ بأصغر وحدة للعملة؛ مثال: `125.50 EGP` تحفظ `12550` قرشًا.
- `created_at` و`updated_at` يقبلان `NULL` لأن Laravel ينشئ أعمدة `timestamps()` بهذه الصورة في MySQL.
- أنواع `JSON` موثقة بنوعها المنطقي حتى إذا عرضها MySQL داخليًا كـ`LONGTEXT` مع قيد JSON.

## المستخدمون والصلاحيات

### `users`

حسابات جميع مستخدمي المنصة، سواء كانوا عملاء أو بائعين أو مديرين.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للمستخدم. |
| `first_name` | `VARCHAR(255)` | لا | الاسم الأول. |
| `last_name` | `VARCHAR(255)` | لا | اسم العائلة. |
| `email` | `VARCHAR(255)` | لا | البريد المستخدم للدخول، وقيمته فريدة. |
| `phone` | `VARCHAR(15)` | نعم | رقم الهاتف الاختياري للمستخدم. |
| `email_verified_at` | `TIMESTAMP` | نعم | وقت تأكيد البريد الإلكتروني. |
| `password` | `VARCHAR(255)` | لا | كلمة المرور بعد تشفيرها. |
| `remember_token` | `VARCHAR(100)` | نعم | رمز جلسة «تذكرني» الخاص بـLaravel. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء الحساب. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للحساب. |

### `roles`

تعريف الأدوار المتاحة مثل العميل والبائع والمدير ومندوب التوصيل.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للدور. |
| `name` | `VARCHAR(30)` | لا | الاسم البرمجي الفريد للدور مثل `seller`. |
| `label` | `VARCHAR(50)` | لا | الاسم المعروض للمستخدم بالعربية. |

### `role_user`

جدول وسيط يربط المستخدمين بالأدوار، ويسمح للمستخدم بامتلاك أكثر من دور.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `role_id` | `BIGINT UNSIGNED` | لا | الدور المرتبط من جدول `roles`. |
| `user_id` | `BIGINT UNSIGNED` | لا | المستخدم المرتبط من جدول `users`. |

المفتاح الأساسي مركب من `role_id` و`user_id` لمنع تكرار الدور للمستخدم نفسه.

### `password_reset_tokens`

رموز استعادة كلمات المرور التي يديرها Laravel.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `email` | `VARCHAR(255)` | لا | بريد الحساب المطلوب تغيير كلمة مروره، وهو المفتاح الأساسي. |
| `token` | `VARCHAR(255)` | لا | رمز استعادة كلمة المرور المشفر. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء الرمز لحساب مدة صلاحيته. |

### `personal_access_tokens`

رموز Laravel Sanctum المستخدمة للمصادقة على الـAPI.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للرمز. |
| `tokenable_type` | `VARCHAR(255)` | لا | نوع الموديل مالك الرمز ضمن العلاقة polymorphic. |
| `tokenable_id` | `BIGINT UNSIGNED` | لا | معرّف الموديل مالك الرمز. |
| `name` | `VARCHAR(255)` | لا | اسم وصفي للرمز أو الجهاز. |
| `token` | `VARCHAR(64)` | لا | قيمة الرمز المشفرة والفريدة. |
| `abilities` | `TEXT` | نعم | الصلاحيات المسموح بها للرمز. |
| `last_used_at` | `TIMESTAMP` | نعم | آخر وقت استُخدم فيه الرمز. |
| `expires_at` | `TIMESTAMP` | نعم | وقت انتهاء صلاحية الرمز إن وجد. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء الرمز. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للرمز. |

## الكتالوج والمنتجات

### `products`

البيانات الأساسية للمنتجات التي يملكها البائعون.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للمنتج. |
| `title` | `VARCHAR(255)` | لا | اسم المنتج المعروض. |
| `slug` | `VARCHAR(255)` | لا | الجزء المقروء من رابط المنتج، وفريد داخل متجر البائع. |
| `description` | `TEXT` | لا | الوصف التفصيلي للمنتج. |
| `cover_image` | `VARCHAR(255)` | نعم | مسار صورة الغلاف الرئيسية. |
| `price` | `DECIMAL(10,2)` | لا | السعر الأساسي للمنتج. |
| `discount_amount` | `DECIMAL(10,2)` | لا | قيمة الخصم المباشر على السعر. |
| `seller_id` | `BIGINT UNSIGNED` | لا | البائع مالك المنتج من جدول `users`. |
| `is_active` | `BOOLEAN` | لا | يحدد هل المنتج ظاهر ومتاح للبيع. |
| `sold_count` | `INT` | لا | إجمالي عدد الوحدات المباعة. |
| `rating_avg` | `DECIMAL(2,1)` | لا | متوسط تقييم المنتج المحسوب. |
| `rating_count` | `INT` | لا | عدد التقييمات الداخلة في المتوسط. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء المنتج. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للمنتج. |

### `product_variants`

النسخ القابلة للبيع من المنتج؛ المخزون والسعر المتغير محفوظان على هذا المستوى.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للنسخة. |
| `product_id` | `BIGINT UNSIGNED` | لا | المنتج الذي تنتمي إليه النسخة. |
| `sku` | `VARCHAR(100)` | نعم | رمز المخزون الخاص بالنسخة. |
| `price` | `DECIMAL(10,2)` | نعم | سعر خاص بالنسخة؛ عند غيابه يستخدم سعر المنتج. |
| `stock` | `INT UNSIGNED` | لا | الكمية المتاحة من هذه النسخة. |
| `option_signature` | `CHAR(64)` | نعم | بصمة ثابتة لتركيبة الاختيارات تمنع تكرار النسخة نفسها. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء النسخة. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للنسخة. |

### `product_variant_options`

اختيارات كل نسخة مثل اللون الأسود والمقاس الكبير.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للاختيار. |
| `product_variant_id` | `BIGINT UNSIGNED` | لا | النسخة التي ينتمي إليها الاختيار. |
| `key` | `VARCHAR(50)` | لا | اسم الاختيار مثل `color` أو `size`. |
| `value` | `VARCHAR(100)` | لا | قيمة الاختيار مثل `black` أو `XL`. |

### `product_attributes`

مواصفات وصفية قابلة للبحث لا تحدد نسخة المخزون، مثل الخامة أو الوزن.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للمواصفة. |
| `product_id` | `BIGINT UNSIGNED` | لا | المنتج صاحب المواصفة. |
| `key` | `VARCHAR(50)` | لا | اسم المواصفة. |
| `value` | `VARCHAR(255)` | لا | القيمة النصية المعروضة والقابلة للفلترة. |
| `numeric_value` | `DECIMAL(15,4)` | نعم | القيمة الرقمية عند دعم فلاتر النطاقات. |
| `unit` | `VARCHAR(30)` | نعم | وحدة القياس المرتبطة بالقيمة الرقمية. |

### `product_pictures`

الصور الإضافية للمنتج.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للصورة. |
| `product_id` | `BIGINT UNSIGNED` | لا | المنتج المرتبطة به الصورة. |
| `picture` | `VARCHAR(255)` | لا | مسار ملف الصورة. |

### `categories`

التصنيفات الرئيسية للمنتجات.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للتصنيف. |
| `title` | `VARCHAR(50)` | لا | اسم التصنيف المعروض. |
| `slug` | `VARCHAR(255)` | لا | الاسم الفريد المستخدم في الروابط والبحث. |

### `sub_category`

التصنيفات الفرعية التابعة لتصنيف رئيسي.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للتصنيف الفرعي. |
| `title` | `VARCHAR(50)` | لا | اسم التصنيف الفرعي. |
| `slug` | `VARCHAR(255)` | لا | الاسم الفريد المستخدم في الروابط والبحث. |
| `category_id` | `BIGINT UNSIGNED` | لا | التصنيف الرئيسي الأب. |

### `product_sub_category`

جدول وسيط يسمح بربط المنتج بأكثر من تصنيف فرعي.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للربط. |
| `product_id` | `BIGINT UNSIGNED` | لا | المنتج المرتبط. |
| `sub_category_id` | `BIGINT UNSIGNED` | لا | التصنيف الفرعي المرتبط. |

### `reviews`

تقييمات العملاء للمنتجات التي اشتروها ضمن طلب مكتمل.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للتقييم. |
| `user_id` | `BIGINT UNSIGNED` | نعم | صاحب التقييم؛ يصبح `NULL` إذا حذف حسابه. |
| `product_id` | `BIGINT UNSIGNED` | لا | المنتج الذي تم تقييمه. |
| `comment` | `VARCHAR(1000)` | نعم | تعليق العميل الاختياري. |
| `rating` | `TINYINT UNSIGNED` | لا | درجة التقييم من 1 إلى 5. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء التقييم. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للتقييم. |

## العناوين والسلة والكوبونات

### `addresses`

عناوين الشحن المحفوظة للمستخدم.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للعنوان. |
| `user_id` | `BIGINT UNSIGNED` | لا | المستخدم مالك العنوان. |
| `country` | `VARCHAR(2)` | لا | رمز الدولة بحرفين. |
| `city` | `VARCHAR(50)` | لا | المدينة. |
| `street` | `VARCHAR(255)` | نعم | الشارع والتفاصيل النصية للعنوان. |
| `is_default` | `BOOLEAN` | لا | يحدد العنوان الافتراضي للمستخدم. |
| `note` | `VARCHAR(255)` | نعم | تعليمات أو ملاحظة إضافية للتوصيل. |
| `latitude` | `DECIMAL(10,7)` | نعم | خط العرض للموقع الجغرافي. |
| `longitude` | `DECIMAL(10,7)` | نعم | خط الطول للموقع الجغرافي. |
| `created_at` | `TIMESTAMP` | نعم | وقت إضافة العنوان. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للعنوان. |

### `carts`

سلة التسوق الخاصة بالمستخدم.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للسلة. |
| `user_id` | `BIGINT UNSIGNED` | لا | المستخدم مالك السلة. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء السلة. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للسلة. |

### `cart_items`

المنتجات والنسخ والكميات الموجودة داخل السلة.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي لعنصر السلة. |
| `cart_id` | `BIGINT UNSIGNED` | لا | السلة التي تحتوي العنصر. |
| `product_id` | `BIGINT UNSIGNED` | لا | المنتج المختار. |
| `product_variant_id` | `BIGINT UNSIGNED` | لا | النسخة المحددة من المنتج. |
| `quantity` | `INT` | لا | الكمية المطلوبة. |
| `created_at` | `TIMESTAMP` | نعم | وقت إضافة العنصر. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للعنصر. |

### `coupons`

كوبونات الخصم التي ينشئها كل بائع لمنتجاته.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للكوبون. |
| `code` | `VARCHAR(50)` | لا | الكود الذي يدخله العميل، وفريد لكل بائع. |
| `percentage` | `DOUBLE(8,2)` | لا | نسبة الخصم المئوية. |
| `expire_date` | `DATE` | لا | آخر تاريخ صالح لاستخدام الكوبون. |
| `max_usage` | `INT` | لا | الحد الأقصى لعدد مرات الاستخدام. |
| `used_count` | `INT` | لا | عدد مرات الاستخدام الفعلي. |
| `seller_id` | `BIGINT UNSIGNED` | لا | البائع مالك الكوبون. |
| `is_active` | `BOOLEAN` | لا | يحدد هل الكوبون مفعل. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء الكوبون. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للكوبون. |

### `cart_coupons`

الكوبونات المطبقة حاليًا على السلة.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للربط. |
| `cart_id` | `BIGINT UNSIGNED` | لا | السلة التي طُبق عليها الكوبون. |
| `coupon_id` | `BIGINT UNSIGNED` | لا | الكوبون المطبق. |
| `created_at` | `TIMESTAMP` | نعم | وقت تطبيق الكوبون. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للربط. |

## الطلبات

### `orders`

الطلب الرئيسي الخاص بالعميل، وقد يحتوي على أكثر من طلب فرعي لبائعين مختلفين.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للطلب. |
| `user_id` | `BIGINT UNSIGNED` | لا | العميل صاحب الطلب. |
| `subtotal` | `DECIMAL(10,2)` | لا | إجمالي العناصر قبل الخصم. |
| `discount` | `DECIMAL(10,2)` | لا | إجمالي الخصومات. |
| `total_price` | `DECIMAL(10,2)` | لا | الإجمالي النهائي المطلوب. |
| `phone` | `VARCHAR(15)` | لا | رقم التواصل المحفوظ وقت الطلب. |
| `address_id` | `BIGINT UNSIGNED` | نعم | عنوان الطلب؛ يصبح `NULL` إذا حذف العنوان المحفوظ. |
| `status` | `ENUM` | لا | حالة الطلب: `pending`, `processing`, `shipped`, `completed`, أو `cancelled`. |
| `payment_status` | `ENUM` | لا | حالة الدفع: `pending`, `paid`, `failed`, أو `refunded`. |
| `payment_method` | `ENUM` | لا | طريقة الدفع: `cash`, `card`, أو `wallet`. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء الطلب. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للطلب. |

### `sub_orders`

جزء مستقل من الطلب الرئيسي لكل بائع.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للطلب الفرعي. |
| `order_id` | `BIGINT UNSIGNED` | لا | الطلب الرئيسي الذي يحتويه. |
| `seller_id` | `BIGINT UNSIGNED` | لا | البائع المسؤول عنه. |
| `subtotal` | `DECIMAL(10,2)` | لا | إجمالي عناصر هذا البائع قبل الخصم. |
| `discount` | `DECIMAL(10,2)` | لا | خصم كوبون البائع على هذا الجزء. |
| `total_price` | `DECIMAL(10,2)` | لا | إجمالي هذا الجزء بعد الخصم. |
| `status` | `ENUM` | لا | حالة الجزء: `pending`, `processing`, `shipped`, `completed`, أو `cancelled`. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء الطلب الفرعي. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للطلب الفرعي. |

### `order_items`

نسخة ثابتة من بيانات المنتج والنسخة وقت الشراء حتى لا تتغير الطلبات القديمة بتعديل المنتج.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي لعنصر الطلب. |
| `sub_order_id` | `BIGINT UNSIGNED` | لا | الطلب الفرعي الذي يحتوي العنصر. |
| `product_id` | `BIGINT UNSIGNED` | نعم | مرجع اختياري للمنتج الحالي، ويصبح `NULL` إذا حذف المنتج. |
| `title` | `VARCHAR(255)` | لا | اسم المنتج وقت الشراء. |
| `slug` | `VARCHAR(255)` | لا | رابط المنتج وقت الشراء. |
| `description` | `TEXT` | لا | وصف المنتج وقت الشراء. |
| `cover_image` | `VARCHAR(300)` | نعم | صورة الغلاف وقت الشراء. |
| `price_at_purchase` | `DECIMAL(10,2)` | لا | سعر الوحدة الفعلي وقت الشراء. |
| `variant_sku` | `VARCHAR(100)` | نعم | رمز النسخة المشتراة. |
| `variant_options` | `JSON` | نعم | اختيارات النسخة المشتراة كـsnapshot. |
| `quantity` | `INT UNSIGNED` | لا | الكمية المشتراة. |
| `created_at_snapshot` | `TIMESTAMP` | نعم | تاريخ إنشاء المنتج الأصلي المحفوظ داخل الـsnapshot. |

### `order_item_pictures`

نسخة ثابتة من صور عنصر الطلب وقت الشراء.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للصورة. |
| `order_item_id` | `BIGINT UNSIGNED` | لا | عنصر الطلب الذي تخصه الصورة. |
| `image_path` | `VARCHAR(300)` | لا | مسار الصورة المحفوظ وقت الشراء. |

### `order_coupon_allocations`

يسجل خصم الكوبون المخصص لكل طلب فرعي، ويتابع تحرير استخدامه عند الإلغاء.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للتخصيص. |
| `order_id` | `BIGINT UNSIGNED` | لا | الطلب الرئيسي. |
| `sub_order_id` | `BIGINT UNSIGNED` | لا | الطلب الفرعي الذي حصل على الخصم، وهو فريد هنا. |
| `coupon_id` | `BIGINT UNSIGNED` | نعم | الكوبون المستخدم؛ يصبح `NULL` إذا حذف الكوبون. |
| `discount` | `DECIMAL(10,2)` | لا | قيمة الخصم المثبتة على الطلب الفرعي. |
| `released_at` | `TIMESTAMP` | نعم | وقت تحرير عداد استخدام الكوبون بعد الإلغاء. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء التخصيص. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للتخصيص. |

## الدفع وStripe

### `payments`

محاولات الدفع الإلكتروني للطلب الرئيسي.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي لمحاولة الدفع. |
| `order_id` | `BIGINT UNSIGNED` | لا | الطلب الذي تُدفع قيمته. |
| `provider` | `VARCHAR(255)` | لا | مزود الدفع، والقيمة الافتراضية `stripe`. |
| `provider_payment_id` | `VARCHAR(255)` | نعم | معرّف عملية الدفع لدى المزود. |
| `checkout_session_id` | `VARCHAR(255)` | نعم | معرّف Stripe Checkout Session. |
| `idempotency_key` | `UUID` | لا | مفتاح فريد يمنع إنشاء العملية مرتين. |
| `amount_minor` | `BIGINT UNSIGNED` | لا | مبلغ الدفع بأصغر وحدة للعملة. |
| `currency` | `CHAR(3)` | لا | رمز العملة المكون من ثلاثة أحرف. |
| `status` | `VARCHAR(255)` | لا | حالة محاولة الدفع مثل `creating`, `pending`, `paid`, أو `failed`. |
| `paid_at` | `TIMESTAMP` | نعم | وقت تأكيد نجاح الدفع. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء محاولة الدفع. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للمحاولة. |

### `seller_stripe_accounts`

بيانات حساب Stripe Connect الخاص بكل بائع وحالة جاهزيته لاستقبال التحويلات.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للسجل. |
| `seller_id` | `BIGINT UNSIGNED` | لا | البائع صاحب الحساب، وله سجل واحد فقط. |
| `stripe_account_id` | `VARCHAR(255)` | لا | معرّف الحساب الفريد لدى Stripe. |
| `onboarding_status` | `VARCHAR(255)` | لا | حالة إكمال إعداد حساب البائع. |
| `payouts_status` | `VARCHAR(255)` | نعم | حالة أهلية الحساب للتحويل من Stripe إلى البنك. |
| `transfers_status` | `VARCHAR(255)` | نعم | حالة أهلية الحساب لاستقبال Transfers من المنصة. |
| `requirements_due` | `JSON` | نعم | متطلبات Stripe المتبقية على البائع. |
| `disabled_reason` | `VARCHAR(255)` | نعم | سبب تعطيل الحساب أو إحدى قدراته. |
| `last_synced_at` | `TIMESTAMP` | نعم | آخر وقت تمت فيه مزامنة الحالة من Stripe. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء السجل المحلي. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تحديث للسجل المحلي. |

### `seller_transfers`

حصة كل بائع وتحويلها من رصيد المنصة في Stripe بعد اكتمال طلبه الفرعي.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للتحويل. |
| `sub_order_id` | `BIGINT UNSIGNED` | لا | الطلب الفرعي المرتبط، وله تحويل واحد فقط. |
| `payment_id` | `BIGINT UNSIGNED` | لا | عملية الدفع التي جاء منها رصيد التحويل. |
| `seller_id` | `BIGINT UNSIGNED` | لا | البائع المستفيد. |
| `stripe_account_id` | `VARCHAR(255)` | نعم | حساب Stripe المستهدف وقت التنفيذ. |
| `stripe_transfer_id` | `VARCHAR(255)` | نعم | معرّف Transfer الفريد بعد إنشائه في Stripe. |
| `idempotency_key` | `UUID` | لا | مفتاح فريد يمنع تكرار التحويل. |
| `amount_minor` | `BIGINT UNSIGNED` | لا | حصة الطلب الفرعي بعملة الدفع وبأصغر وحدة. |
| `currency` | `CHAR(3)` | لا | عملة مبلغ الطلب الأصلي. |
| `settlement_amount_minor` | `BIGINT UNSIGNED` | نعم | حصة البائع بعد تحويلها إلى عملة تسوية Stripe. |
| `settlement_currency` | `CHAR(3)` | نعم | عملة التسوية الفعلية لدى Stripe. |
| `stripe_fee_minor` | `BIGINT UNSIGNED` | نعم | نصيب الطلب الفرعي من رسوم Stripe الفعلية. |
| `platform_fee_minor` | `BIGINT UNSIGNED` | نعم | عمولة المنصة المخصومة. |
| `platform_fee_bps` | `SMALLINT UNSIGNED` | نعم | نسبة عمولة المنصة بنقاط الأساس وقت الحساب. |
| `transfer_amount_minor` | `BIGINT UNSIGNED` | نعم | صافي المبلغ المحول للبائع بعد الرسوم. |
| `status` | `VARCHAR(255)` | لا | حالة التحويل مثل `pending`, `processing`, `succeeded`, `failed`, `blocked`, أو `skipped`. |
| `failure_reason` | `TEXT` | نعم | تفاصيل سبب فشل أو تعطل التحويل. |
| `attempted_at` | `TIMESTAMP` | نعم | وقت آخر محاولة لتنفيذ التحويل. |
| `transferred_at` | `TIMESTAMP` | نعم | وقت نجاح التحويل. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء سجل التحويل. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل لسجل التحويل. |

### `refunds`

طلبات الاسترداد الجزئي الناتجة عن إلغاء طلب فرعي مدفوع إلكترونيًا.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للاسترداد. |
| `order_id` | `BIGINT UNSIGNED` | لا | الطلب الرئيسي. |
| `sub_order_id` | `BIGINT UNSIGNED` | لا | الطلب الفرعي الملغى، وله استرداد واحد فقط. |
| `payment_id` | `BIGINT UNSIGNED` | لا | عملية الدفع الأصلية. |
| `provider_refund_id` | `VARCHAR(255)` | نعم | معرّف الاسترداد لدى Stripe بعد نجاح إنشائه. |
| `idempotency_key` | `UUID` | لا | مفتاح فريد يمنع تنفيذ الاسترداد مرتين. |
| `amount_minor` | `BIGINT UNSIGNED` | لا | المبلغ المسترد بأصغر وحدة للعملة. |
| `currency` | `CHAR(3)` | لا | عملة المبلغ المسترد. |
| `status` | `VARCHAR(255)` | لا | حالة الاسترداد مثل `pending`, `processing`, `succeeded`, أو `failed`. |
| `failure_reason` | `VARCHAR(1000)` | نعم | سبب فشل الاسترداد إن وجد. |
| `refunded_at` | `TIMESTAMP` | نعم | وقت نجاح الاسترداد. |
| `created_at` | `TIMESTAMP` | نعم | وقت إنشاء طلب الاسترداد. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل لطلب الاسترداد. |

### `stripe_webhook_events`

سجل أحداث Stripe المستلمة لمنع معالجة الحدث نفسه أكثر من مرة.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للسجل. |
| `event_id` | `VARCHAR(255)` | لا | معرّف Stripe Event الفريد. |
| `type` | `VARCHAR(255)` | لا | نوع الحدث الوارد من Stripe. |
| `processed_at` | `TIMESTAMP` | نعم | وقت اكتمال معالجة الحدث. |
| `created_at` | `TIMESTAMP` | نعم | وقت تسجيل وصول الحدث. |
| `updated_at` | `TIMESTAMP` | نعم | وقت آخر تعديل للسجل. |

## جداول Laravel الداخلية

### `migrations`

يتتبع migrations التي نفذها Laravel وترتيب دفعاتها. ينشئه أمر Artisan تلقائيًا وليس migration من ملفات المشروع.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `INT UNSIGNED` | لا | المعرّف التسلسلي لسجل التنفيذ. |
| `migration` | `VARCHAR(255)` | لا | اسم migration التي تم تنفيذها. |
| `batch` | `INT` | لا | رقم دفعة التنفيذ المستخدمة عند rollback. |

### `failed_jobs`

يحفظ مهام الـqueue التي فشلت لتشخيصها أو إعادة تشغيلها.

| العمود | النوع | يقبل `NULL` | الغرض |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | لا | المعرّف الأساسي للمهمة الفاشلة. |
| `uuid` | `VARCHAR(255)` | لا | معرّف فريد للمهمة. |
| `connection` | `TEXT` | لا | اتصال الـqueue الذي نفذ المهمة. |
| `queue` | `TEXT` | لا | اسم قائمة الانتظار. |
| `payload` | `LONGTEXT` | لا | بيانات المهمة serialized. |
| `exception` | `LONGTEXT` | لا | تفاصيل الاستثناء وstack trace. |
| `failed_at` | `TIMESTAMP` | لا | وقت فشل المهمة. |

## ملخص العلاقات الأساسية

```text
users
├── addresses
├── carts ── cart_items / cart_coupons
├── products ── product_variants ── product_variant_options
│            ├── product_attributes
│            ├── product_pictures
│            └── reviews
└── roles عبر role_user

orders
├── sub_orders ── order_items ── order_item_pictures
├── payments ── seller_transfers
├── order_coupon_allocations
└── refunds
```
