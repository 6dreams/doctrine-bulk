# Doctrine-Bulk Classes
Adds ability to multiple insert of entities or array to database using doctrine schema.

[![Build Status](https://travis-ci.org/6dreams/doctrine-bulk.svg?branch=master)](https://travis-ci.org/6dreams/doctrine-bulk)

### Notes
* Designed for MySQL (also works with PostgreSQL)
* Works with custom id generators (need few tweaks)
* Without custom id generator, works only with MySQL AI
* Allows retrive first inserted id \ total updated
* As bonus this package includes <code>HashedIdGenerator</code> that can be used for generate char(25) ids from entity data
* Please note that UPDATE queries with HashedIdGenerator currently didn't support changing Id

### Samples
#### Default usage
```php
<?php
declare(strict_types = 1);

use \Doctrine\ORM\EntityManagerInterface;
use \SixDreams\Bulk\BulkInsert;
use \SixDreams\Bulk\BulkUpdate;

/**
 * Class DbWrite
 */
class DbWrite {
    /** @var EntityManagerInterface */
    private $manager;
    
    /**
     * Creates two users in one query.
     *
     * @return int
     */
    public function createTwoUsers(): int
    {
        $firstInsertedId = (int) (new BulkInsert($this->manager, User::class))
            ->addEntity(new User('user 1', 'password'))
            ->addEntity(new User('user 2', 'password'))
            ->execute();
        
        return $firstInsertedId;
    }
    
    /**
     * Updates two users in database.
     */
    public function updateTwoUsers(): void
    {
        (new BulkUpdate($this->manager, User::class))
            ->addEntity(new User(1, 'user 1', 'new_user1_password'))
            ->addEntity(new User(2, 'user 1', 'new_user2_password'), ['password'])
            ->execute();
    }
}
```