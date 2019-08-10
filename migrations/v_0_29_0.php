<?php

namespace jeb\snahp\migrations;

class v_0_29_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_28_2']; }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'snahp_bank_transactions' => [
                    'COLUMNS' => [
                        'id'           => ['UINT', null, 'auto_increment'],
                        'user_id'      => ['INT:11', 0],
                        'broker_id'    => ['INT:11', 0],
                        'created_time' => ['INT:11', 0],
                        'comment'      => ['VCHAR:255', 0],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
                $this->table_prefix . 'snahp_bank_transaction_items' => [
                    'COLUMNS' => [
                        'id'             => ['UINT', null, 'auto_increment'],
                        'transaction_id' => ['INT:11', 0],
                        'type'           => ['VCHAR:255', ''],
                        'amount'         => ['BINT', 0],
                        'data'           => ['TEXT', null],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
                $this->table_prefix . 'snahp_bank_exchange_rates' => [
                    'COLUMNS' => [
                        'id'           => ['UINT', null, 'auto_increment'],
                        'type'         => ['VCHAR:255', ''],
                        'display_name' => ['VCHAR:255', ''],
                        'sell_unit'      => ['VCHAR:255', ''],
                        'buy_unit'      => ['VCHAR:255', '$'],
                        'buy_rate'     => ['BINT', 1],
                        'sell_rate'    => ['BINT', 1],
                        'enable'       => ['BOOL', 1],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
                $this->table_prefix . 'snahp_mrkt_invoices' => [
                    'COLUMNS' => [
                        'id'           => ['UINT', null, 'auto_increment'],
                        'user_id'      => ['INT:11', 0],
                        'broker_id'    => ['INT:11', 0],
                        'created_time' => ['INT:11', 0],
                        'comments'     => ['VCHAR:255', ''],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
                $this->table_prefix . 'snahp_mrkt_invoice_items' => [
                    'COLUMNS' => [
                        'id'               => ['UINT', null, 'auto_increment'],
                        'invoice_id'       => ['INT:11', 0],
                        'product_id'       => ['INT:11', 0],
                        'product_class_id' => ['INT:11', 0],
                        'price'            => ['BINT', 0],
                        'quantity'         => ['INT:11', 1],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
                $this->table_prefix . 'snahp_mrkt_product_classes' => [
                    'COLUMNS' => [
                        'id'           => ['UINT', null, 'auto_increment'],
                        'name'         => ['VCHAR:255', ''],
                        'display_name' => ['VCHAR:255', ''],
                        'description'  => ['VCHAR:255', ''],
                        'price'        => ['BINT', 1000000],
                        'value'        => ['INT:11', 0],
                        'unit'         => ['VCHAR:255', ''],
                        'max_per_user' => ['INT:11', 0],
                        'enable'       => ['BOOL', 1],
                        'data'         => ['TEXT', null],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
                $this->table_prefix . 'snahp_user_inventory' => [
                    'COLUMNS' => [
                        'id'               => ['UINT', null, 'auto_increment'],
                        'user_id'          => ['INT:11', 0],
                        'product_id'       => ['INT:11', 0],
                        'product_class_id' => ['INT:11', 0],
                        'quantity'         => ['INT:11', 0],
                        'enable'           => ['BOOL', 1],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
            ],
            'add_index'    => [
                $this->table_prefix . 'snahp_bank_transactions' => [
                    'user_id' => ['user_id'],
                ],
                $this->table_prefix . 'snahp_bank_transaction_items' => [
                    'transaction_id' => ['transaction_id'],
                ],
                $this->table_prefix . 'snahp_bank_exchange_rates' => [
                    'type' => ['type'],
                ],
                $this->table_prefix . 'snahp_mrkt_invoices' => [
                    'user_id' => ['user_id'],
                ],
                $this->table_prefix . 'snahp_mrkt_invoice_items' => [
                    'invoice_id' => ['invoice_id'],
                ],
                $this->table_prefix . 'snahp_mrkt_product_classes' => [
                    'name' => ['name'],
                ],
                $this->table_prefix . 'snahp_user_inventory' => [
                    'user_id' => ['user_id'],
                    'product_class_id' => ['product_class_id'],
                ],
            ],
            'add_columns' => [
                USERS_TABLE => [
                    'snp_bank_n_token'  => ['BINT', 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'snahp_bank_transactions',
                $this->table_prefix . 'snahp_bank_transaction_items',
                $this->table_prefix . 'snahp_bank_exchange_rates',
                $this->table_prefix . 'snahp_mrkt_invoices',
                $this->table_prefix . 'snahp_mrkt_invoice_items',
                $this->table_prefix . 'snahp_mrkt_product_classes',
                $this->table_prefix . 'snahp_user_inventory',
            ],
        ];
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_econ_b_master', 1]],
            ['config.add', ['snp_bank_b_master', 1]],
            ['config.add', ['snp_bank_b_exchange', 1]],
            ['config.add', ['snp_mrkt_b_master', 1]],
            ['config.add', ['snp_uinv_b_master', 1]],
        ];
    }

}
