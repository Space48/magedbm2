# Magedbm2 - A Database Backup Manager

Magedbm2 is a database backup manager designed to make the process of taking backups from one environment and moving them to another, easy. The backups can be stripped of sensitive tables and can even generate anonymised versions of the sensitive data. While it was originally developed to for Magento 2.x it can be used for any system.

It was written with developers in mind and provides commands for:

* Creating sanitised (or unsanitised) production database backups and uploading them to Amazon S3
* Downloading and importing existing backups to development or staging environments
* Exporting and importing anonymised sensitive data tables

## Requirements

* `<v4.0.0` PHP >7.0 <8.0(with permission to run `exec` and `passthru`)
* `>v4.0.0` PHP 7.4+
* `mysql` client on the `$PATH`

## Installation

Download the Phar file from the [latest Github release](https://github.com/space48/magedbm2/releases/latest). Then run:

    mv magedbm2.phar /usr/local/bin/magedbm2
    chmod +x /usr/local/bin/magedbm2

## Configuration

The configuration can either be provided through configuration files or as options when executing a command. For more information on the configuration files, see [Using Multiple Configuration Files](docs/multiple-configuration-files.md).

The basic configuration parameters required are the credentials for connecting to [Amazon S3](https://aws.amazon.com/s3/) and which buckets to store the different types of exports in.

* `access_key`(`--access-key`): Your AWS Access Key
* `secret_key`(`--secret-key`): Your AWS Secret Key
* `region`: The region in which the S3 buckets are located
* `bucket`: The bucket to store the database backups
* `anonymised_data_bucket`(`--anonymised-data-bucket`): The bucket to store the anonymised exports

### Using without a Magento Installation

It's not always possible to run Magedbm2 on the same server that a Magento installation is present, for example you might want to run a cron for Magedbm2 on your database server so that you don't clog the pipe to the application server with unnecessary export traffic.

Magedbm2 normally discovers the database credentials by looking for the `app/etc/env.php` (or `app/etc/local.xml` for Magento 1.x) configuration file in a Magento project. If this is not found then Magedbm2 will fallback to the following options:

* `--db-host`
* `--db-port`
* `--db-user`
* `--db-pass`
* `--db-name`

This gives us the ability to use Magedbm2 without Magento at all, should we want to.

### Writing Configuration Files

While the ability to specify the credentials as command line options is useful in automated scenarios, for personal everyday usage Magedbm2 can be configured to remember your credentials by running:

    magedbm2 configure [--access-key="..."] [--secret-key="..."] [--region="..."] [--bucket="..."] [--anonymised-data-bucket="..."] [--db-host="..."] [--db-user="..."] [--db-pass="..."] [--db-port="..."] [--db-name="..."][-n] <file-name>

By default, the configure command will interactively prompt you for each of the configuration details. Alternatively, you can run it in a non-interactive mode with the `-n` option and specify the credentials you want to save using the options.

The configuration is saved in a [YAML](http://www.yaml.org/) file determined by the `file-name` argument. If running in interactive mode you will be prompted with appropriate file locations.

### Additional Configuration

There are some advanced configuration options that you may be interested in, such as:

* [Defining Table Groups for Stripping](docs/table-groups.md) 
* [Defining Anonymisation Rules](docs/anonymisation-rules.md)

## Usage

### Upload a database backup

    magedbm2 put [--strip="@development"] [--clean=NUM|--no-clean] <project>

The `put` command will create a database backup and upload it to S3. If no database parameters are passed through on the command line, Magento will be assumed, and the database details will attempted to be loaded from the current working directory, or the directory specified by `--root-dir`.

By default, the tables defined in the `@development` table group (which can be seen by running `magedbm2 put --help`) will be stripped. Additional tables or table groups can be added to the `--strip` option, separated by spaces, e.g. `--strip="@log @sessions customtable customprefix*"`.

The `put` command also automatically cleans up old database backups once a new one has been uploaded, keeping only the 5 most recent backup files. You can customise the number of retained backups with the `--clean` option, or disable it completely with `--no-clean`.

### List available backup files

    magedbm2 ls [<project>]

The `ls` command will show the backup files available for import in S3. If called without a project name, it will show the available projects instead.

### Import a database backup

    magedbm2 get [--download-only] [--force] get <project> [<file>]

Use the `get` command to download a backup file from S3 and import it into the defined database. If no database parameters are passed through on the command line, Magento will be assumed, and the database details will attempted to be loaded from the current working directory, or the directory specified by `--root-dir`.

By default, the `get` command will choose the latest backup file available for the given project. You can import specific backup files by providing the name of the file after the project name.

To download the backup file without importing it, use the `--download-only` option. The file will be downloaded to the current working directory.

### Delete database backup files

    magedbm2 rm <project> <file>

The `rm` command allows you to delete specific backup files from S3.

### Exporting sensitive data

    magedbm2 export <project>

Requires version >= 2.0.0. The `export` command allows you generate an anonymised export of your database, based on your anonymisation rules.

### Importing sensitive data

    magedbm2 import [--no-progress] [--download-only] <project> [<file>]

Requires version >= 2.0.0. The `import` command allows you to import an anonymised export of your database.

### Viewing Configuration

    magedbm2 view-config
    
Requires version > 2.0.2. The `view-config` command will show you the result of the merged configuration files for debugging.

# Contributing

For information on how to contribute to the project, see:

* [Contributing](docs/contributing.md)
