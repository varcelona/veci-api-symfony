<?php

namespace App\Entity;

use App\Repository\UserProfileRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
#[Index(name: 'search_idx', columns: ['first_name','last_name','company_role'])]
#[ApiResource(
    normalizationContext: ['groups' => ['profile:read']],
    denormalizationContext: ['groups' => ['profile:write']],
    graphQlOperations: [
        new Query(name: 'item_query', security: "is_granted('IS_AUTHENTICATED_FULLY')"),
        new QueryCollection(name: 'collection_query', security: "is_granted('IS_AUTHENTICATED_FULLY')"),
        new Mutation(name: 'create', security: "is_granted('IS_AUTHENTICATED_FULLY')"),
        new Mutation(name: 'update', security: "is_granted('IS_AUTHENTICATED_FULLY')"),
        new DeleteMutation(name: 'delete', security: "is_granted('ROLE_ADMIN')")
    ]
)]
class UserProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['profile:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'user:read'])]
    private ?string $companyRole = null;

    #[ORM\Column(length: 255)]
    #[Groups(['profile:read', 'profile:write', 'user:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['profile:read', 'profile:write', 'user:read'])]
    private ?string $lastName = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'user:read'])]
    private ?Image $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'user:read'])]
    private ?string $displayName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'user:read'])]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyRole(): ?string
    {
        return $this->companyRole;
    }

    public function setCompanyRole(?string $companyRole): self
    {
        $this->companyRole = $companyRole;
        return $this;
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

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getFullName(): ?string
    {
        return sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? '');
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }
}
