# Magedbm2 - Magento Database 2.x Backup Manager

Looking for Magento 1.x support? Head over to [magedbm](https://github.com/meanbee/magedbm).

magedbm2 is a database backup manager for Magento 2.

It was written with developers in mind and provides commands for:

* Creating sanitised (or unsanitised) Magento 2 production database backups and uploading them to Amazon S3
* Downloading and importing existing backups to development or staging environments

## Compatibility

magedbm2 is compatible with PHP 7.0+ and requires `exec` and `passthru` functions to create and import database backups.

## Installation

Run the following commands to install the latest version of `magedbm2` globally:

    curl -LO https://s3.eu-west-2.amazonaws.com/magedbm2-releases/magedbm2.phar
    mv magedbm2.phar /usr/local/bin/magedbm2
    chmod +x /usr/local/bin/magedbm2

## Configuration

magedbm2 uses [Amazon S3](https://aws.amazon.com/s3/) to store database backups. To use it you will need to create a bucket on S3 and a user read and write access to that bucket. Note down your:

- AWS Access Key ID
- AWS Secret Access Key
- S3 region where your bucket is hosted
- The bucket name

These details will need to be specified with option flags to most magedbm2 commands.

### Remembering credentials

While the ability to specify the credentials as command line options is useful in automated scenarios, for personal everyday usage magedbm2 can be configured to remember your credentials by running:

    magedbm2 configure [--access-key="..."] [--secret-key="..."] [--region="..."] [--bucket="..."] [-n]

By default, the configure command will interactively prompt you for each of the configuration details. Alternatively, you can run it in a non-interactive mode with the `-n` option and specify the credentials you want to save by using the `--access-key`, `--secret-key`, `--region` or `--bucket` options.

The configuration is saved in a [YAML](http://www.yaml.org/) file, usually located in `~/.magedbm2/config.yml`.

## Usage

### Upload a database backup

    magedbm2 put [--strip="..."] [--clean=NUM|--no-clean] <project>

The put command will create a database backup from a Magento 2 installation at the current working directory (or the directory specified with `--root-dir`) and upload it to S3.

By default, the command uses [n98-magerun2](https://github.com/netz98/n98-magerun2) for database operations and creates backups with all customer data stripped out (the `@development` strip setting to the `n98-magerun2 db:dump` command). However, you can customise what data gets stripped, if any, using the `--strip` option.

The put command also automatically cleans up old database backups once a new one has been uploaded, keeping only the 5 most recent backup files. You can customise the number of retained backups with the `--clean` option, or disable it completely with `--no-clean`.

### List available backup files

    magedbm2 ls [<project>]

The ls command will show the backup files available for import in S3. If called without a project name, it will show the available projects instead.

### Import a database backup

    magedbm2 get [--download-only] [--force] get <project> [<file>]

Use the get command to download a backup file from S3 and import it to a Magento 2 installation at the current working directory (or the directory specified with `--root-dir`).

By default, the get command will choose the latest backup file available for the given project. You can import specific backup files by providing the name of the file after the project name.

To download the backup file without importing it, use the `--download-only` option. The file will be downloaded to the current working directory.

### Delete database backup files

    magedbm2 rm <project> <file>

The rm command allows you to delete specific backup files from S3.

## Development

### Contributing

While there is no formal contribution process, feel free to contribute by:

1. Creating issues, bug reports or feature requests on [Github](https://github.com/meanbee/magedbm2/issues)
2. Submitting pull requests for improvements

Pull requests should be submitted to the `develop` branch to be included in the next release.

### Requirements

Developing magedbm2 requires:
- PHP 7.0+
- [Phing](https://www.phing.info/)

### Installation

To install development dependencies for magedbm2, run:

    phing install

### Code style

magedbm2 follows PSR-2 for code style and a set of rules from [phpmd](https://phpmd.org/) to check for code problems.

You should run the following to check for any potential issues with the code before committing it:

    phing lint

### Testing

magedbm2 is tested with [PHPUnit](https://phpunit.de). 100% code coverage is not required, but all significant parts should have unit tests.

Make sure that the full suite of tests passes before committing changes by running:

    phing test

### Building

You can build the magedbm2 phar archive with [Box](https://github.com/box-project/box2) by running:

    phing build

### Releasing

To release a new version of magedbm2:

1. Build the phar archive
2. Upload `magedbm2.phar` and `magedbm2-<version>.phar` to the [magedbm2-releases](https://s3.eu-west-2.amazonaws.com/magedbm2-releases/) S3 bucket
3. Update the `manifest.json` file with the new version information and upload a copy of the file to the S3 bucket

    To calculate the sha1 checksum of the phar archive, run: `sha1sum -a 1 magedbm2.phar`

4. Create a release on [Github](https://github.com/meanbee/magedbm2/releases)
