# Product catalog API

`GET /api/products` accepts composable query filters. Different filter groups use `AND`; multiple values for the same key use `OR`.

## Common filters

| Query parameter | Example |
| --- | --- |
| `search` | `search=iphone` |
| `category_ids[]` | `category_ids[]=1&category_ids[]=2` |
| `sub_category_ids[]` | `sub_category_ids[]=4` |
| `seller_ids[]` | `seller_ids[]=12` |
| `min_price`, `max_price` | `min_price=10000&max_price=30000` |
| `in_stock` | `in_stock=1` |
| `sort` | `latest`, `price_asc`, or `price_desc` |
| `per_page` | Between 1 and 100; defaults to 10 |

Price filters use each variant's price override when present, otherwise the product price, then subtract the product discount.

## Dynamic filters

- Exact product attributes: `attributes[brand][]=Apple&attributes[brand][]=Samsung`
- Numeric attributes: `attribute_ranges[ram][min]=8&attribute_ranges[ram][max]=32&attribute_ranges[ram][unit]=GB`
- Variant options: `options[color][]=Black&options[size][]=M`

All variant option, stock, and price conditions must match one variant. For example, requesting Black, size M, and in-stock never combines the color from one variant with the stock from another.

The singular `category_id`, `sub_category_id`, and `seller_id` parameters and the older `word` and `limit` parameters remain supported for compatibility.
