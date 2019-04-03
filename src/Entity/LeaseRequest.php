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
use App\Repository\PriceRepository;
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
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 * @author Wouter van Harten <wouter@woutervanharten.nl>
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
        "Studievereniging" => 'ass_type.sv',
        "Overig" => 'ass_type.other', );

    public const STATUSES = array(
        "status.placed",
        "status.contract",
        "status.signed",
        "status.leased",
        "status.deposit_retour",
        "status.rejected",
        "status.retracted",
        "status.occupied", );

    public const KEYTIMES = array(
        'label.noon' => '12:30',
        'label.afternoon' => '17:30',
        'label.evening' => '22:00',
        'label.not_known' => null,
    );

    private const REGIO_PP = 'regio_pp';
    private const SCOUTING_pp = 'scouting_pp';
    private const REGIO_MIN = 'regio_min';
    private const SCOUTING_MIN = 'scouting_min';
    private const OTHER_MIN = 'other_min';
    private const OTHER_MAX = 'other_max';
    private const DEPOSIT_SCOUTING = 'deposit_scouting';
    private const DEPOSIT_OTHER = 'deposit_other';
    private const OTHER_DAY = 'other_day';
    private const SCOUTING_DAY = 'scouting_day';
    private const REGIO_DAY = 'regio_day';

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

    private $key_deliver;

    private $key_return;

    private $priceRepository;

    public function __construct(PriceRepository $repository) {
        $this->publishedAt = new \DateTime();
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->setStatus(0);
        $this->status = 0;
        $this->setAssociationType('ass_type.other');
    }

    public function setPriceRepository(PriceRepository $repository){
        $this->priceRepository = $repository;
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
        $regio_pp = $this->priceRepository->findById(self::REGIO_PP)[0]->getPrice();
        $regio_min = $this->priceRepository->findById(self::REGIO_MIN)[0]->getPrice();
        $regio_day = $this->priceRepository->findById(self::REGIO_DAY)[0]->getPrice();
        $scouting_pp = $this->priceRepository->findById(self::SCOUTING_pp)[0]->getPrice();
        $scouting_min = $this->priceRepository->findById(self::SCOUTING_MIN)[0]->getPrice();
        $scouting_day = $this->priceRepository->findById(self::SCOUTING_DAY)[0]->getPrice();
        $other_min = $this->priceRepository->findById(self::OTHER_MIN)[0]->getPrice();
        $other_max = $this->priceRepository->findById(self::OTHER_MAX)[0]->getPrice();
        $other_day = $this->priceRepository->findById(self::OTHER_DAY)[0]->getPrice();



        switch ($this->getAssociationType()) {
            case 'ass_type.regio':
                if($days == 0){
                    return $regio_day;
                } else {
                    return max($regio_pp * $this->getNumAttendants(), $regio_min) * $days;
                }
                break;
            case 'ass_type.scouting':
                if ($days == 0){
                    return $scouting_day;
                } else {
                    return max($scouting_pp * $this->getNumAttendants(), $scouting_min) * $days;
                }
                break;
            default:
                if($days == 0){
                    return $other_day;
                } else {
                    if ($this->getNumAttendants() < 16) {
                        return $other_min * $days;
                    } else {
                        return $other_max * $days;
                    }
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

    public function setStatus(?int $status): self {
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
                return $this->priceRepository->findById(self::DEPOSIT_SCOUTING)[0]->getPrice();
                break;
            case 'ass_type.scouting':
                return $this->priceRepository->findById(self::DEPOSIT_SCOUTING)[0]->getPrice();
                break;
            default:
                return $this->priceRepository->findById(self::DEPOSIT_OTHER)[0]->getPrice();
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

    public function getPaid(): ?float {
        return $this->paid;
    }

    public function setPaid(?float $paid): self {
        $this->paid = $paid;

        return $this;
    }

    public function getKeyDeliver(): ?\DateTimeInterface {
        return $this->key_deliver;
    }

    public function setKeyDeliver(?\DateTimeInterface $key_deliver): self {
        $this->key_deliver = $key_deliver;

        return $this;
    }

    public function getKeyReturn(): ?\DateTimeInterface {
        return $this->key_return;
    }

    public function setKeyReturn(?\DateTimeInterface $key_return): self {
        $this->key_return = $key_return;

        return $this;
    }

    public function setOccupied(): self {
        $this->setStatus(7);
        return $this;
    }
}
