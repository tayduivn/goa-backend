<?php

use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed {

  public function getDependencies() {
    return [
      'RoleSeeder',
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
        'email'    => 'admin@gmail.com',
        'first_name'     => 'admin',
        'last_name'     => 'last',
        'password' => "$2y$10$/C90fWobQk6mUz8UZfb73Oo900vXXM.BZAXKppwnnfgkQNN1BWPjG",
        'address' => "USA",
        'phone' => "+145645644",
        'role_id' => "1",
      ],
      [
        'email'    => 'goa@gmail.com',
        'first_name'     => 'goa',
        'last_name'     => 'last',
        'password' => "$2y$10$/C90fWobQk6mUz8UZfb73Oo900vXXM.BZAXKppwnnfgkQNN1BWPjG",
        'address' => "USA",
        'phone' => "145645644",
        'role_id' => "2",
      ],
    ];
    $this->table('user')->insert($data)->save();
  }
}
