# Magedbm2 - Magento 2.x Database Backup Manager

Looking for Magento 1.x support? Head over to [magedbm](https://github.com/meanbee/magedbm).

[![Build Status](https://travis-ci.org/space48/magedbm2.svg?branch=master)](https://travis-ci.org/space48/magedbm2)

magedbm2 is a database backup manager for Magento 2.

It was written with developers in mind and provides commands for:

* Creating sanitised (or unsanitised) Magento 2 production database backups and uploading them to Amazon S3
* Downloading and importing existing backups to development or staging environments
* Exporting and importing anonymised sensitive data tables

## Compatibility

magedbm2 requires PHP 7.0+ with permission to run the `exec` and `passthru` functions to create and import database backups.

## Installation

Download the Phar file from the [latest Github release](https://github.com/space48/magedbm2/releases/latest). Then run:

    mv magedbm2.phar /usr/local/bin/magedbm2
    chmod +x /usr/local/bin/magedbm2

## Configuration

magedbm2 uses [Amazon S3](https://aws.amazon.com/s3/) to store generated files. To use it you will need to create two buckets on S3 (one for database backups and another for data exports) and a user that has read and write access to the buckets. Note down your:

- AWS Access Key ID
- AWS Secret Access Key
- S3 region where your bucket is hosted
- The bucket names

These details will need to be specified with option flags to most `magedbm2` commands.

### Remembering credentials

While the ability to specify the credentials as command line options is useful in automated scenarios, for personal everyday usage magedbm2 can be configured to remember your credentials by running:

    magedbm2 configure [--access-key="..."] [--secret-key="..."] [--region="..."] [--bucket="..."] [--data-bucket="..."][-n]

By default, the configure command will interactively prompt you for each of the configuration details. Alternatively, you can run it in a non-interactive mode with the `-n` option and specify the credentials you want to save by using the `--access-key`, `--secret-key`, `--region`, `--bucket` or `--data-bucket` options.

The configuration is saved in a [YAML](http://www.yaml.org/) file, usually located in `~/.magedbm2/config.yml`.

### Using without a Magento Installation

It's not always possible to run `magedbm2` on the same server that a Magento installation is present, for example you might want to run a cron for `magedbm2` on your database server so that you don't clog the pipe to the application server with unnecessary backup traffic.

`magedbm2` normally discovers the database credentials by looking for the `app/etc/env.php` configuration file in a Magento project. If this is not found then `magedbm2` will fallback to the following options:

* `--db-host`
* `--db-port`
* `--db-user`
* `--db-pass`

### Defining strip table rules

Often in your projects you'll have a list of tables that you want to strip as a group. You may be familiar with this concept in [magerun](https://github.com/netz98/n98-magerun) when passing `--strip=@development` to the `db:dump` command. Magedbm ships with a default set of strip table groups but you can add your by adding a new key under `table-groups` in your configuration, e.g.

    table-groups:
      - id: superpay
        description: Sensitive Super Pay tables
        tables: superpay_* super_logs superpay

You can then use that table group in your `put` command, `magedbm2 put --strip=@superpay myproject`.

### Defining anonymisation rules

As well as stripping certain tables in your projects, you might also have project-specific columns containing sensitive data that you do not want exported. To do this you need to define which formatter to apply to the column.

Depending on the type of entity that you want to anonymise you'll either need to use the `tables` or the `eav` key in the configuration.  Assuming that we've added a new table called `super_pay` with two columns that need anonymisation and we've added a new attribute to customers.

    anonymizer:
      tables:
        - name: super_pay
          columns:
            customer_card_number: Faker\Provider\Payment::creditCardNumber
            receipt_email: Faker\Provider\Internet::email
      eav:
        - entity: customer
          attributes:
            super_pay_tracking_id: Meanbee\Magedbm2\Anonymizer\Formatter\Rot13 # lol.

You have access to the [Faker](https://github.com/fzaninotto/Faker) library.

The format of the formatter string is either `${className}` if the class implements `Meanbee\Magedbm2\Anonymizer\FormatterInterface` or `${className}::${method}` if you want to call a method directly.

## Usage

### Upload a database backup

    magedbm2 put [--strip="..."] [--clean=NUM|--no-clean] <project>

The `put` command will create a database backup from a Magento 2 installation at the current working directory (or the directory specified with `--root-dir`) and upload it to S3.

By default, the command uses [n98-magerun2](https://github.com/netz98/n98-magerun2) for database operations and creates backups with all customer data stripped out (the `@development` strip setting to the `n98-magerun2 db:dump` command). However, you can customise what data gets stripped, if any, using the `--strip` option.

The `put` command also automatically cleans up old database backups once a new one has been uploaded, keeping only the 5 most recent backup files. You can customise the number of retained backups with the `--clean` option, or disable it completely with `--no-clean`.

### List available backup files

    magedbm2 ls [<project>]

The `ls` command will show the backup files available for import in S3. If called without a project name, it will show the available projects instead.

### Import a database backup

    magedbm2 get [--download-only] [--force] get <project> [<file>]

Use the `get` command to download a backup file from S3 and import it to a Magento 2 installation at the current working directory (or the directory specified with `--root-dir`).

By default, the `get` command will choose the latest backup file available for the given project. You can import specific backup files by providing the name of the file after the project name.

To download the backup file without importing it, use the `--download-only` option. The file will be downloaded to the current working directory.

### Delete database backup files

    magedbm2 rm <project> <file>

The `rm` command allows you to delete specific backup files from S3.

### Exporting sensitive data

    magedbm2 export <project>

The `export` command allows you generate an anonymised export of your database, based on your anonymisation rules.

### Importing sensitive data

    magedbm2 import [--no-progress] [--download-only] <project> [<file>]

The `import` command allows you to import an anonymised export of your database.

## Development

### Contributing

While there is no formal contribution process, feel free to contribute by:

1. Creating issues, bug reports or feature requests on [Github](https://github.com/space48/magedbm2/issues)
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
2. Update the `manifest.json` file with the new version information

    - Use `https://github.com/space48/magedbm2/releases/download/<version>/magedbm2.phar` as the URL
    
    - To calculate the sha1 checksum of the phar archive, run: `sha1sum magedbm2.phar`

3. Update the Installation instructions above with the new version
4. Create a release on [Github](https://github.com/space48/magedbm2/releases)
