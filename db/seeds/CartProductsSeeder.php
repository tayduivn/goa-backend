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
        'price'      => 50.50,
        'quantity'   => 10,
        'cart_id'    => 1,
        'product_id' => 1,
      ]
    ];
    $this->table('cart_products')->insert($data)->save();

  }
}
