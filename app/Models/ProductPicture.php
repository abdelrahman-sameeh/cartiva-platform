<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $product_id
 * @property string $picture
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|ProductPicture newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductPicture newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductPicture query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductPicture whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductPicture wherePicture($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductPicture whereProductId($value)
 * @mixin \Eloquent
 */
class ProductPicture extends Model
{

  public $timestamps = false;

  protected $appends = ['picture_url'];

  protected $fillable = [
    'product_id',
    'picture',
  ];

  public function getPictureUrlAttribute()
  {
    return url($this->picture);
  }

  public function product()
  {
    return $this->belongsTo(Product::class);
  }

}
