<?php

namespace Meanbee\Magedbm2\Shell;

use Symfony\Component\Process\Process;

class Pipe implements CommandInterface
{
    private $commands = [];

    public function command(CommandInterface $command)
    {
        $this->commands[] = $command;
        return $this;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return implode(' | ', array_map(function (CommandInterface $command) {
            return $command->toString();
        }, $this->commands));
    }

    /**
     * @return Process
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function toProcess(): Process
    {
        return new Process($this->toString(), null, null, null, 60 * 60 * 12);
    }
}
