<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Str;


/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int $category_id
 * @property-read \App\Models\Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 */
class SubCategory extends Model
{
  protected $table = 'sub_category';

  protected $fillable = ['title', 'category_id'];

  public $timestamps = false;

  public static function booted()
  {
    static::creating(function ($sub_category) {
      $sub_category->slug = Str::slug($sub_category->title);
    });
    static::updating(function ($sub_category) {
      $sub_category->slug = Str::slug($sub_category->title);
    });
  }


  public function products()
  {
    return $this->belongsToMany(Product::class);
  }

  public function category()
  {
    return $this->belongsTo(Category::class);
  }

}
