# Cartiva Platform

منصة تجارة إلكترونية مبنية باستخدام Laravel 13 وVite.

## متطلبات التشغيل المحلي

ثبّت الأدوات التالية على جهازك قبل البدء:

- PHP 8.3 أو أحدث، مع إضافات Laravel المعتادة وإضافة `pdo_mysql`.
- Composer 2.
- MySQL 8.
- Node.js 20.19 أو أحدث ضمن الإصدار 20، أو Node.js 22.12 أو أحدث، وnpm.
- Stripe CLI عند الحاجة إلى تجربة الدفع وStripe Connect محليًا.

يمكن التأكد من الإصدارات بالأوامر التالية:

```bash
php -v
composer --version
mysql --version
node --version
npm --version
```

## تثبيت المشروع بدون Docker

### 1. تنزيل المشروع

```bash
git clone <repository-url>
cd cartiva-platform
```

### 2. تثبيت حزم PHP وJavaScript

```bash
composer install
npm ci
```

### 3. إنشاء ملف الإعدادات

```bash
cp .env.example .env
php artisan key:generate
```

على Windows PowerShell استخدم:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### 4. إنشاء قاعدة بيانات MySQL

ادخل إلى MySQL بحساب يملك صلاحية إنشاء قواعد البيانات والمستخدمين:

```bash
mysql -u root -p
```

ثم نفّذ:

```sql
CREATE DATABASE e_commerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'e_commerce_user'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON e_commerce_db.* TO 'e_commerce_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

يمكنك اختيار اسم قاعدة بيانات أو مستخدم أو كلمة مرور مختلفة، بشرط استخدام القيم نفسها في ملف `.env`.

### 5. ضبط ملف `.env`

حدّث إعدادات التطبيق وقاعدة البيانات لتشير إلى الخدمات المثبتة على جهازك بدل أسماء خدمات Docker:

```dotenv
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e_commerce_db
DB_USERNAME=e_commerce_user
DB_PASSWORD=secret

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

MAIL_MAILER=log
```

استخدام `MAIL_MAILER=log` يجعل رسائل البريد تظهر في `storage/logs/laravel.log` ولا يحتاج إلى Mailpit أو خادم بريد محلي.

### 6. إنشاء الجداول والبيانات التجريبية

```bash
php artisan migrate --seed
php artisan storage:link
```

لا تستخدم `migrate:fresh` على قاعدة بيانات تحتوي على بيانات مهمة لأنه يحذف جميع الجداول قبل إنشائها من جديد.

### 7. تشغيل المشروع

شغّل Laravel في Terminal:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

وشغّل Vite في Terminal أخرى:

```bash
npm run dev
```

المشروع سيكون متاحًا على:

```text
http://127.0.0.1:8000
```

شغّل الـscheduler في Terminal إضافية حتى تتم إعادة محاولة تحويلات البائع المعلقة:

```bash
php artisan schedule:work
```

## تشغيل الاختبارات

يستخدم الاختبار قاعدة بيانات SQLite مؤقتة في الذاكرة، لذلك لن يغيّر بيانات قاعدة MySQL المحلية:

```bash
php artisan test
```

ويمكن تشغيل فحوص إضافية بالأوامر التالية:

```bash
./vendor/bin/pint --test
npm run build
```

## إعداد Stripe محليًا

ضع مفتاح Stripe التجريبي ودولة حسابات البائعين في `.env`:

```dotenv
STRIPE_SECRET=sk_test_xxx
STRIPE_CURRENCY=egp
STRIPE_CONNECT_COUNTRY=EG
```

بعد تثبيت Stripe CLI، يمكن تشغيل مستمع Stripe Connect يدويًا:

```bash
stripe listen \
  --all-thin \
  --events-from @accounts \
  --forward-to http://127.0.0.1:8000/api/webhooks/stripe/connect
```

وفي Terminal أخرى شغّل مستمع Stripe Checkout:

```bash
stripe listen \
  --events checkout.session.completed,checkout.session.async_payment_succeeded,checkout.session.async_payment_failed,checkout.session.expired \
  --forward-to http://127.0.0.1:8000/api/webhooks/stripe/checkout
```

عند التشغيل اليدوي، انسخ قيمة `whsec_...` التي يعرضها كل مستمع إلى المتغير المناسب ثم امسح config cache:

```dotenv
STRIPE_CONNECT_WEBHOOK_SECRET=whsec_xxx
STRIPE_CHECKOUT_WEBHOOK_SECRET=whsec_xxx
```

```bash
php artisan config:clear
```

## تشغيل خدمات التطوير بسكربت واحد

بعد إنهاء خطوات التثبيت السابقة مرة واحدة، يوجد سكربت يشغّل Laravel وVite والـscheduler ومستمعي Stripe معًا في Terminal واحدة، ويضبط webhook secrets المؤقتة تلقائيًا:

```bash
composer dev:stripe
```
