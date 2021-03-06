<?php
/*
 * ========================================================================
 * Copyright (c) 2011 Vladislav "FractalizeR" Rastrusny
 * Website: http://www.fractalizer.ru
 * Email: FractalizeR@yandex.ru
 * ------------------------------------------------------------------------
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================================
 */

require_once __DIR__ . '/../../src/Autoloader.php';

\phpSweetPDO\Autoloader::register();

use phpSweetPDO\SQLHelpers\Basic as Helpers;

/**
 * Test class for Connection.
 *
 * Generated by PHPUnit on 2011-04-30 at 22:44:39.
 */
class ConnectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \phpSweetPDO\Connection
     */
    protected $_connection;

    protected function setUp()
    {
        $this->_connection = new \phpSweetPDO\Connection('mysql:dbname=test;host=127.0.0.1', 'root', '');
        $this->_connection->execute("DROP TABLE IF EXISTS `phpsweetpdo`");
        $this->_connection->execute(
            "CREATE TABLE phpsweetpdo(
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        field1 char (10),
                        field2 int) ENGINE=MYISAM;
                        "
        );
        $this->_connection->execute(
            "CREATE PROCEDURE phpsweetpdo_out (OUT param1 INT)
                        BEGIN
                            SELECT COUNT(*) INTO param1 FROM phpsweetpdo;
                        END;
                        "
        );
        $this->_connection->execute(Helpers::insert('phpsweetpdo', array('field1' => 'Test 1', 'field2' => 10)));
        $this->_connection->execute(Helpers::insert('phpsweetpdo', array('field1' => 'Test 2', 'field2' => 20)));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $this->_connection->execute("DROP TABLE phpsweetpdo");
        $this->_connection->execute("DROP PROCEDURE phpsweetpdo_out");
        $this->_connection->close();
    }


    /**
     * @expectedException \PDOException
     */
    public function testConnectionException()
    {
        new \phpSweetPDO\Connection('mysql:dbname=test;host=127.0.0.1', 'InVaLidUSer', 'invalidPassWoRd');
    }


    public function testSelect()
    {
        $result = $this->_connection->select("SELECT * FROM phpsweetpdo ORDER BY field1 ASC");
        $this->assertEquals($result->count(), 2);
        $this->assertEquals(get_class($result), 'phpSweetPDO\Recordset');

        $id = 0;
        foreach ($result as $currentRow) {
            $id++;
            $this->assertEquals($currentRow->id, $id);
        }
        $this->assertEquals($id, 2);
    }

    public function testGetOneValue()
    {
        $result = $this->_connection->getOneValue("SELECT field2 FROM phpsweetpdo ORDER BY field1 DESC LIMIT 1");
        $this->assertEquals($result, 20);
    }

    public function testGetOneValueFalse()
    {
        $result = $this->_connection->getOneValue("SELECT field2 FROM phpsweetpdo WHERE id=300"); //empty result
        $this->assertEquals($result, false);
    }

    public function testGetOneRow()
    {
        $result = $this->_connection->getOneRow("SELECT * FROM phpsweetpdo ORDER BY field1 ASC LIMIT 1");
        $this->assertEquals(get_class($result), 'phpSweetPDO\RecordsetRow');
        $this->assertEquals($result->field1, "Test 1");
        $this->assertEquals($result->field2, 10);
    }

    public function testGetOneRowFalse()
    {
        $result = $this->_connection->getOneRow("SELECT field2 FROM phpsweetpdo WHERE id=300"); //empty result
        $this->assertEquals($result, false);
    }

    /**
     * @expectedException \LogicException
     */
    public function testRecordsetRowFieldMissing()
    {
        $result = $this->_connection->getOneRow("SELECT field2 FROM phpsweetpdo ORDER BY field1 ASC LIMIT 1");
        $this->assertEquals(get_class($result), 'phpSweetPDO\RecordsetRow');
        $result->fieldInexistent; //This should throw LogicException
    }

    /**
     * @expectedException \phpSweetPDO\Exceptions\DbException
     */
    public function testRecordsetDbException()
    {
        $this->_connection->execute("Bla bla bla!"); //Should throw DbException
    }

    public function testParametersPositionedSingle()
    {
        $result = $this->_connection->getOneValue("SELECT field2 FROM phpsweetpdo WHERE id=?", 1);
        $this->assertEquals($result, 10);
    }

    public function testParametersPositionedArray()
    {
        $result = $this->_connection->getOneValue(
            "SELECT field2 FROM phpsweetpdo WHERE id=? AND field2<>?",
            array(1, 300)
        );
        $this->assertEquals($result, 10);
    }

    public function testParametersNamedSingle()
    {
        $result = $this->_connection->getOneValue("SELECT field2 FROM phpsweetpdo WHERE id=:id", array('id' => 1));
        $this->assertEquals($result, 10);
    }

    public function testParametersNamedArray()
    {
        $result = $this->_connection->getOneValue(
            "SELECT field2 FROM phpsweetpdo WHERE id=:id AND field2<>:idd",
            array('id' => 1, 'idd' => 300)
        );
        $this->assertEquals($result, 10);
    }

    public function testOutputParameters()
    {
        $this->_connection->execute("CALL phpsweetpdo_out(@test)");
        $result = $this->_connection->getOneValue("SELECT @test");
        $this->assertEquals(2, $result);
    }
}