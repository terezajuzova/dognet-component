<?php

declare(strict_types=1);

namespace Keboola\Component;

use Keboola\Component\BaseComponent;


class MyComponent extends BaseComponent
{
    public function execute(): void
    {
       
        $this->getLogger()->info("***************************************************");
    }
}
