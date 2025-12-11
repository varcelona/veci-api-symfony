<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Repository\StoreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\GraphQL\Input\StoreInput;

#[ORM\Entity(repositoryClass: StoreRepository::class)]

#[ApiResource(
    normalizationContext: ['groups' => ['store:read', "brand:read"]],
    denormalizationContext: ['groups' => ['store:write']],
    paginationClientEnabled: true,
    paginationType: 'page',
    graphQlOperations: [
        new Query(
            security: "is_granted('PUBLIC_ACCESS')"
        ),
        new QueryCollection(
            security: "is_granted('PUBLIC_ACCESS')"
        ),
        new Mutation(
            name: 'create',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Mutation(
            name: 'update',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new DeleteMutation(
            name: 'delete',
            security: "is_granted('ROLE_ADMIN')"
        ),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class Store
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["store:read", 'storeDiscount:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Groups(['store:read', 'store:write', 'storeDiscount:read'])]
    private string $name;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    #[Groups(['store:read', 'store:write'])]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['store:read', 'store:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['store:read', 'store:write'])]
    private string $address;

    #[ORM\Column(nullable: true)]
    #[Groups(["store:read", "store:write"])]
    private ?bool $enabled = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['store:read', 'store:write'])]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['store:read', 'store:write'])]
    private ?string $email = null;


    #[ORM\ManyToOne(targetEntity: Brand::class, inversedBy: 'stores')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["store:read", "store:write"])]
    private ?Brand $brand = null;

    #[ORM\ManyToMany(targetEntity: Merchant::class, inversedBy: 'stores')]
    #[ORM\JoinTable(name: 'store_merchant')]
    #[Groups(['store:read'])]
    private Collection $merchants;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: StoreDiscount::class, cascade: ['persist', 'remove'])]
    #[Groups(['store:read'])]
    private Collection $discounts;

    #[ORM\Column(type: "json", nullable: false)]
    #[Assert\NotBlank]
    #[Groups(["store:read", "store:write"])]
    private array $geolocation = [];

    /**
     * @var Collection<int, StoreSchedule>
     */
    #[ORM\OneToMany(targetEntity: StoreSchedule::class, mappedBy: 'store', cascade: ['persist', 'remove'])]
    #[Assert\NotBlank]
    #[Groups(["store:read"])]
    private iterable $schedule;

    /**
     * @var Collection<int, Customer>
     */
    #[ORM\ManyToMany(targetEntity: Customer::class, mappedBy: 'favorites')]
    private Collection $customers;

    public function __construct()
    {

        $this->customers = new ArrayCollection();
        $this->schedule = new ArrayCollection();
        $this->merchants = new ArrayCollection();
        $this->discounts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getGeolocation(): array
    {
        return $this->geolocation;
    }

    public function setGeolocation(array $geoJson): self
    {
        if (!isset($geoJson['type']) || $geoJson['type'] !== 'Point' || !isset($geoJson['coordinates'])) {
            throw new \InvalidArgumentException("Invalid GeoJSON format");
        }
        $this->geolocation = $geoJson;

        return $this;
    }

    /**
     * @return Collection<int, StoreSchedule>
     */
    public function getSchedule(): Collection
    {
        return $this->schedule;
    }

    public function addSchedule(StoreSchedule $schedule): static
    {
        if (!$this->schedule->contains($schedule)) {
            $this->schedule->add($schedule);
            $schedule->setStore($this);
        }

        return $this;
    }

    public function removeSchedule(StoreSchedule $schedule): static
    {
        if ($this->schedule->removeElement($schedule)) {
            // set the owning side to null (unless already changed)
            if ($schedule->getStore() === $this) {
                $schedule->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Customer>
     */
    public function getCustomers(): Collection
    {
        return $this->customers;
    }

    public function addCustomer(Customer $customer): static
    {
        if (!$this->customers->contains($customer)) {
            $this->customers->add($customer);
            $customer->addFavorite($this);
        }

        return $this;
    }

    public function removeCustomer(Customer $customer): static
    {
        if ($this->customers->removeElement($customer)) {
            $customer->removeFavorite($this);
        }

        return $this;
    }

    public function getMerchants(): Collection
    {
        return $this->merchants;
    }

    public function addMerchant(Merchant $merchant): static
    {
        if (!$this->merchants->contains($merchant)) {
            $this->merchants->add($merchant);
        }

        return $this;
    }

    public function removeMerchant(Merchant $merchant): static
    {
        $this->merchants->removeElement($merchant);

        return $this;
    }

    /**
     * @return Collection<int, StoreDiscount>
     */
    public function getDiscounts(): Collection
    {
        return $this->discounts;
    }

    public function addDiscount(StoreDiscount $discount): static
    {
        if (!$this->discounts->contains($discount)) {
            $this->discounts->add($discount);
            $discount->setStore($this);
        }

        return $this;
    }

    #[ORM\PrePersist]
    public function generateSlug(): void
    {
        if (!$this->slug && $this->name) {
            $this->slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->name)));
        }
    }

    #[ORM\PreUpdate]
    public function updateSlug(): void
    {
        if (!$this->slug && $this->name) {
            $this->slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->name)));
        }
    }
}
