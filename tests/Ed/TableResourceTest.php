<?php
namespace Ed;

class TableResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $this->assertEquals(array(), TableResource::match('/'));
        $this->assertEquals(array('table' => 'foo'), TableResource::match('/foo'));
        $this->assertEquals(array('table' => 'foo', 'key' => '', 'extension' => 'json'), TableResource::match('/foo.json'));
    }
}
