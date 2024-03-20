<?php

declare(strict_types=1);

namespace Keboola\Component\Manifest\ManifestManager\Options;

class OutFileManifestOptions
{
    /** @var string[] */
    private array $tags;

    private bool $isPublic;

    private bool $isPermanent;

    private bool $notify;

    private bool $isEncrypted;

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $result = [];

        if (isset($this->tags)) {
            $result['tags'] = $this->tags;
        }
        if (isset($this->isPublic)) {
            $result['is_public'] = $this->isPublic;
        }
        if (isset($this->isPermanent)) {
            $result['is_permanent'] = $this->isPermanent;
        }
        if (isset($this->notify)) {
            $result['notify'] = $this->notify;
        }
        if (isset($this->isEncrypted)) {
            $result['is_encrypted'] = $this->isEncrypted;
        }

        return $result;
    }

    /**
     * @param string[] $tags
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function setIsPermanent(bool $isPermanent): self
    {
        $this->isPermanent = $isPermanent;
        return $this;
    }

    public function setNotify(bool $notify): self
    {
        $this->notify = $notify;
        return $this;
    }

    public function setIsEncrypted(bool $isEncrypted): self
    {
        $this->isEncrypted = $isEncrypted;
        return $this;
    }
}
