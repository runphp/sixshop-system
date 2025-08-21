<?php

use think\migration\Migrator;
use think\migration\db\Column;

class ExtensionConfig extends Migrator
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('extension_config', [
            'id' => false,
            'primary_key' => 'id'
        ]);
        
        $table->addColumn('id', 'integer', [
                'identity' => true,
                'signed' => false,
                'comment' => '主键'
            ])
            ->addColumn('extension_id', 'string', [
                'limit' => 32,
                'comment' => '模块ID，关联extension表'
            ])
            ->addColumn('key', 'string', [
                'limit' => 64,
                'comment' => '配置项名称'
            ])
            ->addColumn('value', 'json', [
                'null' => true,
                'comment' => '配置值，JSON格式存储'
            ])
            ->addColumn('type', 'string', [
                'limit' => 32,
                'default' => 'text',
                'comment' => '配置类型：input, radio, select等'
            ])
            ->addColumn('title', 'string', [
                'limit' => 32,
                'default' => 'text',
                'comment' => '配置名称'
            ])
            ->addColumn('create_time', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => '创建时间'
            ])
            ->addColumn('update_time', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => '更新时间'
            ])
            ->addIndex(['extension_id'], ['name' => 'extension_id'])
            ->addIndex(['extension_id', 'key'], ['unique' => true, 'name' => 'uniq_extension_id_key']) // 复合唯一索引
            ->create();
    }
}
