# Defining Table Groups

Often in your projects you'll have a list of tables that you want to strip as a group. You may be familiar with this concept in [magerun](https://github.com/netz98/n98-magerun) when passing `--strip=@development` to the `db:dump` command. Magedbm2 ships with a default set of strip table groups but you can add your by adding a new key under `table-groups` in your configuration, e.g.

    table-groups:
      - id: superpay
        description: Sensitive Super Pay tables
        tables: superpay_* super_logs superpay

You can then use that table group in your `put` command, `magedbm2 put --strip=@superpay myproject`.