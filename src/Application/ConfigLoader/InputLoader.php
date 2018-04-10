<?php

namespace Meanbee\Magedbm2\Application\ConfigLoader;

use Meanbee\Magedbm2\Application\Config;
use Meanbee\Magedbm2\Application\ConfigLoaderInterface;
use Symfony\Component\Console\Input\InputInterface;

class InputLoader implements ConfigLoaderInterface
{
    /**
     * @var InputInterface
     */
    private $input;

    public function __construct(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * @return Config
     */
    public function asConfig()
    {
        $values = [];

        foreach ($this->input->getOptions() as $key => $value) {
            if ($value !== null) {
                $values[$key] = $value;
            }
        }

        return new Config($values);
    }
}
