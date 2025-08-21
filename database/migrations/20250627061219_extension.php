<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Extension extends Migrator
{
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
    public function change()
    {
        $table = $this->table('extension', [
            'comment' => '扩展管理表',
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'id' => false,
            'primary_key' => 'id'
        ]);

        $table->addColumn('id', 'string', [
            'limit' => 50,
            'null' => false,
            'comment' => '扩展唯一标识符'
        ])->addColumn('name', 'string', [
            'limit' => 100,
            'null' => false,
            'comment' => '扩展名称'
        ])->addColumn('is_core', 'boolean', [
            'default' => false,
            'comment' => '是否核心扩展'
        ])->addColumn('description', 'text', [
            'null' => true,
            'comment' => '扩展描述'
        ])->addColumn('version', 'string', [
            'limit' => 20,
            'null' => false,
            'default' => '1.0.0',
            'comment' => '扩展版本'
        ])->addColumn('core_version', 'string', [
            'limit' => 20,
            'null' => false,
            'comment' => '支持的核心版本'
        ])->addColumn('author', 'string', [
            'limit' => 100,
            'null' => false,
            'comment' => '作者'
        ])->addColumn('email', 'string', [
            'limit' => 100,
            'null' => true,
            'comment' => '作者邮箱'
        ])->addColumn('website', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => '扩展地址'
        ])->addColumn('image', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => '扩展图片地址'
        ])->addColumn('license', 'string', [
            'limit' => 50,
            'null' => true,
            'comment' => '开源协议'
        ])->addColumn('status', 'integer', [
            'limit' => 1,
            'default' => 0,
            'comment' => '状态(1:未安装,2:安装,3:启用,4:禁用)'
        ])->addColumn('create_time', 'datetime', [
            'null' => true,
            'comment' => '创建时间'
        ])->addColumn('update_time', 'datetime', [
            'null' => true,
            'comment' => '更新时间'
        ])->addColumn('delete_time', 'datetime', [
            'null' => true,
            'comment' => '删除时间'
        ])->addIndex(['id'], [
            'unique' => true,
            'name' => 'uniq_id'
        ])->create();
    }
}
