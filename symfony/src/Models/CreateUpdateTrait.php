<?php
namespace App\Models;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\HasLifecycleCallbacks]
trait CreateUpdateTrait
{
  #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
  protected ?\DateTimeImmutable $createdAt;

  #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
  protected ?\DateTimeImmutable $updatedAt;

  #[ORM\PrePersist]
  public function setCreatedAtValue(): void
  {
      $this->createdAt = new \DateTimeImmutable();
  }

  public function getCreatedAt(): ?\DateTimeImmutable
  {
      return $this->createdAt;
  }

  #[ORM\PreUpdate]
  #[ORM\PrePersist]
  public function setUpdatedAtValue(): void
  {
      $this->updatedAt = new \DateTimeImmutable();
  }

  public function getUpdatedAt(): ?\DateTimeImmutable
  {
      return $this->updatedAt;
  }
}