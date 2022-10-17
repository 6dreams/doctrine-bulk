<?php
declare(strict_types=1);

namespace Tests;

use DateTime;
use ReflectionClass;
use Taxaos\Bulk\BulkUpsert;
use Taxaos\Exceptions\FieldNotFoundException;
use Taxaos\Exceptions\NullValueException;
use Tests\Entity\Author;
use Tests\Entity\Book;
use Tests\Entity\Magazine;

/**
 * Class TestDoctrineBoot
 */
class UpsertTest extends AbstractBulkTest
{
    /**
     * Test adding entity to bulk.
     */
    public function testEntity(): void
    {
        $manager = $this->getManager();

        $author = (new Author())->setId('jnweifohg0934hgh')->setFullName('full namez')->setOtherData('random stuff');
        $book = (new Book())->setAuthor($author)->setTitle('random_text');

        $bulk = new BulkUpsert($manager, Author::class);
        $bulk->addEntity((new Author())->setFullName('full namez')->setOtherData('random stuff'));
        self::assertEquals(
            [
                ['id' => 'akwkorfmq0w0kg8scsgsos4c0',
                    'fullName' => 'full namez',
                    'otherData' => 'random stuff'
                ]
            ],
            $this->extractField($bulk, 'values')
        );

        $bulk = new BulkUpsert($manager, Book::class);
        $bulk->addEntity($book);

        self::assertEquals(
            [
                [
                    'id' => null,
                    'title' => 'random_text',
                    'author' => 'jnweifohg0934hgh'
                ]
            ],
            $this->extractField($bulk, 'values')
        );
    }

    /**
     * Test adding entity to bulk.
     */
    public function testEntityCompoundKey(): void
    {
        $manager = $this->getManager();

        $author = (new Author())->setId('jnweifohg0934hgh')->setFullName('full namez')->setOtherData('random stuff');
        $magazine = (new Magazine())->setYear(2022)->setMonth(10)->setAuthor($author)->setTitle('random_text');

        $bulk = new BulkUpsert($manager, Author::class);
        $bulk->addEntity((new Author())->setFullName('full namez')->setOtherData('random stuff'));
        self::assertEquals(
            [
                [
                    'id' => 'akwkorfmq0w0kg8scsgsos4c0',
                    'fullName' => 'full namez',
                    'otherData' => 'random stuff'
                ]
            ],
            $this->extractField($bulk, 'values')
        );

        $bulk = new BulkUpsert($manager, Magazine::class);

        $bulk->addEntity($magazine);

        self::assertEquals(
            [
                [
                    'year' => 2022,
                    'month' => 10,
                    'title' => 'random_text',
                    'author' => 'jnweifohg0934hgh',
                    'createdAt' => '2022-10-17T00:00:00+00:00',
                    'updatedAt' => null,
                ]
            ],
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

        $bulk = (new BulkUpsert($manager, Author::class))
            ->addValue($data);

        self::assertEquals([$data], $this->extractField($bulk, 'values'));
    }

    /**
     * Test for adding null to not nullable values.
     */
    public function testWrong(): void
    {
        $this->expectException(NullValueException::class);

        (new BulkUpsert($this->getManager(), Author::class))
            ->addValue(['otherData' => '']);
    }

    /**
     * Test for adding not null values.
     */
    public function testNotExists(): void
    {
        $this->expectException(FieldNotFoundException::class);

        (new BulkUpsert($this->getManager(), Author::class))
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
    protected function extractField(object $class, string $name): mixed
    {
        $reflectionClass = new ReflectionClass($class);
        $property = $reflectionClass->getProperty($name);
        $property->setAccessible(true);

        $propertyValue = $property->getValue($class);

        if (is_array($propertyValue)) {
            foreach ($propertyValue as $outerKey => $otherValue) {
                foreach ($otherValue as $key => $value) {
                    if ($value instanceof DateTime) {
                        $propertyValue[$outerKey][$key] = $value->format('c');
                    }
                }
            }
        }


        return $propertyValue;
    }
}
