<?php
declare(strict_types=1);

namespace Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Magazine
 *
 * @ORM\Table(name="magazine")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class Magazine
{
    use CreatedAtTrait;
    use UpdatedAtTrait;

    /**
     * @ORM\Column(name="year", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id()
     */
    protected int $year;

    /**
     * @ORM\Column(name="month", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id()
     */
    protected int $month;

    /**
     * @ORM\Column(name="title", type="string", nullable=false)
     */
    protected string $title;

    /**
     * @ORM\ManyToOne(targetEntity="Author")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected Author $author;

    public function setYear(int $year): Magazine
    {
        $this->year = $year;

        return $this;
    }

    public function setMonth(int $month): Magazine
    {
        $this->month = $month;

        return $this;
    }

    public function setTitle(string $title): Magazine
    {
        $this->title = $title;

        return $this;
    }

    public function setAuthor(Author $author): Magazine
    {
        $this->author = $author;

        return $this;
    }
}
