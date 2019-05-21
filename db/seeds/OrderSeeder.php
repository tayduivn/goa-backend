<?php

use Phinx\Seed\AbstractSeed;

class OrderSeeder extends AbstractSeed {

  public function getDependencies() {
    return [
      'UserSeeder',
      'CartSeeder',
      'TransactionSeeder',
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
        'subtotal' => 1401.25,
        'total'    => 1401.25,
        'user_id'  => 2,
        'cart_id'  => 1,
        'transaction_id'  => 1,
      ]
    ];
    $this->table('order')->insert($data)->save();

  }
}
