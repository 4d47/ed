<?php
namespace Scrudler;

class ScrudlerResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $this->assertEquals(array('/'), ScrudlerResource::match('/'));
        $this->assertEquals(array('/foo', 'table' => 'foo'), ScrudlerResource::match('/foo'));
        $this->assertEquals(array('/foo.json', 'table' => 'foo', 'key' => '', 'extension' => 'json'), ScrudlerResource::match('/foo.json'));
    }
}
