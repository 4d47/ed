<?php
namespace Ed;

class TableResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $this->assertEquals(array('/'), TableResource::match('/'));
        $this->assertEquals(array('/foo', 'table' => 'foo'), TableResource::match('/foo'));
        $this->assertEquals(array('/foo.json', 'table' => 'foo', 'key' => '', 'extension' => 'json'), TableResource::match('/foo.json'));
    }
}
