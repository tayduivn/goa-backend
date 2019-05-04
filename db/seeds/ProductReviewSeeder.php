<?php

use Phinx\Seed\AbstractSeed;

class ProductReviewSeeder extends AbstractSeed {
  public function getDependencies() {
    return [
      'UserSeeder',
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
        'title' => 'Hi Goa',
        'message' => 'My message',
        'stars'  => 5,
        'user_id'  => 1,
        'product_id'  => 1,
      ]
    ];
    $this->table('product_review')->insert($data)->save();
  }
}
