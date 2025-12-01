<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CustomerRepository;
use App\Controller\Api\CustomerInfo;
use App\State\UserPasswordHasher;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Entity\User;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(processor: UserPasswordHasher::class, validationContext: ['groups' => ['Default', 'customer:create']]),
        new Get(
            security: "is_granted('ROLE_CUSTOMER') and object == user",
            securityMessage: 'Sorry, but you are not the owner.'
        ),
        new Get(
            name: 'customerInfo',
            uriTemplate: '/customer',
            controller: CustomerInfo::class
        ),
        new Put(processor: UserPasswordHasher::class),
        new Patch(processor: UserPasswordHasher::class),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['customer:read']],
    denormalizationContext: ['groups' => ['customer:create', 'customer:update']],
    paginationClientEnabled: true,
    graphQlOperations: [
        new Query(),
        new QueryCollection(),
        new Mutation(name: 'update'),
        new DeleteMutation(name: 'delete'),
    ]
)]
#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    public const ROLE_CUSTOMER = 'ROLE_CUSTOMER';

    #[Groups(['customer:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'customer')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /**
     * @var Collection<int, Store>
     */
    #[Groups(['customer:read', 'customer:create', 'customer:update'])]
    #[ORM\ManyToMany(targetEntity: Store::class, inversedBy: 'customers')]
    private Collection $favorites;

    public function __construct()
    {
        $this->favorites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Store>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Store $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
        }

        return $this;
    }

    public function removeFavorite(Store $favorite): static
    {
        $this->favorites->removeElement($favorite);

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
