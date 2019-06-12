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

    const STORAGE_ADAPTERS = 'storage-adapters';
    const SELECTED_STORAGE_ADAPTER = 'selected-storage-adapter';

    const TEMPORARY_DIR = 'tmp_dir';

    const FORCE = 'force';
    const DOWNLOAD_ONLY = 'download-only';
    const STRIP = 'strip';

    const CLEAN_COUNT = 'clean';
    const NO_CLEAN = 'no-clean';

    /**
     * Get the options that are allowed to be defined in the global configuration file.
     *
     * @return array
     */
    public static function getAllowedGlobalConfig(): array
    {
        return [
            self::TABLE_GROUPS,
            self::TEMPORARY_DIR,
            self::CLEAN_COUNT,
        ];
    }

    /**
     * Get the options that are allowed to be defined in the project configuration file.
     *
     * @return array
     */
    public static function getAllowedProjectConfig(): array
    {
        return [
            self::DB_HOST,
            self::DB_NAME,
            self::DB_USER,
            self::DB_PASS,
            self::DB_PORT,

            self::TABLE_GROUPS,

            self::TEMPORARY_DIR,
            self::STRIP,
            self::CLEAN_COUNT
        ];
    }

    private function __construct()
    {
        // Don't allow instantiation.
    }
}
