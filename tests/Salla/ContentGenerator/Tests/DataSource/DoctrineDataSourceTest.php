<?php

namespace Salla\ContentGenerator\Tests\DataSource;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

use Salla\ContentGenerator\DataSource\DoctrineDataSource;

class DoctrineDataSourceTest extends \PHPUnit_Framework_TestCase
{

    protected function createConnection()
    {
        $config = new Configuration();

        $options = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $conn = DriverManager::getConnection($options, $config);

        $conn->exec("CREATE TABLE posts (
            `id` INTEGER PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL,
            'author' VARCHAR(255) NOT NULL,
            `is_published` BOOLEAN NOT NULL
        );");

        $conn->exec("
            INSERT INTO posts (id, title, slug, author, is_published) VALUES
                (1, 'First post', 'first-post', 'Gyula', 1),
                (2, 'Second post', 'second-post', 'John', 0),
                (3, 'Third post', 'third-post', 'Mike', 1),
                (4, 'Fourth post', 'fourth-post', 'Gyula', 1)
        ");

        return $conn;
    }

    public function testTableNameQuery()
    {
        $connection = $this->createConnection();

        $variables  = array('id', 'title', 'slug');
        $dataSource = new DoctrineDataSource($connection, 'posts');

        $this->assertEquals(array(
            array('id' => 1, 'title' => 'First post',  'slug' => 'first-post'),
            array('id' => 2, 'title' => 'Second post', 'slug' => 'second-post'),
            array('id' => 3, 'title' => 'Third post',  'slug' => 'third-post'),
            array('id' => 4, 'title' => 'Fourth post', 'slug' => 'fourth-post'),
        ), $dataSource->getData($variables));
    }

    public function testDoctrineStatementQuery()
    {
        $connection = $this->createConnection();

        $stmt = $connection->prepare('SELECT * FROM posts p WHERE p.author = ?');
        $stmt->bindValue(1, 'Gyula');

        $variables  = array('id', 'title', 'slug');
        $dataSource = new DoctrineDataSource($connection, $stmt);

        $this->assertEquals(array(
            array('id' => 1, 'title' => 'First post',  'slug' => 'first-post'),
            array('id' => 4, 'title' => 'Fourth post', 'slug' => 'fourth-post'),
        ), $dataSource->getData($variables));
    }

    public function testSqlStringQuery()
    {
        $connection = $this->createConnection();

        $variables  = array('id', 'title', 'slug');
        $dataSource = new DoctrineDataSource($connection, 'SELECT * FROM posts p WHERE p.is_published = 1');

        $this->assertEquals(array(
            array('id' => 1, 'title' => 'First post',  'slug' => 'first-post'),
            array('id' => 3, 'title' => 'Third post',  'slug' => 'third-post'),
            array('id' => 4, 'title' => 'Fourth post', 'slug' => 'fourth-post'),
        ), $dataSource->getData($variables));
    }
}
