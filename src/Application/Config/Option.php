<?php

namespace Meanbee\Magedbm2\Application\Config;

final class Option
{
    public const GLOBAL_CONFIG_FILE = 'config';
    public const PROJECT_CONFIG_FILE = 'project-config';

    public const ROOT_DIR = 'root-dir';

    public const DB_HOST = 'db-host';
    public const DB_NAME = 'db-name';
    public const DB_USER = 'db-user';
    public const DB_PASS = 'db-pass';
    public const DB_PORT = 'db-port';
    public const DB_SSL_CA = 'db-ssl-ca';

    public const YAML_DB_HOST = 'db_host';
    public const YAML_DB_NAME = 'db_name';
    public const YAML_DB_USER = 'db_user';
    public const YAML_DB_PASS = 'db_pass';
    public const YAML_DB_PORT = 'db_port';
    public const YAML_DB_SSL_CA = 'db_ssl_ca';

    public const TABLE_GROUPS = 'table-groups';

    public const TEMPORARY_DIR = 'tmp-dir';

    public const FORCE = 'force';
    public const DOWNLOAD_ONLY = 'download-only';
    public const STRIP = 'strip';

    public const CLEAN_COUNT = 'clean';
    public const NO_CLEAN = 'no-clean';

    public const STORAGE_SECRET_KEY = 'secret-key';
    public const STORAGE_ANONYMISED_BUCKET = 'anonymised-data-bucket';
    public const STORAGE_BUCKET = 'bucket';
    public const STORAGE_REGION = 'region';
    public const STORAGE_ANONYMISED_REGION = 'anonymised-region';
    public const STORAGE_ACCESS_KEY = 'access-key';

    public const YAML_STORAGE_SECRET_KEY = 'secret_key';
    public const YAML_STORAGE_ACCESS_KEY = 'access_key';
    public const YAML_ANONYMISED_BUCKET = 'anonymised_data_bucket';
    public const YAML_ANONYMISED_REGION = 'anonymised_region';

    /**
     * Options that a user is allowed to save in a configuration file.
     *
     * @return array
     */
    public static function allowUserToPersist()
    {
        return [
            self::YAML_DB_HOST,
            self::YAML_DB_NAME,
            self::YAML_DB_USER,
            self::YAML_DB_PASS,
            self::YAML_DB_PORT,
            self::YAML_DB_SSL_CA,

            self::YAML_STORAGE_ACCESS_KEY,
            self::YAML_STORAGE_SECRET_KEY,
            self::STORAGE_BUCKET,
            self::STORAGE_REGION,
            self::YAML_ANONYMISED_BUCKET,
            self::YAML_ANONYMISED_REGION
        ];
    }

    private function __construct()
    {
        // Don't allow instantiation.
    }

    public static function mapYamlOptionToConfigOption(string $yamlOption)
    {
        return str_replace('_', '-', $yamlOption);
    }
}
