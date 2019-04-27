<?php

use Phinx\Seed\AbstractSeed;

class CartSeeder extends AbstractSeed {

  public function getDependencies() {
    return [
      'UserSeeder',
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
        'status'      => 'current',
        'user_id'    => 1,
      ]
    ];
    $this->table('cart')->insert($data)->save();

  }
}
