<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\Config\BaseConfig;

class MyConfig extends BaseConfig
{
    public function getApiUrl(): string
    {
        return $this->getStringValue(['api_url']);
    }

    public function getUsername(): string
    {
        return $this->getStringValue(['username']);
    }

    public function getPassword(): string
    {
        return $this->getStringValue(['#password']);
    }
    public function getDataFilter(): string
    {
        return $this->getStringValue(['data_filter']);
    }
}
