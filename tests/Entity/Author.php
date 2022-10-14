<?php
declare(strict_types=1);

namespace Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Taxaos\Generator\HashedIdEntityInterface;

/**
 * Class Author
 *
 * @ORM\Table(name="author")
 * @ORM\Entity
 */
class Author implements HashedIdEntityInterface
{
    /**
     * @ORM\Column(name="id", type="string", length=25, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Taxaos\Generator\HashedIdGenerator")
     */
    protected string $id;

    /**
     * @ORM\Column(name="full_name", type="string", nullable=false)
     */
    protected string $fullName;

    /**
     * @ORM\Column(name="other_data", type="string", nullable=false)
     */
    protected string $otherData;

    public function setId(string $id): Author
    {
        $this->id = $id;

        return $this;
    }

    public function setFullName(string $fullName): Author
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function setOtherData(string $otherData): Author
    {
        $this->otherData = $otherData;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHashGeneratorValues(): array
    {
        return [$this->fullName];
    }

    /**
     * @inheritdoc
     */
    public function getHashGeneratorFields(): array
    {
        return ['fullName'];
    }

    /**
     * @inheritdoc
     */
    public function getHashGeneratorFieldValue(string $name, $value)
    {
        return $value;
    }
}
