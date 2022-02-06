<?php

namespace PhpDep\Dto;

use PhpDep\Contracts\ClassInfoInterface;

class ClassReferencesInfo extends ReferencesInfo implements ClassInfoInterface
{
    /**
     * @param string $className
     * @param array<string> $references
     */
    public function __construct(private string $className, array $references)
    {
        parent::__construct($references);
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
}
