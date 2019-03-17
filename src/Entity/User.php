<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields="email", message="Email already taken")
 */
class User implements UserInterface, \JsonSerializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank()
     * @Assert\Email(
     *     message="The email '{{ value }}' is not a valid email.",
     *     checkMX = true
     * )
     * @var string
     * @ORM\Column(type="string", length=180, unique=true, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @Assert\NotBlank(groups={"update_profile"})
     * @Assert\Length(
     *     max="20",
     *     min="2",
     *     maxMessage="First name must contain maximum 20 characters.",
     *     minMessage="First name must contain minimum 2 characters.",
     *     groups={"update_profile"}
     * )
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $firstName;

    /**
     * @Assert\NotBlank(groups={"update_profile"})
     * @Assert\Length(
     *     max="20",
     *     min="2",
     *     maxMessage="Last name must contain maximum 20 characters.",
     *     minMessage="Last name must contain minimum 2 characters.",
     *     groups={"update_profile"}
     * )
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

    /**
     * @Assert\Length(
     *     max="255",
     *     maxMessage="About must contain maximum 255 characters.",
     *     groups={"update_profile"}
     * )
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $about;

    /**
     * @Assert\NotBlank()
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $picture;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $apiToken;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Company", mappedBy="user", orphanRemoval=true)
     */
    private $company;

    /**
     * @ORM\Column(type="boolean")
     */
    private $approved;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="followers")
     */
    private $following;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="following")
     */
    private $followers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Point", mappedBy="user", orphanRemoval=true)
     */
    private $points;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Coupon", mappedBy="user", orphanRemoval=true)
     */
    private $coupons;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Post", mappedBy="user", orphanRemoval=true)
     */
    private $posts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="user", orphanRemoval=true)
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Likes", mappedBy="user", orphanRemoval=true)
     */
    private $likes;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(
     *     max="30",
     *     min="6",
     *     maxMessage="Password must contain maximum 30 characters.",
     *     minMessage="Password must contain minimum 6 characters."
     * )
     * @var string
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private $google_id;

    /**
     * @ORM\Column(type="bigint", nullable=true, unique=true)
     */
    private $instagramId;

    /**
     * @var resource|string
     */
    private $pictureContent;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pictureKey;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $facebookId;

    public function __construct()
    {
        $this->roles = ['ROLE_RETAILER'];
        $this->picture = '/images/profile/default_picture.png';
        $this->company = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->points = new ArrayCollection();
        $this->coupons = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getAbout(): ?string
    {
        return $this->about;
    }

    public function setAbout(?string $about): self
    {
        $this->about = $about;

        return $this;
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

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(string $apiToken): self
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    /**
     * @return Collection|Company[]
     */
    public function getCompany(): Collection
    {
        return $this->company;
    }

    public function addCompany(Company $company): self
    {
        if (!$this->company->contains($company)) {
            $this->company[] = $company;
            $company->setUser($this);
        }

        return $this;
    }

    public function removeCompany(Company $company): self
    {
        if ($this->company->contains($company)) {
            $this->company->removeElement($company);
            // set the owning side to null (unless already changed)
            if ($company->getUser() === $this) {
                $company->setUser(null);
            }
        }

        return $this;
    }

    public function getApproved(): ?bool
    {
        return $this->approved;
    }

    public function setApproved(bool $approved): self
    {
        $this->approved = $approved;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function addFollowing(self $following): self
    {
        if (!$this->following->contains($following)) {
            $this->following[] = $following;
        }

        return $this;
    }

    public function removeFollowing(self $following): self
    {
        if ($this->following->contains($following)) {
            $this->following->removeElement($following);
        }

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(self $follower): self
    {
        if (!$this->followers->contains($follower)) {
            $this->followers[] = $follower;
            $follower->addFollowing($this);
        }

        return $this;
    }

    public function removeFollower(self $follower): self
    {
        if ($this->followers->contains($follower)) {
            $this->followers->removeElement($follower);
            $follower->removeFollowing($this);
        }

        return $this;
    }

    /**
     * @return Collection|Point[]
     */
    public function getPoints(): Collection
    {
        return $this->points;
    }

    public function addPoint(Point $point): self
    {
        if (!$this->points->contains($point)) {
            $this->points[] = $point;
            $point->setUser($this);
        }

        return $this;
    }

    public function removePoint(Point $point): self
    {
        if ($this->points->contains($point)) {
            $this->points->removeElement($point);
            // set the owning side to null (unless already changed)
            if ($point->getUser() === $this) {
                $point->setUser(null);
            }
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
            $coupon->setUser($this);
        }

        return $this;
    }

    public function removeCoupon(Coupon $coupon): self
    {
        if ($this->coupons->contains($coupon)) {
            $this->coupons->removeElement($coupon);
            // set the owning side to null (unless already changed)
            if ($coupon->getUser() === $this) {
                $coupon->setUser(null);
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
            $post->setUser($this);
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            // set the owning side to null (unless already changed)
            if ($post->getUser() === $this) {
                $post->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Likes[]
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Likes $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes[] = $like;
            $like->setUser($this);
        }

        return $this;
    }

    public function removeLike(Likes $like): self
    {
        if ($this->likes->contains($like)) {
            $this->likes->removeElement($like);
            // set the owning side to null (unless already changed)
            if ($like->getUser() === $this) {
                $like->setUser(null);
            }
        }

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'approved' => $this->getApproved(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'about' => $this->getAbout(),
            'picture' => $this->getPicture(),
            'role' => $this->getRoles(),
            'email' => $this->getEmail(),
            'password' => $this->getPassword(),
            'api_token' => $this->getApiToken(),
            'google_id' => $this->getGoogleId(),
            'instagram_id' => $this->getInstagramId()
        ];
    }

    public function getGoogleId(): ?string
    {
        return $this->google_id;
    }

    public function setGoogleId(?string $google_id): self
    {
        $this->google_id = $google_id;

        return $this;
    }

    public function getInstagramId(): ?int
    {
        return $this->instagramId;
    }

    public function setInstagramId(?int $instagramId): self
    {
        $this->instagramId = $instagramId;

        return $this;
    }

    public function getPictureContent()
    {
        return $this->pictureContent;
    }

    public function setPictureContent($pictureContent): self
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

    public function getFacebookId(): ?string
    {
        return $this->facebookId;
    }

    public function setFacebookId(?string $facebookId): self
    {
        $this->facebookId = $facebookId;

        return $this;
    }
}
