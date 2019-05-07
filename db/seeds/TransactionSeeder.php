<?php

use Phinx\Seed\AbstractSeed;

class TransactionSeeder extends AbstractSeed {

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
        'code'               => '001',
        'processor'          => 'Paypal',
        'processor_trans_id' => '002',
        'cc_num'             => '2525',
        'cc_type'            => 'Credit Card',
      ]
    ];
    $this->table('transaction')->insert($data)->save();
  }
}
