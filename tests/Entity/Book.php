<?php
declare(strict_types = 1);

namespace Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Book
 *
 * @ORM\Table(name="book")
 * @ORM\Entity
 */
class Book
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\Column(name="title", type="string", nullable=false)
     */
    protected string $title;

    /**
     * @var Author
     *
     * @ORM\ManyToOne(targetEntity="Author")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected Author $author;

    /**
     * Setter for Id.
     *
     * @param int $id
     *
     * @return Book
     */
    public function setId(int $id): Book
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Setter for Title.
     *
     * @param string $title
     *
     * @return Book
     */
    public function setTitle(string $title): Book
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Setter for Author.
     *
     * @param Author $author
     *
     * @return Book
     */
    public function setAuthor(Author $author): Book
    {
        $this->author = $author;

        return $this;
    }
}
