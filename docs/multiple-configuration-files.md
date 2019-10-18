# Multiple Configuration Files

Magedbm2 looks for configuration files in a number of places before merging them all together to build a final configuration structure. This allows developers to store various pieces of information in appropriate places. The credentials for AWS, for example, should be unique to each developer and so exist as a file on each developer's machine. The bucket and region to store the database backups in though should travel around with the code and be committed to the repository.

The load order of the configuration files are as follows:

* **Dist**: Configuration distributed inside of the phar
  * This is the base configuration that contains the majority of the default configuration such as the standard table groups. It can be found at `etc/config.yml`, in the root of the repository. 
* **User-specific**: User's configuration file
  * This is a configuration file that could contain things like a user's AWS credentials and will be looked for in the user's home directory at `~/.magedbm2/config.yml`.
  * The location of this file can be overwritten by passing a file name with the `--config` option.
* **Project-specific**: Project configuration file
  * This is the configuration file that could contain the project specific configuration that will be shipped around to each developer and environment by virtue of it living in the project repository. This will be loaded from `.magedbm2.yml` in the current working directory.
  * The location of this file can be overwritten by passing a file name with the `--project-config` option.
* Any command line options.
