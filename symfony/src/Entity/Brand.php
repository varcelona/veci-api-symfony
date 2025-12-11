<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Repository\BrandRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;

#[ORM\Entity(repositoryClass: BrandRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['brand:read', "store:read"]],
    denormalizationContext: ['groups' => ['brand:write']],
    paginationType: 'page',
    graphQlOperations: [
        new Query(
        ),
        new QueryCollection(
        ),
        new Mutation(
            name: 'create',
        ),
        new Mutation(
            name: 'update',

        ),
        new DeleteMutation(
            name: 'delete'
        ),
    ]
)]
class Brand
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["store:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank]
    #[Groups(["brand:read", "store:read", "brand:write"])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank]
    #[Groups(["brand:read", "store:read", "brand:write"])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["brand:read", "store:read", "brand:write"])]
    private ?bool $enabled = null;

    /**
     * @var Collection<int, Store>
     */
    #[ORM\OneToMany(targetEntity: Store::class, mappedBy: 'brand', cascade: ['persist', 'remove'])]
    #[Groups(["brand:read", "brand:write"])]
    private Collection $stores;

    public function __construct()
    {
        $this->stores = new ArrayCollection();
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

    /**
     * @return Collection<int, Store>
     */
    public function getStores(): Collection
    {
        return $this->stores;
    }

    public function addStore(Store $store): static
    {
        if (!$this->stores->contains($store)) {
            $this->stores->add($store);
            $store->setBrand($this);
        }

        return $this;
    }

    public function removeStore(Store $store): static
    {
        if ($this->stores->removeElement($store)) {
            // set the owning side to null (unless already changed)
            if ($store->getBrand() === $this) {
                $store->setBrand(null);
            }
        }

        return $this;
    }
}
