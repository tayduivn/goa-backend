<?php

use Phinx\Seed\AbstractSeed;

class PaymentsSeeder extends AbstractSeed {

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
        'stripe_secret_token'      => 'your secret token',
        'stripe_publishable_token' => 'your publishable token',
        'paypal_token'             => 'your paypal token',
      ]
    ];
    $this->table('payment')->insert($data)->save();
  }
}
