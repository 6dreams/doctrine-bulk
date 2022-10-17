<?php declare(strict_types=1);

namespace Tests\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait CreatedAtTrait
{
    /**
     * @ORM\Column(
     *     name="created_at",
     *     type="datetime",
     *     nullable=true,
     * )
     */
    public ?DateTime $createdAt = null;
    
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new DateTime('2022-10-17 00:00:00');
        }
    }
}
