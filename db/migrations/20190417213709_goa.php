<?php

use Phinx\Migration\AbstractMigration;

class Goa extends AbstractMigration {
  /**
   * Change Method.
   *
   * Write your reversible migrations using this method.
   *
   * More information on writing migrations is available here:
   * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
   *
   * The following commands can be used in this method and Phinx will
   * automatically reverse them when rolling back:
   *
   *    createTable
   *    renameTable
   *    addColumn
   *    renameColumn
   *    addIndex
   *    addForeignKey
   *
   * Remember to call "create()" or "update()" and NOT "save()" when working
   * with the Table class.
   */
  public function change() {
    $this->tableRole();
    $this->tableUser();
    $this->tableProduct();
    $this->tableProductImage();
    $this->tableProductReview();
    $this->tableCart();
    $this->tableOrder();
    $this->tableTransaction();
    $this->tableCategory();
    $this->tableProductCategory();
  }

  public function tableRole() {
    if ($this->hasTable('role')) {
      $this->table('role')->drop()->save();
    }
    $this->table('role')
      ->addColumn('name', 'string', ['limit' => 255])
      ->addColumn('active', 'boolean', ['default' => true])
      ->addColumn('inserted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addIndex(['name'], ['unique' => true])
      ->save();
  }

  public function tableUser() {
    /*if ($this->hasTable('user')) {
          $this->table('user')->drop()->save();
        }*/
    $this->table('user')
      ->addColumn('email', 'string', ['limit' => 255])
      ->addColumn('first_name', 'string', ['limit' => 255])
      ->addColumn('last_name', 'string', ['limit' => 255])
      ->addColumn('password', 'string', ['limit' => 255])
      ->addColumn('address', 'string', ['limit' => 255, 'null' => true])
      ->addColumn('phone', 'string', ['limit' => 255, 'null' => true])
      ->addColumn('active', 'boolean', ['default' => true])
      ->addColumn('role_id', 'integer')
      ->addColumn('inserted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addIndex(['email'], ['unique' => true])
      ->addForeignKey('role_id', 'role', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->save();
  }

  public function tableProduct() {
    /*if ($this->hasTable('product')) {
          $this->table('product')->drop()->save();
        }*/
    $this->table('product')
      ->addColumn('sku', 'string', ['limit' => 255])
      ->addColumn('name', 'string', ['limit' => 255])
      ->addColumn('description_short', 'string')
      ->addColumn('description_one', 'string', ['limit' => 255])
      ->addColumn('description_two', 'string')
      ->addColumn('preparation', 'string')
      ->addColumn('regular_price', 'decimal')
      ->addColumn('quantity', 'integer')
      ->addColumn('active', 'boolean', ['default' => true])
      ->addColumn('inserted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('user_id', 'integer')
      ->addIndex(['name'], ['unique' => true])
      ->addForeignKey('user_id', 'user', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->save();
  }

  public function tableProductImage() {
    /*if ($this->hasTable('product_image')) {
          $this->table('product_image')->drop()->save();
        }*/
    $this->table('product_image')
      ->addColumn('image', 'string')
      ->addColumn('active', 'boolean', ['default' => true])
      ->addColumn('inserted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('product_id', 'integer')
      ->addForeignKey('product_id', 'product', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->save();
  }

  public function tableProductReview() {
    /*if ($this->hasTable('product_review')) {
          $this->table('product_review')->drop()->save();
        }*/
    $this->table('product_review')
      ->addColumn('message', 'string', ['limit' => 255])
      ->addColumn('stars', 'integer', ['limit' => 1])
      ->addColumn('active', 'boolean', ['default' => true])
      ->addColumn('inserted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('user_id', 'integer')
      ->addColumn('product_id', 'integer')
      ->addForeignKey('user_id', 'user', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->addForeignKey('product_id', 'product', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->save();
  }

  public function tableCart() {
    /*if ($this->hasTable('cart')) {
          $this->table('cart')->drop()->save();
        }*/
    $this->table('cart')
      ->addColumn('price', 'decimal')
      ->addColumn('quantity', 'integer')
      ->addColumn('active', 'boolean', ['default' => true])
      ->addColumn('inserted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('user_id', 'integer')
      ->addColumn('product_id', 'integer')
      ->addForeignKey('user_id', 'user', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->addForeignKey('product_id', 'product', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->save();
  }

  /**
   * status: 'Nuevo', 'Enviando', 'Completado', 'Cancelado'
   */
  public function tableOrder() {
    /*if ($this->hasTable('order')) {
          $this->table('order')->drop()->save();
        }*/
    $this->table('order')
      ->addColumn('subtotal', 'decimal')
      ->addColumn('total', 'decimal')
      ->addColumn('status', 'enum', ['values' => ['Nuevo', 'Enviando', 'Completado', 'Cancelado'], 'default' => 'Nuevo'])
      ->addColumn('active', 'boolean', ['default' => true])
      ->addColumn('inserted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('user_id', 'integer')
      ->addColumn('cart_id', 'integer')
      ->addIndex(['id', 'user_id'], ['unique' => true])
      ->addForeignKey('user_id', 'user', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->addForeignKey('cart_id', 'cart', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->save();
  }

  public function tableTransaction() {
    /*if ($this->hasTable('transaction')) {
          $this->table('transaction')->drop()->save();
        }*/
    $this->table('transaction')
      ->addColumn('code', 'string', ['limit' => 255])
      ->addColumn('processor', 'string', ['limit' => 255])
      ->addColumn('processor_trans_id', 'string', ['limit' => 255])
      ->addColumn('cc_num', 'string', ['limit' => 255])
      ->addColumn('cc_type', 'string', ['limit' => 255])
      ->addColumn('active', 'boolean', ['default' => true])
      ->addColumn('start_date', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('end_date', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('inserted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('order_id', 'integer')
      ->addIndex(['id', 'code'], ['unique' => true])
      ->addForeignKey('order_id', 'order', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->save();
  }

  public function tableCategory() {
    /*if ($this->hasTable('category')) {
          $this->table('category')->drop()->save();
        }*/
    $this->table('category')
      ->addColumn('name', 'string', ['limit' => 255])
      ->addColumn('active', 'boolean', ['default' => true])
      ->addColumn('inserted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->save();
  }

  public function tableProductCategory() {
    /*if ($this->hasTable('product_category')) {
          $this->table('product_category')->drop()->save();
        }*/
    $this->table('product_category')
      ->addColumn('active', 'boolean', ['default' => true])
      ->addColumn('inserted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('category_id', 'integer')
      ->addColumn('product_id', 'integer')
      ->addForeignKey('category_id', 'category', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->addForeignKey('product_id', 'product', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
      ->save();
  }
}
