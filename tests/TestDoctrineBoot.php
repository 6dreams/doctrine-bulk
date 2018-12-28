<?php
declare(strict_types = 1);

namespace Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\TestCase;
use SixDreams\Bulk\BulkInsert;
use SixDreams\Exceptions\FieldNotFoundException;
use SixDreams\Exceptions\NullValueException;
use Tests\Entity\Author;
use Tests\Entity\Book;

/**
 * Class TestDoctrineBoot
 */
class TestDoctrineBoot extends TestCase
{
    /**
     * Test adding entity to bulk.
     */
    public function testEntity(): void
    {
        $manager = $this->getManager();

        $author = (new Author())->setId('jnweifohg0934hgh')->setFullName('full namez')->setOtherData('random stuff');
        $book   = (new Book())->setAuthor($author)->setTitle('random_text');

        $bulk = new BulkInsert($manager, Author::class);
        $bulk->addEntity((new Author())->setFullName('full namez')->setOtherData('random stuff'));
        self::assertEquals(
            [['id' => null, 'fullName' => 'full namez', 'otherData' => 'random stuff']],
            $this->extractField($bulk, 'values')
        );

        $bulk = new BulkInsert($manager, Book::class);
        $bulk->addEntity($book);
        self::assertEquals(
            [['id' => null, 'title' => 'random_text', 'shortText' => null, 'author' => 'jnweifohg0934hgh']],
            $this->extractField($bulk, 'values')
        );
    }

    /**
     * Test adding to array.
     */
    public function testArray(): void
    {
        $manager = $this->getManager();

        $data = ['fullName' => 'full namez', 'otherData' => 'random stuff'];

        $bulk = (new BulkInsert($manager, Author::class))
            ->addValue($data);

        self::assertEquals([$data], $this->extractField($bulk, 'values'));
    }

    /**
     * Test for adding null to not nullable values.
     */
    public function testWrong(): void
    {
        $this->expectException(NullValueException::class);

        (new BulkInsert($this->getManager(), Author::class))
            ->addValue(['otherData' => '']);
    }

    /**
     * Test for adding not null values.
     */
    public function testNotExists(): void
    {
        $this->expectException(FieldNotFoundException::class);

        (new BulkInsert($this->getManager(), Author::class))
            ->addValue(['dno' => '']);
    }

    /**
     * Return value of property.
     *
     * @param object $class
     * @param string $name
     *
     * @return mixed
     */
    protected function extractField(object $class, string $name)
    {
        $prop = (new \ReflectionClass($class))->getProperty($name);
        $prop->setAccessible(true);

        return $prop->getValue($class);
    }

    /**
     * Create new entity manager.
     *
     * @return EntityManagerInterface
     */
    protected function getManager(): EntityManagerInterface
    {
        return EntityManager::create(
            ['driver'   => 'pdo_sqlite'],
            Setup::createAnnotationMetadataConfiguration([__DIR__ . '/Entity/'], true, null, null, false)
        );
    }
}
