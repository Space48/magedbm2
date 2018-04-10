<?php

namespace Meanbee\Magedbm2\Service\Anonymiser\Export;

class Row
{
    /**
     * @var \SimpleXMLElement
     */
    private $xml;

    /**
     * @var string
     */
    public $table;

    /**
     * @var bool
     */
    private $processed = false;

    /**
     * @var array
     */
    private $fields = [];

    public function __construct(\SimpleXMLElement $xml = null)
    {
        $this->xml = $xml;
    }

    /**
     * @return array
     */
    public function all()
    {
        $this->process();

        return $this->fields;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function get($name)
    {
        $this->process();

        if (!isset($this->fields[$name])) {
            return null;
        }

        return $this->fields[$name];
    }

    /**
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        // Process first to ensure that we overwrite any data thats there, avoiding the potential that our data will be
        // overwritten on the first read.
        $this->process();

        $this->fields[$name] = $value;
    }

    private function process()
    {
        if (!($this->xml instanceof \SimpleXMLElement)) {
            return;
        }

        if ($this->processed) {
            return;
        }

        foreach ($this->xml->field as $field) {
            $name = (string)$field['name'];
            $value = (string)$field;
            $null = (string)$field['xsi_nil'];

            $this->fields[$name] = ($null === 'true') ? null : $value;
        }

        $this->processed = true;
    }

    /**
     * @param $xmlString
     * @return Row
     */
    public static function fromString($xmlString): Row
    {
        $xmlString = str_replace('xsi:', 'xsi_', $xmlString);
        $xml = new \SimpleXMLElement($xmlString);

        return new Row($xml);
    }
}
