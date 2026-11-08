# بناء وتشغيل الـ containers

docker compose up -d --build

# توليد الـ APP_KEY (أول مرة بس)

docker compose exec app php artisan key:generate

# تشغيل الـ migrations

docker compose exec app php artisan migrate:fresh --seed


# for testing 
docker compose exec app php artisan test tests/Feature

# .env file 
# if u need to change this config u must change it in docker-compose.yml
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=e_commerce_db
DB_USERNAME=e_commerce_user
DB_PASSWORD=secret
DB_ROOT_PASSWORD=secret
