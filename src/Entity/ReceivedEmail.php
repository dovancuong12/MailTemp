<?php

namespace App\Entity;

use App\Repository\ReceivedEmailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReceivedEmailRepository::class)]
#[ORM\Index(name: 'idx_real_to', columns: ['real_to'])]
class ReceivedEmail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $realFrom;

    #[ORM\Column(length: 255)]
    private string $realTo;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fromName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fromAddress = null;

    #[ORM\Column]
    private array $toMultiple = [];

    #[ORM\Column]
    private array $bccMultiple = [];

    // length forces Doctrine to map this to LONGTEXT on MySQL (up to 4 GB)
    // instead of TEXT (64 KB), so large HTML emails are stored in full.
    #[ORM\Column(type: Types::TEXT, length: 4294967295, nullable: true)]
    private ?string $html = null;

    #[ORM\Column]
    private array $metadata = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'receivedEmails')]
    private ?TemporaryEmailBox $temporaryEmailBox = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $uuid;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $subjectReadAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->uuid = Uuid::v4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRealFrom(): string
    {
        return $this->realFrom;
    }

    public function setRealFrom(string $realFrom): static
    {
        $this->realFrom = $realFrom;

        return $this;
    }

    public function getRealTo(): string
    {
        return $this->realTo;
    }

    public function setRealTo(string $realTo): static
    {
        $this->realTo = $realTo;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function setFromName(?string $fromName): static
    {
        $this->fromName = $fromName;

        return $this;
    }

    public function getFromAddress(): ?string
    {
        return $this->fromAddress;
    }

    public function setFromAddress(?string $fromAddress): static
    {
        $this->fromAddress = $fromAddress;

        return $this;
    }

    public function getToMultiple(): array
    {
        return $this->toMultiple;
    }

    public function getToMultipleString(): string
    {
        return json_encode($this->toMultiple);
    }

    public function setToMultiple(array $toMultiple): static
    {
        $this->toMultiple = $toMultiple;

        return $this;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    /**
     * HTML to actually render for this message.
     *
     * Some emails (e.g. plain-text Microsoft one-time-code mails) arrive with no
     * HTML part, so $html is null and the body would show up blank. The Cloudflare
     * worker still forwards the plain-text body inside metadata ("text" key), so we
     * fall back to it, HTML-escaped and wrapped, whenever the HTML body is missing.
     */
    public function getDisplayHtml(): ?string
    {
        if ($this->html !== null && trim($this->html) !== '') {
            return $this->html;
        }

        $text = $this->metadata['text'] ?? null;

        if (is_string($text) && trim($text) !== '') {
            return sprintf(
                '<div style="white-space:pre-wrap;word-break:break-word;'
                . 'font-family:system-ui,-apple-system,\'Segoe UI\',Roboto,sans-serif;'
                . 'font-size:14px;line-height:1.5;padding:12px">%s</div>',
                htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        }

        return $this->html;
    }

    public function setHtml(?string $html): static
    {
        $this->html = $html;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getMetadataString(): string
    {
        return json_encode($this->metadata);
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getBccMultiple(): array
    {
        return $this->bccMultiple;
    }

    public function getBccMultipleString(): string
    {
        return json_encode($this->bccMultiple);
    }

    public function setBccMultiple(array $bccMultiple): static
    {
        $this->bccMultiple = $bccMultiple;

        return $this;
    }

    public function getTemporaryEmailBox(): ?TemporaryEmailBox
    {
        return $this->temporaryEmailBox;
    }

    public function setTemporaryEmailBox(?TemporaryEmailBox $temporaryEmailBox): static
    {
        $this->temporaryEmailBox = $temporaryEmailBox;

        return $this;
    }

    public function getHasAssignedTemporaryEmailBox(): string
    {
        if ($this->temporaryEmailBox === null) {
            return 'No';
        }

        return 'Yes';
    }

    public function __toString(): string
    {
        return sprintf('From %s with subject: %s', $this->realFrom, $this->subject ?? '(no subject)');
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;

        return $this;
    }

    public function getSubjectReadAt(): ?\DateTimeImmutable
    {
        return $this->subjectReadAt;
    }

    public function setSubjectReadAt(\DateTimeImmutable $subjectReadAt): static
    {
        $this->subjectReadAt = $subjectReadAt;

        return $this;
    }
}
