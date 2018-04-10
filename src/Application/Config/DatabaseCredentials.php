<?php

namespace Meanbee\Magedbm2\Application\Config;

class DatabaseCredentials
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    public function __construct(
        string $name,
        string $username,
        string $password = null,
        string $host = 'localhost',
        string $port = '3306'
    ) {
        $this->name = $name;
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPort(): string
    {
        return $this->port;
    }

    /**
     * @return \PDO
     */
    public function createPDO(): \PDO
    {
        return new \PDO(
            sprintf(
                'mysql:dbname=%s;host=%s;port=%s',
                $this->getName(),
                $this->getHost(),
                $this->getPort()
            ),
            $this->getUsername(),
            $this->getPassword()
        );
    }
}
