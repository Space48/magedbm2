# Changelog

All notable changes will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.1] - 2020-12-11

### Fixed

- The `s_core_customergroups*` tables are no longer removed from when using the `platform_shopware` table group.

### Changed

- Additional tables that are not required for local development environments have been added to `platform_shopware` to minimmise database sizes.

## [3.0.0] - 2020-01-17

### Added

- New command `view-config` to dump the current YAML configuration to stdout.
- Added `platform_shopware` and `platform_magento_one` table groups because we're using this for more than just Magento 2.x!

### Changed

- **BREAKING**: Renamed `tmp_dir` configuration option to `tmp-dir`.
- **BREAKING**: All MySQL interactions now use `utf-8` as the default charset, potentially making backups created with an older versions unimportable. Use `--download-only` and import manually if you run into this or update on your server and `put` a new database backup.
- The `configure` command now presents the user will files to edit and a preview of the changes before writing to file.
- Updated table groups from [netz98/n98-magerun2](https://github.com/netz98/n98-magerun2/blob/3260cab7770e80b8db66c996d50d60b7ef76774c/config.yaml) for Magento 2.x.

### Fixed

- The `--project-config` option is now respected and the configuration file is loaded at the correct time.
- The merging of indexed based configuration items (like adding new table groups) is now fixed.

## [2.0.2] - 2018-09-08

### Fixed

- The put command no longer performs Magento 2 discovery if the required database options are specified as command-line parameters.
- The admin table strip group no longer removes administrator roles, only users.

## [2.0.1] - 2018-04-17

### Changed

- Strip `DEFINER` from `CREATE TRIGGER` statements.
- Don't allow blank `--db-*` options.

## [2.0.0] - 2018-04-12

### Added

- Support to `export` and `import` anonymised data.
- Project level configuration so that you can include a `.magedbm2.yml` in your repository.

### Changed

- No longer requires magerun

## [1.0.0] - 2017-06-08

### Added

- Initial release.
