# Doctrine-Bulk Classes
Adds ability to multiple insert of entities or array to database using doctrine schema.

### Notes
* Designed for MySQL
* Works with custom id generators (need few tweaks)
* Without custom id generator, works only with MySQL AI
* Allows retrive first inserted id \ total updated
* As bonus this package includes <code>HashedIdGenerator</code> that can be used for generate char(25) ids from entity data

### Samples
#### Default usage
```php
<?php
declare(strict_types = 1);

use \Doctrine\ORM\EntityManagerInterface;
use \SixDreams\Bulk\BulkInsert;

class DbWrite {
    /** @var EntityManagerInterface */
    private $manager;
    
    public function createTwoUsers(): int {
        $firstInsertedId = (int) (new BulkInsert($this->manager, User::class))
            ->addEntity(new User('user 1', 'password'))
            ->addEntity(new User('user 2', 'password'))
            ->execute();
        
        return $firstInsertedId;
    }
}
```