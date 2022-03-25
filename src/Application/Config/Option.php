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
    const STORAGE_DATA_BUCKET = 'anonymised-data-bucket';
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
}
