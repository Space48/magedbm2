<?php

namespace Meanbee\Magedbm2\Service\Anonymiser\Export;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;

abstract class RowProcessor
{
    /** @var array */
    private $formatterCache = [];

    /** @var \Faker\Generator */
    private $faker;

    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }

    /**
     * @param Row $row
     * @return string
     */
    abstract public function process(Row $row);

    /**
     * @param $name
     * @param $value
     * @return string
     */
    protected function renderColumn($name, $value)
    {
        if ($value === null) {
            return sprintf("\t\t\t" . '<column name="%s" xsi_type="nil" />' . "\n", $name, $value);
        }

        if ($this->isCdataNeeded($value)) {
            $value = '<![CDATA[' . $value . ']]>';
        }

        return sprintf("\t\t\t" . '<column name="%s">%s</column>' . "\n", $name, $value);
    }

    /**
     * @param $content
     * @return bool
     */
    private function isCdataNeeded($content)
    {
        if (false !== strpos($content, '<')) {
            return true;
        }

        if (false !== strpos($content, '>')) {
            return true;
        }

        return false;
    }

    /**
     * Run a formatter against a value and return the formatted value.
     *
     * @param $value
     * @param $formatterSpec
     * @return mixed
     */
    protected function runFormatter($value, $formatterSpec)
    {
        if ($value === null) {
            return null;
        }

        if ($formatterSpec === null) {
            return $value;
        }

        if (!array_key_exists($formatterSpec, $this->formatterCache)) {
            $class = null;
            $method = null;

            if (strpos($formatterSpec, '::')) {
                list($class, $method) = explode('::', $formatterSpec);
            } else {
                $class = $formatterSpec;
            }

            if (!class_exists($class)) {
                throw new \RuntimeException(sprintf("Formatter class %s does not exist", $class));
            }

            if (in_array('Faker\Provider\Base', class_parents($class), true)) {
                $instance = new $class($this->faker);
            } else {
                $instance = new $class;
            }

            if ($method) {
                $this->formatterCache[$formatterSpec] = function ($value) use ($instance, $method) {
                    return $instance->$method();
                };
            } elseif ($instance instanceof FormatterInterface) {
                $this->formatterCache[$formatterSpec] = function ($value) use ($instance) {
                    return $instance->format($value, []);
                };
            } else {
                throw new \RuntimeException("Unable to process formatter spec: $formatterSpec");
            }
        }

        return $this->formatterCache[$formatterSpec]($value);
    }
}
