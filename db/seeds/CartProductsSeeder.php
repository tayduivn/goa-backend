<?php

use Phinx\Seed\AbstractSeed;

class CartProductsSeeder extends AbstractSeed {

  public function getDependencies() {
    return [
      'CartSeeder',
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
        'quantity'   => 2,
        'cart_id'    => 1,
        'product_id' => 1,
      ],
      [
        'quantity'   => 3,
        'cart_id'    => 1,
        'product_id' => 2,
      ]
    ];
    $this->table('cart_products')->insert($data)->save();

  }
}
