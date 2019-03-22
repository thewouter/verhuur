<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PostRepository")
 * @ORM\Table(name="symfony_demo_post")
 *
 * Defines the properties of the Post entity to represent the blog posts.
 *
 * See https://symfony.com/doc/current/book/doctrine.html#creating-an-entity-class
 *
 * Tip: if you have an existing database, you can generate these entity class automatically.
 * See https://symfony.com/doc/current/cookbook/doctrine/reverse_engineering.html
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class LeaseRequest {
    /**
     * Use constants to define configuration options that rarely change instead
     * of specifying them under parameters section in config/services.yaml file.
     *
     * See https://symfony.com/doc/current/best_practices/configuration.html#constants-vs-configuration-options
     */
    public const NUM_ITEMS = 10;
    public const ASSOCIATION_TYPES = array(
        "Scouting Regio" => 'ass_type.regio',
        "Scouting buiten regio" => 'ass_type.scouting',
        "Dispuut" => 'ass_type.dispuut',
        "Studievereniging" => 'ass_type.sv', );

    public const STATUSES = array(
        "status.placed",
        "status.contract",
        "status.signed",
        "status.leased",
        "status.rejected",
        "status.retracted", );

    private const REGIO_PP = 2;
    private const SCOUTING_pp = 3;
    private const REGIO_MIN = 30;
    private const SCOUTING_MIN = 50;
    private const OTHER_MIN = 105;
    private const OTHER_MAX = 145;
    private const DEPOSIT_SCOUTING = 100;
    private const DEPOSIT_OTHER = 250;

    private $status;

    private $num_attendants;

    private $read;

    private $paid;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank(message="post.blank_summary")
     * @Assert\Length(max=255)
     */
    private $summary;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $publishedAt;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @var Comment[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="Comment",
     *      mappedBy="post",
     *      orphanRemoval=true,
     *      cascade={"persist"}
     * )
     * @ORM\OrderBy({"publishedAt": "DESC"})
     */
    private $comments;

    /**
     * @var Tag[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Tag", cascade={"persist"})
     * @ORM\JoinTable(name="symfony_demo_post_tag")
     * @ORM\OrderBy({"name": "ASC"})
     * @Assert\Count(max="4", maxMessage="post.too_many_tags")
     */
    private $tags;

    private $association;

    private $contract;

    private $contract_signed;

    public function __construct() {
        $this->publishedAt = new \DateTime();
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->setStatus(self::STATUSES[0]);
        $this->status = 0;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(string $title): void {
        $this->title = $title;
    }

    public function getSlug(): ?string {
        return $this->slug;
    }

    public function setSlug(string $slug): void {
        $this->slug = $slug;
    }

    public function getPublishedAt(): \DateTime {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTime $publishedAt): void {
        $this->publishedAt = $publishedAt;
    }

    public function getAuthor(): ?User {
        return $this->author;
    }

    public function setAuthor(User $author): void {
        $this->author = $author;
    }

    public function getComments(): Collection {
        return $this->comments;
    }

    public function addComment(Comment $comment): void {
        $comment->setPost($this);
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
        }
        $this->setPublishedAt(new \DateTime());
    }

    public function removeComment(Comment $comment): void {
        $this->comments->removeElement($comment);
    }

    public function getSummary(): ?string {
        return $this->summary;
    }

    public function setSummary(string $summary): void {
        $this->summary = $summary;
    }

    public function addTag(Tag ...$tags): void {
        foreach ($tags as $tag) {
            if (!$this->tags->contains($tag)) {
                $this->tags->add($tag);
            }
        }
    }

    public function removeTag(Tag $tag): void {
        $this->tags->removeElement($tag);
    }

    public function getTags(): Collection {
        return $this->tags;
    }

    private $start_date;

    private $end_date;

    private $association_type;

    private $price;

    public function getStartDate(): ?\DateTimeInterface {
        return $this->start_date;
    }

    public function setStartDate(?\DateTimeInterface $start_date): self {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface {
        return $this->end_date;
    }

    public function setEndDate(?\DateTimeInterface $end_date): self {
        $this->end_date = $end_date;

        return $this;
    }

    public function getAssociationType(): ?string {
        return $this->association_type;
    }

    public function setAssociationType(string $association_type): self {
        $this->association_type = $association_type;

        return $this;
    }

    public function getPrice(): ?float {
        return $this->price;
    }

    public function setPrice(?float $price): self {
        $this->price = $price;

        return $this;
    }

    public function guessPrice(): float {
        $days = $this->getEndDate()->diff($this->getStartDate())->format("%a");
        switch ($this->getAssociationType()) {
            case 'ass_type.regio':
                return max(self::REGIO_PP * $this->getNumAttendants(), self::REGIO_MIN) * $days;
                break;
            case 'ass_type.scouting':
                return max(self::SCOUTING_pp * $this->getNumAttendants(), self::SCOUTING_MIN) * $days;
                break;
            default:
                if ($this->getNumAttendants() < 16) {
                    return self::OTHER_MIN * $days;
                } else {
                    return self::OTHER_MAX * $days;
                }
                break;
        }
        return 0;
    }

    public function getStatus(): ?string {
        return $this->status;
    }

    public function getStatusText(): ?string {
        if (is_null($this->status)) {
            return self::STATUSES[0];
        }
        return self::STATUSES[$this->getStatus()];
    }

    public function setStatus(?string $status): self {
        $this->status = $status;

        return $this;
    }

    public function getNumAttendants(): ?int {
        return $this->num_attendants;
    }

    public function setNumAttendants(int $num_attendants): self {
        $this->num_attendants = $num_attendants;

        return $this;
    }

    public function getAssociation(): ?string {
        return $this->association;
    }

    public function setAssociation(string $association): self {
        $this->association = $association;

        return $this;
    }

    public function getDeposit(): float {
        switch ($this->getAssociationType()) {
            case 'ass_type.regio':
                return self::DEPOSIT_SCOUTING;
                break;
            case 'ass_type.scouting':
                return self::DEPOSIT_SCOUTING;
                break;
            default:
                return self::DEPOSIT_OTHER;
                break;
        }
    }

    public function getContract(): ?string {
        return $this->contract;
    }

    public function setContract(?string $contract): self {
        $this->contract = $contract;

        return $this;
    }

    public function getContractSigned(): ?string {
        return $this->contract_signed;
    }

    public function setContractSigned(?string $contract_signed): self {
        $this->contract_signed = $contract_signed;

        return $this;
    }

    public function getRead(): ?bool {
        return $this->read;
    }

    public function setRead(?bool $read): self {
        $this->read = $read;

        return $this;
    }

    public function getPaid(): ?float
    {
        return $this->paid;
    }

    public function setPaid(?float $paid): self
    {
        $this->paid = $paid;

        return $this;
    }
}
