<?php

use Phinx\Seed\AbstractSeed;

class SingUpEmailSeeder extends AbstractSeed {

  /**
   * Run Method.
   *
   * Write your database seeder using this method.
   *
   * More information on writing seeders is available here:
   * http://docs.phinx.org/en/latest/seeding.html
   */
  public function run() {
    $this->table('sing_up_email')->insert([['email' => 'admin@gmail.com',],])->save();
  }
}
