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

    /**
     * Options that a user is allowed to save in a configuration file.
     *
     * @return array
     */
    public static function allowUserToPersist()
    {
        return [
            self::DB_HOST,
            self::DB_NAME,
            self::DB_USER,
            self::DB_PASS,
            self::DB_PORT,

            self::STORAGE_ACCESS_KEY,
            self::STORAGE_SECRET_KEY,
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
            'db_host' => self::DB_HOST,
            'db_name' => self::DB_NAME,
            'db_user' => self::DB_USER,
            'db_pass' => self::DB_PASS,
            'db_port' => self::DB_PORT,
            'table_groups' => self::TABLE_GROUPS,
            'tmp_dir' => self::TEMPORARY_DIR,
            'secret_key' => self::STORAGE_SECRET_KEY,
            'data_bucket' => self::STORAGE_DATA_BUCKET,
            'access_key' => self::STORAGE_ACCESS_KEY,
        ];
    }
}
