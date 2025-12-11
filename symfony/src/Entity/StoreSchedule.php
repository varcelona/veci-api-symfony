<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Repository\StoreScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\GraphQL\Input\StoreScheduleInput;

#[ORM\Entity(repositoryClass: StoreScheduleRepository::class)]
#[UniqueEntity(
    fields: ['store', 'weekDay'],
    message: 'Ya existe un horario cargado para este día en esta tienda.'
)]
#[Index(name: 'search_idx', columns: ['store_id'])]
#[ApiResource(
    normalizationContext: ['groups' => ['schedule:read', 'store:read']],
    denormalizationContext: ['groups' => ['schedule:write']],
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
class StoreSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['store:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['store:read', 'schedule:read', 'schedule:write'])]
    private ?string $weekDay = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['store:read', 'schedule:read', 'schedule:write'])]
    private ?\DateTimeInterface $timeFrom = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['store:read', 'schedule:read', 'schedule:write'])]
    private ?\DateTimeInterface $timeTo = null;

    #[ORM\ManyToOne(inversedBy: 'schedule')]
    #[Assert\NotBlank]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['schedule:read', 'schedule:write'])]
    private ?Store $store = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['store:read', 'schedule:read', 'schedule:write'])]
    private ?bool $open = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWeekDay(): ?string
    {
        return $this->weekDay;
    }

    public function setWeekDay(string $weekDay): static
    {
        $this->weekDay = $weekDay;

        return $this;
    }

    public function getTimeFrom(): ?string
    {
        if ($this->timeFrom) {
            return $this->timeFrom->format('H:i:s');
        }

        return $this->timeFrom;
    }

    public function setTimeFrom(?\DateTimeInterface $timeFrom): static
    {
        $this->timeFrom = $timeFrom;

        return $this;
    }

    public function getTimeTo(): ?string
    {
        if ($this->timeTo) {
            return $this->timeTo->format('H:i:s');
        }

        return $this->timeTo;
    }

    public function setTimeTo(?\DateTimeInterface $timeTo): static
    {
        $this->timeTo = $timeTo;

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

    public function getOpen(): ?bool
    {
        return $this->open;
    }

    public function setOpen(bool $open): static
    {
        $this->open = $open;

        return $this;
    }
}
