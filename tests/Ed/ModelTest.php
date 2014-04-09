<?php
namespace Ed;

class ModelTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $pdo = new \PDO2(new \PDO('sqlite::memory:'));
        $pdo->exec("
            CREATE TABLE actors (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                first_name VARCHAR(45) NOT NULL,
                last_name VARCHAR(45) NOT NULL
            )
        ");
        $pdo->exec("
            CREATE TABLE films (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                code CHAR(40) NOT NULL,
                description TEXT NOT NULL,
                is_rotten BOOLEAN NOT NULL,
                grossing DOUBLE NOT NULL DEFAULT 0,
                rating FLOAT NOT NULL DEFAULT 0,
                picture BLOB,
                dvd_release_at DATETIME,
                theatre_release_on DATE,
                release_year YEAR NOT NULL DEFAULT 1982
            )
        ");
        $pdo->exec("
            CREATE TABLE films_actors (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                film_id INTEGER NOT NULL REFERENCES films (id),
                actor_id INTEGER NOT NULL REFERENCES actors (id)
            )
        ");
        $pdo->exec("INSERT INTO actors VALUES (1, 'Al', 'Pacino')");
        $pdo->exec("INSERT INTO actors VALUES (2, 'Wesley', 'Snipes')");
        $pdo->exec("INSERT INTO actors VALUES (3, 'Tom', 'Cruise')");
        $this->db = new Model($pdo, require 'config-defaults.php');
    }

    public function testGetTables()
    {
        $this->assertEquals(array('actors', 'films', 'films_actors'), $this->db->getTables());
    }

    public function testGetPrimaryKey()
    {
        $this->assertSame('id', $this->db->getPrimaryKey('actors'));
    }

    public function testGet()
    {
        $result = $this->db->get('actors', 1);
        $this->assertEquals(1, $result->row->id);
        $this->assertNotEmpty($result->has['films_actors']);
        $this->assertNotEmpty($result->schema);

        $result = $this->db->get('actors');
        $this->assertNotEmpty($result->schema);
        $this->assertEquals(3, count($result->has['actors']->results));
    }

    public function testCreate()
    {
        $data = array('first_name' => 'Bob', 'last_name' => 'Hache', 'ignored' => 12);
        $this->assertEquals(4, $this->db->create('actors', $data));
    }

    public function testUpdate()
    {
        $this->db->update('actors', 1, array('first_name' => 'Alfredo'));
        $this->assertEquals('Alfredo', $this->db->get('actors', 1)->row->first_name);
    }

    public function testDelete()
    {
        $this->db->delete('actors', 3);
        $this->assertNull($this->db->get('actors', 3));
    }
}
