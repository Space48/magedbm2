<?php

namespace Meanbee\Magedbm2\Shell\Command;

use Meanbee\Magedbm2\Shell\CommandInterface;
use Symfony\Component\Process\Process;

abstract class Base implements CommandInterface
{
    /**
     * @var string[]
     */
    private $arguments = [];

    /**
     * @var string|null
     */
    private $outputFile;

    /**
     * The name of the command to be executed.
     *
     * @return string
     */
    abstract protected function name(): string;

    /**
     * Base constructor.
     * @param array|string $arguments
     */
    public function __construct($arguments = '')
    {
        $arguments = is_array($arguments) ? $arguments : [$arguments];
        $arguments = array_filter($arguments);

        if (count($arguments) > 0) {
            $this->arguments($arguments);
        }
    }

    /**
     * @param $file
     * @return $this
     */
    public function output($file)
    {
        $this->outputFile = $file;

        return $this;
    }

    /**
     * @param array $args
     * @return $this
     */
    public function arguments(array $args)
    {
        foreach ($args as $arg) {
            $this->argument($arg);
        }

        return $this;
    }

    /**
     * @param $arg
     * @return $this
     */
    public function argument($arg)
    {
        $this->arguments[] = $arg;

        return $this;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $parts = [$this->name()];

        $parts = array_merge($parts, $this->arguments);

        if ($this->outputFile) {
            $parts[] = '> ' . $this->outputFile;
        }

        return implode(' ', $parts);
    }

    /**
     * @return Process
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function toProcess(): Process
    {
        return new Process($this->toString(), null, null, null, 60 * 60 * 12);
    }
}
