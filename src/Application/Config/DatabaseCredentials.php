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

    /**
     * @var string
     */
    private $sslCAPath;

    public function __construct(
        string $name,
        string $username,
        string $password = null,
        string $host = 'localhost',
        string $port = '3306',
        string $sslCAPath = null
    ) {
        $this->name = $name;
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
        $this->sslCAPath = $sslCAPath;
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

    public function getSSLCAPath(): ?string {
        return $this->sslCAPath;
    }

    /**
     * @return \PDO
     */
    public function createPDO(): \PDO
    {
        $options = array();
        if ($this->getSSLCAPath() !== null) {
            $options[\PDO::MYSQL_ATTR_SSL_CA] = $this->getSSLCAPath();
            $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        return new \PDO(
            sprintf(
                'mysql:dbname=%s;host=%s;port=%s;charset=utf8',
                $this->getName(),
                $this->getHost(),
                $this->getPort()
            ),
            $this->getUsername(),
            $this->getPassword(),
            $options
        );
    }
}
