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

#[ORM\Entity(repositoryClass: StoreRepository::class)]

#[ApiResource(
    normalizationContext: ['groups' => ['stores:read', "brands:read"]],
    denormalizationContext: ['groups' => ['stores:write']],
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
class Store
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["stores:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank]
    #[Groups(["stores:read", "stores:write", 'schedule:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank]
    #[Groups(["stores:read", "stores:write"])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["stores:read", "stores:write"])]
    private ?bool $enabled = null;

    #[ORM\ManyToOne(targetEntity: Brand::class, inversedBy: 'stores')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["stores:read", "stores:write"])]
    private ?Brand $brand = null;

    #[ORM\Column(length: 255)]
    #[Groups(["stores:read", "stores:write"])]
    private ?string $address = null;

    /**
     * @var Collection<int, StoreProduct>
     */
    #[ORM\OneToMany(targetEntity: StoreProduct::class, mappedBy: 'store')]
    #[Groups(["stores:read"])]
    private Collection $products;

    #[ORM\Column(type: "json", nullable: false)]
    #[Assert\NotBlank]
    #[Groups(["stores:read", "stores:write"])]
    private array $geolocation = [];

    /**
     * @var Collection<int, StoreSchedule>
     */
    #[ORM\OneToMany(targetEntity: StoreSchedule::class, mappedBy: 'store')]
    #[Assert\NotBlank]
    #[Groups(["stores:read", "stores:write"])]
    private Collection $schedule;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->schedule = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    /**
     * @return Collection<int, StoreProduct>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(StoreProduct $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setStore($this);
        }

        return $this;
    }

    public function removeProduct(StoreProduct $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getStore() === $this) {
                $product->setStore(null);
            }
        }

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
}
