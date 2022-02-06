<?php

declare(strict_types=1);

namespace PhpDep\Contracts;

interface ClassInfoInterface
{
    public function getClassName(): string;
}
