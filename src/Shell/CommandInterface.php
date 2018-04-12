<?php

namespace Meanbee\Magedbm2\Shell;

use Symfony\Component\Process\Process;

interface CommandInterface
{
    /**
     * @return string
     */
    public function toString(): string;

    /**
     * @return Process
     */
    public function toProcess(): Process;
}
