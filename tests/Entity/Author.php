<?php
declare(strict_types = 1);

namespace Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use SixDreams\Generator\HashedIdEntityInterface;

/**
 * Class Author
 *
 * @ORM\Table(name="author")
 * @ORM\Entity
 */
class Author implements HashedIdEntityInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=25, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="SixDreams\Generator\HashedIdGenerator")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", nullable=false)
     */
    protected $fullName;

    /**
     * @var string
     *
     * @ORM\Column(name="other_data", type="string", nullable=false)
     */
    protected $otherData;

    /**
     * Setter for Id.
     *
     * @param string $id
     *
     * @return Author
     */
    public function setId(string $id): Author
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Setter for FullName.
     *
     * @param string $fullName
     *
     * @return Author
     */
    public function setFullName(string $fullName): Author
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * Setter for OtherData.
     *
     * @param string $otherData
     *
     * @return Author
     */
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
