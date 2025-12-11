<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\StoreDiscountRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StoreDiscountRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['storeDiscount:read']],
    denormalizationContext: ['groups' => ['storeDiscount:write']],
    graphQlOperations: [
        new Query(),
        new QueryCollection(),

        new Mutation(
            name: "create",
            security: "is_granted('ROLE_MERCHANT') or is_granted('ROLE_ADMIN')"
        ),
        new Mutation(
            name: "update",
            security: "is_granted('ROLE_MERCHANT') or is_granted('ROLE_ADMIN')"
        ),
        new DeleteMutation(
            name: "delete",
            security: "is_granted('ROLE_MERCHANT') or is_granted('ROLE_ADMIN')"
        )
    ]
)]

#[ApiResource(
    normalizationContext: ['groups' => ['storeDiscount:read']],
    denormalizationContext: ['groups' => ['storeDiscount:write']],
    graphQlOperations: [
        new Query(name: "item_query"),
        new QueryCollection(name: "collection_query"),
    ]
)]
class StoreDiscount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['storeDiscount:read', 'store:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Store::class, inversedBy: 'discounts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private Store $store;

    #[ORM\Column(length: 255)]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private ?string $shortDescription = null;

    #[ORM\Column(length: 50)]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private string $discountType;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private ?float $value = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private ?float $originalPrice = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private ?float $finalPrice = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private ?string $terms = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private \DateTimeInterface $validFrom;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private \DateTimeInterface $validTo;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private ?int $quantity = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private bool $unlimited = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['storeDiscount:read', 'storeDiscount:write'])]
    private bool $enabled = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStore(): Store
    {
        return $this->store;
    }

    public function setStore(Store $store): self
    {
        $this->store = $store;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
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

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): self
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }

    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    public function setDiscountType(string $discountType): self
    {
        $this->discountType = $discountType;
        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(?float $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getOriginalPrice(): ?float
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(?float $originalPrice): self
    {
        $this->originalPrice = $originalPrice;
        return $this;
    }

    public function getFinalPrice(): ?float
    {
        return $this->finalPrice;
    }

    public function setFinalPrice(?float $finalPrice): self
    {
        $this->finalPrice = $finalPrice;
        return $this;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(?string $terms): self
    {
        $this->terms = $terms;
        return $this;
    }

    public function getValidFrom(): \DateTimeInterface
    {
        return $this->validFrom;
    }

    public function setValidFrom(\DateTimeInterface $validFrom): self
    {
        $this->validFrom = $validFrom;
        return $this;
    }

    public function getValidTo(): \DateTimeInterface
    {
        return $this->validTo;
    }

    public function setValidTo(\DateTimeInterface $validTo): self
    {
        $this->validTo = $validTo;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function isUnlimited(): bool
    {
        return $this->unlimited;
    }

    public function setUnlimited(bool $unlimited): self
    {
        $this->unlimited = $unlimited;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function isActive(): bool
    {
        $now = new \DateTimeImmutable();

        return
            $this->enabled &&
            $this->validFrom <= $now &&
            $this->validTo >= $now &&
            ($this->unlimited || $this->quantity > 0);
    }
}
