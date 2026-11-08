<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property int $product_id
 * @property int $sub_category_id
 */
class ProductSubCategory extends Model {
  public $table = "product_sub_category";

  public $timestamps = false;

  protected $fillable = ['product_id', 'sub_category_id'];
}
