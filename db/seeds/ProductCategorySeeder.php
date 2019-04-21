<?php

use Phinx\Seed\AbstractSeed;

class ProductCategorySeeder extends AbstractSeed {
  public function getDependencies() {
    return [
      'ProductSeeder',
      'CategorySeeder',
    ];
  }

  /**
   * Run Method.
   *
   * Write your database seeder using this method.
   *
   * More information on writing seeders is available here:
   * http://docs.phinx.org/en/latest/seeding.html
   */
  public function run() {
    $data = [
      [
        'category_id' => '1',
        'product_id'  => '1',
      ]
    ];
    $this->table('product_category')->insert($data)->save();
  }
}
