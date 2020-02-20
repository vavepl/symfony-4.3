<?php

namespace App\Entity;

use CrEOF\Spatial\PHP\Types\Geometry\Point;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as CustomAssert;
use App\Validator\Constraints as AssertCustom;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 */
class Event
{
    const STATUS_CANCELED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_CLOSED = 2;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"read", "rating", "notifications", "event_list"})
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"read", "write"})
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"read", "write", "notifications", "event_list"})
     * @Assert\NotBlank()
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"read", "write", "event_list"})
     * @Assert\NotBlank()
     */
    private $endDate;

    /**
     * @ORM\Column(type="smallint")
     * @Groups({"read", "notifications", "event_list"})
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Company", inversedBy="events")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"userFilter", "userEventFilter"})
     */
    private $company;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserEvent", mappedBy="event")
     */
    private $users;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\EventCategory", inversedBy="events", cascade={"persist"})
     * @Groups({"write", "read", "event_list"})
     * @Assert\NotBlank()
     */
    private $eventCategory;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\EventDetail", mappedBy="event", cascade={"persist", "remove"})
     * @Groups({"read", "write"})
     */
    private $eventDetails;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Positive(groups={"write"})
     * @Groups({"read", "write"})
     */
    private $depositAmount;

    /**
     * @var Point $point
     * @ORM\Column(type="point", nullable=true)
     * @Groups({"read", "write"})
     */
    private $point;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write", "notifications"})
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write", "notifications"})
     */
    private $locality;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write", "notifications"})
     */
    private $voivodship;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write", "notifications"})
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=11, nullable=true)
     * @AssertCustom\Phone(groups={"write"})
     * @Groups({"read", "write"})
     */
    private $phone;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"read", "write"})
     * @Assert\NotBlank()
     */
    private $isDeposit;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"read", "write"})
     * @Assert\NotBlank()
     */
    private $isUserLimitReached;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     * @Groups({"read", "write"})
     */
    private $calendarDetail;

    /**
     * @ORM\Column(type="decimal", precision=2, scale=1, nullable=true)
     * @Groups({"read", "rating"})
     */
    private $ratingScore;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"read", "rating"})
     */
    private $ratingTotal;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"read", "rating"})
     */
    private $ratingCounter;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @Groups({"read", "files"})
     * @CustomAssert\FilesCount
     */
    private $files = [];

    /**
     * @var int $priceFrom
     * @Groups({"read"})
     */
    private $priceFrom;

    /**
     * @var int $priceTo
     * @Groups({"read"})
     */
    private $priceTo;

    /**
     * @var int $getUsers
     * @Groups({"read"})
     */
    private $getUsers;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->eventDetails = new ArrayCollection();
        $this->status = self::STATUS_ACTIVE;
        $this->isUserLimitReached = 0;
        $this->ratingScore = 0;
        $this->ratingTotal = 0;
        $this->ratingCounter = 0;
        $this->created_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUsers(User $user): self
    {
        if ($this->users->contains($user)) {
            return $this;
        }

        $this->users[] = $user;
        $user->addEvents($this);
        return $this;
    }

    public function removeUsers(User $user): self
    {
        if (!$this->users->contains($user)) {
            return $this;
        }

        $this->users->removeElement($user);
        $user->removeCompany($this);
        return $this;
    }

    public function getEventCategory(): ?EventCategory
    {
        return $this->eventCategory;
    }

    public function setEventCategory(?EventCategory $eventCategory): self
    {
        $this->eventCategory = $eventCategory;

        return $this;
    }

    /**
     * @return Collection|EventDetail[]
     */
    public function getEventDetails(): Collection
    {
        return $this->eventDetails;
    }

    public function addEventDetail(EventDetail $eventDetail): self
    {
        if (!$this->eventDetails->contains($eventDetail)) {
            $this->eventDetails[] = $eventDetail;
            $eventDetail->setEvent($this);
        }

        return $this;
    }

    public function removeEventDetail(EventDetail $eventDetail): self
    {
        if ($this->eventDetails->contains($eventDetail)) {
            $this->eventDetails->removeElement($eventDetail);
            // set the owning side to null (unless already changed)
            if ($eventDetail->getEvent() === $this) {
                $eventDetail->setEvent(null);
            }
        }

        return $this;
    }


    public function clearEventDetails()
    {
        foreach ($this->eventDetails as $eventDetail) {
            $this->removeEventDetail($eventDetail);
        }

        $this->eventDetails = new ArrayCollection();

        return $this;
    }

    public function getIsDeposit(): ?bool
    {
        return $this->isDeposit;
    }

    public function setIsDeposit(bool $isDeposit): self
    {
        $this->isDeposit = $isDeposit;

        return $this;
    }

    public function getIsUserLimitReached(): ?bool
    {
        return $this->isUserLimitReached;
    }

    public function setIsUserLimitReached(bool $isUserLimitReached): self
    {
        $this->isUserLimitReached = $isUserLimitReached;

        return $this;
    }

    public function getDepositAmount()
    {
        return $this->depositAmount;
    }

    public function setDepositAmount($depositAmount): self
    {
        $this->depositAmount = $depositAmount;

        return $this;
    }

    public function getPoint()
    {
        return $this->point;
    }

    public function setPoint($point): void
    {
        $this->point = $point;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getLocality(): ?string
    {
        return $this->locality;
    }

    public function setLocality(?string $locality): self
    {
        $this->locality = $locality;

        return $this;
    }

    public function getVoivodship(): ?string
    {
        return $this->voivodship;
    }

    public function setVoivodship(?string $voivodship): self
    {
        $this->voivodship = $voivodship;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCalendarDetail()
    {
        return $this->calendarDetail;
    }

    public function setCalendarDetail($calendarDetail): self
    {
        $this->calendarDetail = $calendarDetail;

        return $this;
    }

    public function getRatingScore()
    {
        return $this->ratingScore;
    }

    public function setRatingScore($ratingScore): self
    {
        $this->ratingScore = $ratingScore;

        return $this;
    }

    public function getRatingTotal()
    {
        return $this->ratingTotal;
    }

    public function setRatingTotal($ratingTotal): self
    {
        $this->ratingTotal = $ratingTotal;

        return $this;
    }

    public function getRatingCounter(): ?int
    {
        return $this->ratingCounter;
    }

    public function setRatingCounter(?int $ratingCounter): self
    {
        $this->ratingCounter = $ratingCounter;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getFiles(): ?array
    {
        return $this->files;
    }

    public function setFiles(?array $files): self
    {
        $this->files = $files;

        return $this;
    }

    public function addFile(string $filename): self
    {
        $this->files[] = $filename;

        return $this;
    }

    public function removeFile(string $filename): self
    {
        $this->files = array_diff($this->files, [$filename]);

        return $this;
    }

    /**
     * @return int
     */
    public function getGetUsers(): int
    {
        if($this->getUsers === null){
            return $this->users->count();
        } else {
            return $this->getUsers;
        }
    }

    /**
     * @param int $users
     * @return Event
     */
    public function setGetUsers(int $users): self
    {
        $this->getUsers = $users;

        return $this;
    }

    public function getPriceFrom(): int
    {
        $priceFrom = 0;

        foreach ($this->eventDetails as $eventDetail) {
            if($priceFrom == 0){
                $priceFrom = $eventDetail->getPrice();
            } else if($priceFrom > $eventDetail->getPrice()){
                $priceFrom = $eventDetail->getPrice();
            }
        }

        return $priceFrom;
    }

    public function getPriceTo(): int
    {
        $priceTo = 0;

        foreach ($this->eventDetails as $eventDetail) {
            if($priceTo == 0){
                $priceTo = $eventDetail->getPrice();
            } else if($priceTo < $eventDetail->getPrice()){
                $priceTo = $eventDetail->getPrice();
            }
        }

        return $priceTo;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function close(): void
    {
        $this->setStatus(self::STATUS_CLOSED);
    }
}
