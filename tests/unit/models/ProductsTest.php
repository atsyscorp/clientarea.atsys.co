<?php

namespace tests\unit\models;

require_once dirname(__DIR__, 3) . '/models/Products.php';

use app\models\Products;

class DummyProducts extends Products
{
    private static $_schema;

    public static function getTableSchema()
    {
        if (self::$_schema === null) {
            self::$_schema = new \yii\db\TableSchema([
                'name' => 'products',
                'primaryKey' => ['id'],
                'columns' => [
                    'id' => new \yii\db\ColumnSchema(['name' => 'id', 'type' => 'integer', 'phpType' => 'integer']),
                    'name' => new \yii\db\ColumnSchema(['name' => 'name', 'type' => 'string', 'phpType' => 'string']),
                    'type' => new \yii\db\ColumnSchema(['name' => 'type', 'type' => 'string', 'phpType' => 'string']),
                    'billing_cycle' => new \yii\db\ColumnSchema(['name' => 'billing_cycle', 'type' => 'string', 'phpType' => 'string']),
                ]
            ]);
        }
        return self::$_schema;
    }
}

class ProductsTest extends \Codeception\Test\Unit
{
    public function testProductTypeConstants()
    {
        verify(Products::TYPE_HOSTING)->equals('hosting');
        verify(Products::TYPE_LICENSE)->equals('license');
        verify(Products::TYPE_DEVELOPMENT)->equals('development');
        verify(Products::TYPE_SUPPORT)->equals('support');
        verify(Products::TYPE_DOMAIN)->equals('domain');
    }

    public function testOptsType()
    {
        $opts = Products::optsType();
        verify($opts)->arrayContainsEquals('Hosting');
        verify($opts)->arrayContainsEquals('Licencia');
        verify($opts)->arrayContainsEquals('Desarrollo');
        verify($opts)->arrayContainsEquals('Soporte');
        verify($opts)->arrayContainsEquals('Dominio');

        verify(array_keys($opts))->arrayContainsEquals(Products::TYPE_HOSTING);
        verify(array_keys($opts))->arrayContainsEquals(Products::TYPE_LICENSE);
        verify(array_keys($opts))->arrayContainsEquals(Products::TYPE_DEVELOPMENT);
        verify(array_keys($opts))->arrayContainsEquals(Products::TYPE_SUPPORT);
        verify(array_keys($opts))->arrayContainsEquals(Products::TYPE_DOMAIN);
    }

    public function testDisplayType()
    {
        $product = new DummyProducts();
        
        $product->type = Products::TYPE_HOSTING;
        verify($product->displayType())->equals('Hosting');

        $product->type = Products::TYPE_LICENSE;
        verify($product->displayType())->equals('Licencia');

        $product->type = 'non-existent-type';
        verify($product->displayType())->equals('non-existent-type');
    }
}
