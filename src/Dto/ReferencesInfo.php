<?php

declare(strict_types=1);

namespace PhpDep\Dto;

use PhpDep\Contracts\ReferencesInfoInterface;

class ReferencesInfo implements ReferencesInfoInterface
{
    /**
     * @param array<string> $references
     */
    public function __construct(protected array $references)
    {
    }

    /**
     * @return array<string>
     */
    public function getReferences(): array
    {
        return $this->references;
    }
}
