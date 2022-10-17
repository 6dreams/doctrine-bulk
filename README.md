# Doctrine-Bulk Classes
Adds ability to multiple upsert (mySQL only) of entities or array to database using doctrine schema.

Forked from https://github.com/6dreams/doctrine-bulk

### Notes
* Designed only for MySQL
* Works with custom id generators (need few tweaks)
* Without custom id generator, works only with MySQL AI
* Allows retrieve first inserted id \ total updated
* As bonus this package includes <code>HashedIdGenerator</code> that can be used for generate char(25) ids from entity data
* LifeCycleCallbacks Events::prePersist / Events::preUpdate are supported

### Samples
#### Default usage
```php
<?php
declare(strict_types = 1);

use \Doctrine\ORM\EntityManagerInterface;
use \Taxaos\Bulk\BulkInsert;
use \Taxaos\Bulk\BulkUpdate;

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
    public function updateExistingUsersAndCreateTwoUsers(): int
    {
        $dbUsers = []; // imagine some loaded users from DB and some changed data from your code 
                 
        $bulkUpsert = new BulkUpsert($this->manager, User::class);
        
        foreach ($dbUsers as $dbUser) {
            $bulkUpsert->addEntity($dbUser);
        }
        
        // now 2 new users
        $bulkUpsert->addEntity(new User('user 1', 'password'));
        $bulkUpsert->addEntity(new User('user 2', 'password'));
            
        $firstInsertedId = (int) $bulkUpsert->execute();
        
        return $firstInsertedId;
    }
}
```
