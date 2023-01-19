# Quickstart

This repository contains quickstart commands to have the basics for a website up
and running with a single command.

## Installation

```
composer require jelle-s/quickstart
```

## Configuration

Following environment variables can/should be configured:

- `QUICKSTART_WEBSITES_DIR`: The directory where your websites/projects live,
defaults to `getenv('HOME') . '/websites'`
- `QUICKSTART_DATABASE_USER`: The database user to use to create the other
databases and users, defaults to 'root'
- `QUICKSTART_DATABASE_PASSWORD`: The database password of the configured user
above, defaults to ''
- `QUICKSTART_DATABASE_HOST`: The database host, defaults to 'localhost'
- `QUICKSTART_DATABASE_DRIVER`: The database driver to use, defaults to
'pdo_mysql', see
https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/configuration.html#driver

## Commands

### Create

```
vendor/bin/quickstart create [--dns --apache --database] myproject.local
```

```
Description:
  Create a database and dns and apache configuration for a domain.

Usage:
  create [options] [--] <domain>

Arguments:
  domain

Options:
      --dns             Add dns configuration to /etc/hosts
      --apache          Create an apache virtualhost
      --database        Create a database and database user
```

The database and database user is the domain name with `.` replaced by `_`.

### Destroy

```
vendor/bin/quickstart destroy [--dns --apache --database] myproject.local
```

```
Description:
  Destroy a database and dns and apache configuration for a domain.

Usage:
  destroy [options] [--] <domain>

Arguments:
  domain

Options:
      --dns             Remove dns configuration from /etc/hosts
      --apache          Remove the apache virtualhost
      --database        Remove the database and database user
```

The database and database user is the domain name with `.` replaced by `_`.
