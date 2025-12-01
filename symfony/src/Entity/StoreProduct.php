<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Repository\StoreProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;

#[ORM\Entity(repositoryClass: StoreProductRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['stores:read','product:read']],
    denormalizationContext: ['groups' => ['product:write']],
    paginationClientEnabled: true,
    paginationType: 'page',
    graphQlOperations: [
        new Query(),
        new QueryCollection(),
        new Mutation(name: 'create'),
        new Mutation(name: 'update'),
        new DeleteMutation(name: 'delete'),
    ]
)]

class StoreProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['customer:read', 'product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['stores:read', 'customer:read','product:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['stores:read', 'customer:read','product:write'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['stores:read', 'customer:read','product:write'])]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['stores:read', 'customer:read','product:write'])]
    private ?string $originalPrice = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['stores:read', 'customer:read','product:write'])]
    private ?bool $enabled = true;

    #[ORM\Column(length: 255)]
    #[Groups(['stores:read', 'customer:read','product:write'])]
    private ?string $code = null;

    #[ORM\Column(nullable: true)]
    private ?int $onHand = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiresOn = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["product:read", "product:write"])]
    private ?Store $store = null;

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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getOriginalPrice(): ?string
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(?string $originalPrice): static
    {
        $this->originalPrice = $originalPrice;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getOnHand(): ?int
    {
        return $this->onHand;
    }

    public function setOnHand(?int $onHand): static
    {
        $this->onHand = $onHand;

        return $this;
    }

    public function getExpiresOn(): ?\DateTimeInterface
    {
        return $this->expiresOn;
    }

    public function setExpiresOn(?\DateTimeInterface $expiresOn): static
    {
        $this->expiresOn = $expiresOn;

        return $this;
    }

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): static
    {
        $this->store = $store;

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
}
