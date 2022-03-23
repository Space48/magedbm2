<?php

namespace Meanbee\Magedbm2\Application\Config;

final class Option
{
    const GLOBAL_CONFIG_FILE = 'config';
    const PROJECT_CONFIG_FILE = 'project-config';

    const ROOT_DIR = 'root-dir';

    const DB_HOST = 'db-host';
    const DB_NAME = 'db-name';
    const DB_USER = 'db-user';
    const DB_PASS = 'db-pass';
    const DB_PORT = 'db-port';

    const YAML_DB_HOST = 'db_host';
    const YAML_DB_NAME = 'db_name';
    const YAML_DB_USER = 'db_user';
    const YAML_DB_PASS = 'db_pass';
    const YAML_DB_PORT = 'db_port';

    const TABLE_GROUPS = 'table-groups';
    
    const TEMPORARY_DIR = 'tmp-dir';

    const FORCE = 'force';
    const DOWNLOAD_ONLY = 'download-only';
    const STRIP = 'strip';

    const CLEAN_COUNT = 'clean';
    const NO_CLEAN = 'no-clean';

    const STORAGE_SECRET_KEY = 'secret-key';
    const STORAGE_DATA_BUCKET = 'data-bucket';
    const STORAGE_BUCKET = 'bucket';
    const STORAGE_REGION = 'region';
    const STORAGE_ACCESS_KEY = 'access-key';

    const YAML_STORAGE_SECRET_KEY = 'secret_key';
    const YAML_STORAGE_ACCESS_KEY = 'access_key';

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

            self::YAML_STORAGE_ACCESS_KEY,
            self::YAML_STORAGE_SECRET_KEY,
            self::STORAGE_BUCKET,
            self::STORAGE_REGION
        ];
    }

    private function __construct()
    {
        // Don't allow instantiation.
    }

    public static function getYamlOptionMap()
    {
        return [
            'root_dir' => self::ROOT_DIR,
            self::YAML_DB_HOST => self::DB_HOST,
            self::YAML_DB_NAME => self::DB_NAME,
            self::YAML_DB_USER => self::DB_USER,
            self::YAML_DB_PASS => self::DB_PASS,
            self::YAML_DB_PORT => self::DB_PORT,
            'table_groups' => self::TABLE_GROUPS,
            'tmp_dir' => self::TEMPORARY_DIR,
            self::YAML_STORAGE_SECRET_KEY => self::STORAGE_SECRET_KEY,
            'data_bucket' => self::STORAGE_DATA_BUCKET,
            self::YAML_STORAGE_ACCESS_KEY => self::STORAGE_ACCESS_KEY,
        ];
    }
}
