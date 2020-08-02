<?php
declare(strict_types = 1);

namespace Tests;

use SixDreams\Bulk\BulkUpdate;
use Tests\Entity\Author;
use Tests\Entity\Book;

/**
 * Class UpdateTest
 */
class UpdateTest extends AbstractBulkTest
{
    /**
     * Test update.
     *
     * @dataProvider provider
     *
     * @param string $exceptedQuery
     * @param int    $exceptedBindings
     * @param array  $entities
     */
    public function testUpdate(string $exceptedQuery, int $exceptedBindings, array $entities): void
    {
        $manager = $this->getManager();

        $update = new BulkUpdate($manager, Book::class);

        foreach ($entities as $id => $entity) {
            if (\is_object($entity)) {
                $update->addEntity($entity);
            } else {
                $update->addValue($id, $entity);
            }
        }

        [$query, $bindings] = $update->getSQL();

        self::assertEquals($exceptedQuery, $query);
        self::assertCount($exceptedBindings, $bindings);
    }

    /**
     * Test on short update, if fields are defined.
     */
    public function testShortEntity(): void
    {
        [$query, $bind] = (new BulkUpdate($this->getManager(), Book::class))
            ->addEntity((new Book())->setId(123)->setTitle('test'), ['title'])
            ->getSQL();

        self::assertEquals('UPDATE book SET title = CASE WHEN id = 123 THEN :T0 END WHERE id IN (123);', $query);
        self::assertCount(1, $bind);
    }

    /**
     * Data provider for @see testUpdate.
     *
     * @return array
     */
    public function provider(): array
    {
        return [
            [
                'UPDATE book SET title = CASE WHEN id = 123 THEN :T0 WHEN id = 333 THEN :T3 END, short_text = CASE WHEN id = 123 THEN NULL WHEN id = 333 THEN short_text END, author_id = CASE WHEN id = 123 THEN NULL WHEN id = 333 THEN 1 END WHERE id IN (123, 333);',
                2,
                [
                    123 => (new Book())
                        ->setTitle('title')
                        ->setId(123),
                    333 => [
                        'title'  => 'Dj. Ban',
                        'author' => 1
                    ]
                ]
            ],
            [
                'UPDATE book SET title = CASE WHEN id = 123 THEN :T0 END, short_text = CASE WHEN id = 123 THEN NULL END, author_id = CASE WHEN id = 123 THEN :T2 END WHERE id IN (123);',
                2,
                [
                    123 => [
                        'title'     => 'Overwrite',
                        'author'    => 32770,
                        'shortText' => 'ddd'
                    ],
                    333 => (new Book())
                        ->setId(123)
                        ->setTitle('NewValue')
                        ->setAuthor((new Author())->setId('str_id'))
                ]
            ]
        ];
    }
}
