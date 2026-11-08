users (DONE)

- id (PK)
- first_name
- last_name
- email (UNIQUE)
- password
- created_at
- phone
- role => "admin", "seller", "user", "delivery"

---

category (DONE)

- id (pk)
- title

---

sub_category (DONE)

- id (pk)
- category_id (FK category.id)
- title

---

products (DONE)

- id (PK)
- title
- slug (UNIQUE)
- description
- cover_image
- price
- quantity
- seller_id (FK -> user.id)
- is_active
- created_at

---

product_pictures (DONE)

- id (pk)
- product_id (FK products.id)
- picture

---

product_sub_category (pivot table) (Done)

- id (PK)
- product_id (FK product.id)
- sub_category_id (FK sub_category.id)

---

attributes (DONE)

- id (PK)
- product_id (FK product.id)
- key
- value

---

coupons

- id (PK)
- code (UNIQUE)
- percentage
- expire_date
- max_usage
- used_count
- seller_id (FK -> user.id)
- is_active

---

addresses

- id (PK)
- user_id (FK -> users.id)
- country
- city
- street
- is_default
- note
- latitude
- longitude

---

carts

- id (PK)
- user_id (FK -> users.id)

---

cart_items

- id (PK)
- cart_id (FK -> carts.id)
- product_id (FK -> products.id)
- quantity

---

cart_coupons

- id (PK)
- cart_id (FK -> carts.id)
- seller_id (FK -> users.id)
- coupon_id (FK -> coupons.id)

---

orders

- id (PK)
- user_id (FK -> users.id)
- subtotal
- discount
- total_price
- phone
- address_id (FK -> addresses.id)
- status
- payment_status
- payment_method
- created_at

---

sub_orders

- id (PK)
- order_id (FK orders.id)
- seller_id (FK users.id)
- subtotal
- discount
- total
- status

---

order_items

- id (PK)
- order_id (FK orders.id)
- product_id (optional, nullable)
- title
- slug
- description
- cover_image
- price_at_purchase
- quantity
- created_at_snapshot

---

order_item_pictures

- id (PK)
- order_item_id (FK -> order_items.id)
- picture

---

review

- id (PK)
- product_id (FK -> product.id)
- author_id (FK -> user.id)
- content
- rate
