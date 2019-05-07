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
        'subtotal' => 500.3,
        'total'    => 800.3,
        'user_id'  => 1,
        'cart_id'  => 1,
        'transaction_id'  => 1,
      ]
    ];
    $this->table('order')->insert($data)->save();

  }
}
