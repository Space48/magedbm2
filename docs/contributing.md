# Development

## Contributing

While there is no formal contribution process, feel free to contribute by:

1. Creating issues, bug reports or feature requests on [Github](https://github.com/space48/magedbm2/issues)
2. Submitting pull requests for improvements

Pull requests should be submitted to the `develop` branch to be included in the next release.

## Installation

To install development dependencies for magedbm2, run:

    make install

## Code style

magedbm2 follows PSR-2 for code style and a set of rules from [phpmd](https://phpmd.org/) to check for code problems.

You should run the following to check for any potential issues with the code before committing it:

    make lint
### Potential issues:
Issue 1: `Cannot declare class PHPCompatibility\Sniffs\Lists\NewKeyedListSniff, because the name is already in use` This will appear if you've contributed before the upgrade to 4.0.0 having had phpcs set up and your next contribution was after the upgrade to 4.0.0.

Fix: `./vendor/bin/phpcs --config-set installed_paths vendor/phpcompatibility/php-compatibility`

## Testing

magedbm2 is tested with [PHPUnit](https://phpunit.de). 100% code coverage is not required, but all significant parts should have unit tests.

Make sure that the full suite of tests passes before committing changes by running:

    make test

## Changelog

If applicable, update the `CHANGELOG.md` with your change, following the [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) standard.

## Building

You can build the magedbm2 phar archive with [Box](https://github.com/box-project/box2) by running:

    make build

## Releasing

To release a new version of magedbm2:

1. Build the phar archive
2. Update the `manifest.json` file with the new version information

    - Use `https://github.com/space48/magedbm2/releases/download/<version>/magedbm2.phar` as the URL
    
    - To calculate the sha1 checksum of the phar archive, run: `sha1sum magedbm2.phar`

3. Update the Installation instructions above with the new version
4. Create a release on [Github](https://github.com/space48/magedbm2/releases)
