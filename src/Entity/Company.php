<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CompanyRepository")
 * @UniqueEntity(fields="name", message="Company name already taken")
 */
class Company implements \JsonSerializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(
     *     max="20",
     *     min="2",
     *     maxMessage="Name must contain maximum 20 characters.",
     *     minMessage="Name must contain minimum 2 characters."
     * )
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(
     *     max="255",
     *     min="2",
     *     maxMessage="Address must contain maximum 255 characters.",
     *     minMessage="Address must contain minimum 2 characters."
     * )
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $address;

    /**
     * @Assert\NotBlank()
     * @var float
     * @ORM\Column(type="float")
     */
    private $latitude;

    /**
     * @Assert\NotBlank()
     * @var float
     * @ORM\Column(type="float")
     */
    private $longitude;

    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="company", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Point", mappedBy="company", cascade={"persist", "remove"})
     */
    private $point;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Coupon", mappedBy="company", orphanRemoval=true)
     */
    private $coupons;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Post", mappedBy="company")
     */
    private $posts;

    /**
     * @Assert\NotBlank()
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $picture;

    /**
     * @var resource|string
     */
    private $pictureContent;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pictureKey;

    public function __construct()
    {
        $this->picture = '/images/company/default_picture.png';
        $this->coupons = new ArrayCollection();
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPoint(): ?Point
    {
        return $this->point;
    }

    public function setPoint(Point $point): self
    {
        $this->point = $point;

        // set the owning side of the relation if necessary
        if ($this !== $point->getCompany()) {
            $point->setCompany($this);
        }

        return $this;
    }

    /**
     * @return Collection|Coupon[]
     */
    public function getCoupons(): Collection
    {
        return $this->coupons;
    }

    public function addCoupon(Coupon $coupon): self
    {
        if (!$this->coupons->contains($coupon)) {
            $this->coupons[] = $coupon;
            $coupon->setCompany($this);
        }

        return $this;
    }

    public function removeCoupon(Coupon $coupon): self
    {
        if ($this->coupons->contains($coupon)) {
            $this->coupons->removeElement($coupon);
            // set the owning side to null (unless already changed)
            if ($coupon->getCompany() === $this) {
                $coupon->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Post[]
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->addCompany($this);
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            $post->removeCompany($this);
        }

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'address' => $this->getAddress(),
            'latitude' => $this->getLatitude(),
            'longitude' => $this->getLongitude(),
            'picture' => $this->getPicture(),
            'userId' => $this->getUser()->getId()
        ];
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    public function getPictureContent(): ?string
    {
        return $this->pictureContent;
    }

    public function setPictureContent(?string $pictureContent): self
    {
        $this->pictureContent = $pictureContent;

        return $this;
    }

    public function getPictureKey(): ?string
    {
        return $this->pictureKey;
    }

    public function setPictureKey(?string $pictureKey): self
    {
        $this->pictureKey = $pictureKey;

        return $this;
    }
}
