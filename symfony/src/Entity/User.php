<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use Symfony\Component\Serializer\Annotation\Groups;
use App\GraphQL\Resolver\MeResolver;
use App\GraphQL\Resolver\CreateUserWithProfileResolver;
use App\GraphQL\Resolver\LoginUserResolver;
use App\GraphQL\Resolver\RegisterCustomerResolver;
use App\GraphQL\Resolver\UpdateCustomerResolver;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use App\Models\CreateUpdateTrait;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[Index(name: 'search_idx', columns: ['email', 'profile_id'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    paginationClientEnabled: true,
    paginationType: 'page',
    graphQlOperations: [
        // Consultas estándar (solo admins)
        new Query(security: "is_granted('IS_AUTHENTICATED_FULLY')"),
        new QueryCollection(security: "is_granted('ROLE_ADMIN')"),

        // Crear/editar usuarios internos (solo admin)
        new Mutation(name: 'create', security: "is_granted('ROLE_ADMIN')"),
        new Mutation(name: 'update', security: "is_granted('IS_AUTHENTICATED_FULLY')"),

        // Eliminar usuarios (solo admin)
        new DeleteMutation(name: 'delete', security: "is_granted('ROLE_ADMIN')"),

        // Query “me”
        new Query(
            name: 'me',
            resolver: MeResolver::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            args: []
        ),

        // Registro con perfil completo
        new Mutation(
            name: 'register',
            resolver: CreateUserWithProfileResolver::class,
            security: "is_granted('PUBLIC_ACCESS')",
            args: [
                'email' => ['type' => 'String!'],
                'password' => ['type' => 'String!'],
                'roles' => ['type' => '[String!]', 'description' => 'Roles opcionales'],
                'enabled' => ['type' => 'Boolean', 'description' => 'Por defecto true'],
                'profile' => ['type' => 'UserProfileInput!', 'description' => 'Datos del perfil embebido']
            ]
        ),

        // Registro rápido tipo CUSTOMER (sin JWT previo)
        new Mutation(
            name: 'registerCustomer',
            resolver: RegisterCustomerResolver::class,
            security: "is_granted('PUBLIC_ACCESS')",
            args: [
                'email' => ['type' => 'String!'],
                'password' => ['type' => 'String!']
            ]
        ),

        //updateCustomer para usuarios autenticados
        new Mutation(
            name: 'updateCustomer',
            resolver: UpdateCustomerResolver::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            args: [
                'id' => ['type' => 'ID!'],
                'email' => ['type' => 'String'],
                'password' => ['type' => 'String'],
                'profile' => [
                    'type' => 'UserProfileInput',
                    'description' => 'Datos del perfil del usuario'
                ],
            ]
        ),

        // Login de usuario
        new Mutation(
            name: 'loginCustomer',
            resolver: LoginUserResolver::class,
            security: "is_granted('PUBLIC_ACCESS')",
            args: [
                'email' => ['type' => 'String!'],
                'password' => ['type' => 'String!'],
            ]
        ),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{

    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_CUSTOMER = 'ROLE_CUSTOMER';
    public const ROLE_MERCHANT = 'ROLE_MERCHANT';

    use CreateUpdateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'customer:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read', 'customer:read', 'user:write'])]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'user:write'])]
    private array $roles = [];

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:read', 'user:write'])]
    private bool $enabled = true;

    #[ORM\Column]
    #[Groups(['user:write'])] // nunca se expone en "read"
    private string $password;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $passwordChanged = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:read', 'customer:read', 'user:write'])]
    private ?UserProfile $profile = null;

    #[ORM\Column(nullable: true, unique: true)]
    private ?string $oauthId = null;

    #[ORM\Column(nullable: true)]
    private ?string $oauthProvider = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Customer::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private ?Customer $customer = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:read'])]
    private $isVerified = false;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $authCode;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Merchant::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private ?Merchant $merchant = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;

        if ($this->customer) $roles[] = self::ROLE_CUSTOMER;
        if ($this->merchant) $roles[] = self::ROLE_MERCHANT;

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string { return $this->password; }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getPasswordChanged(): ?\DateTimeImmutable { return $this->passwordChanged; }

    public function setPasswordChanged(?\DateTimeImmutable $passwordChanged): self
    {
        $this->passwordChanged = $passwordChanged;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function isEnabled(): bool { return $this->enabled; }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getProfile(): ?UserProfile { return $this->profile; }

    public function setProfile(?UserProfile $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    #[Groups(['user:read'])]
    public function getFullName(): ?string
    {
        return $this->profile ? $this->profile->getFullName() : null;
    }

    public function __toString(): string
    {
        return $this->getEmail();
    }

    public function getOauthId(): ?string { return $this->oauthId; }

    public function setOauthId(?string $oauthId): static
    {
        $this->oauthId = $oauthId;
        return $this;
    }

    public function getOauthProvider(): ?string { return $this->oauthProvider; }

    public function setOauthProvider(?string $oauthProvider): static
    {
        $this->oauthProvider = $oauthProvider;
        return $this;
    }

    public function getCustomer(): ?Customer { return $this->customer; }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getMerchant(): ?Merchant { return $this->merchant; }

    public function setMerchant(?Merchant $merchant): self
    {
        $this->merchant = $merchant;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isEmailAuthEnabled(): bool
    {
        return $this->isVerified;
    }

    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    public function getEmailAuthCode(): string
    {
        if (null === $this->authCode) {
            throw new \LogicException('The email authentication code was not set');
        }

        return $this->authCode;
    }

    public function setEmailAuthCode(string $authCode): void
    {
        $this->authCode = $authCode;
    }
}
