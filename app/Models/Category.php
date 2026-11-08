<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SubCategory> $sub_categories
 * @property-read int|null $sub_categories_count
 */
class Category extends Model
{
  protected $table = 'categories';

  protected $fillable = [
    'title'
  ];

  public static function booted()
  {
    static::creating(function ($category) {
      $category->slug = Str::slug($category->title);
    });

    static::updating(function ($category) {
      $category->slug = Str::slug($category->title);
    });
  }

  public $timestamps = false;


  public function sub_categories(){
    return $this->hasMany(SubCategory::class);
  }

}


