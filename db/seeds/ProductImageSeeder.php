<?php

use Phinx\Seed\AbstractSeed;

class ProductImageSeeder extends AbstractSeed {
  public function getDependencies() {
    return [
      'ProductSeeder',
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
        'image'      => 'image.png',
        'product_id' => 1,
      ]
    ];
    $this->table('product_image')->insert($data)->save();
  }
}
