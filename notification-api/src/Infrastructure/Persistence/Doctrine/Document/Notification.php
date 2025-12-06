<?php

namespace NotificationApi\Infrastructure\Persistence\Doctrine\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use NotificationApi\Infrastructure\Persistence\Doctrine\Repository\NotificationRepository;

#[ODM\Document(collection: 'notifications', repositoryClass: NotificationRepository::class)]
#[ODM\Index(keys: ['userId' => 'asc', 'createdAt' => 'desc'])]
#[ODM\Index(keys: ['status' => 'asc', 'serviceName' => 'asc'])]
#[ODM\Index(keys: ['createdAt' => 'asc'], options: ['expireAfterSeconds' => 7776000])] // 90 days * 24 * 3600
class Notification
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $userId;

    #[ODM\Field(type: 'string')]
    private string $type;

    #[ODM\Field(type: 'string')]
    private string $title;

    #[ODM\Field(type: 'string')]
    private string $body;

    #[ODM\Field(type: 'hash')]
    private array $data = [];

    #[ODM\Field(type: 'string')]
    private string $status = 'pending';

    #[ODM\Field(type: 'date', nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ODM\Field(type: 'date')]
    private \DateTimeInterface $createdAt;

    #[ODM\Field(type: 'string')]
    private string $serviceName;

    #[ODM\Field(type: 'date', nullable: true)]
    private ?\DateTimeInterface $readAt = null;

    public function __construct(
        string $userId,
        string $type,
        string $title,
        string $body,
        string $serviceName,
        array $data = [],
    ) {
        $this->userId = $userId;
        $this->type = $type;
        $this->title = $title;
        $this->body = $body;
        $this->serviceName = $serviceName;
        $this->data = $data;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getReadAt(): ?\DateTimeInterface
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeInterface $readAt): self
    {
        $this->readAt = $readAt;

        return $this;
    }
}
