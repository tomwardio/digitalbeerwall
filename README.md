# Digital Beer Wall

A [Symfony 7](http://www.symfony.com) based website for bottled beer. Originally created in 2015 using Symfony 2, this hobby site has been updated to work with the newest versions of various packages, and given a (very slight) makeover to include light/dark themes with bootstrap 5.

Below are instructions on how to setup the site on a new machine for development.

# Installation

## Requirements

* [Symfony CLI](https://symfony.com/download)
* [PHP 8.2](http://php.net) or newer.
* [MariaDB](https://mariadb.org/) (or MySQL) for the site's database
* [Composer](https://getcomposer.org/download/) to install PHP packages.

## Setup Database

First let's setup the database required to run the site and a user to access it.

```
$ sudo mysql -e "CREATE DATABASE digitalbeerwall;"
$ sudo mysql -e "CREATE USER 'digitalbeerwall'@'localhost' IDENTIFIED BY 'my_password';"
$ sudo mysql -e "GRANT ALL PRIVILEGES ON digitalbeerwall.* TO 'digitalbeerwall'@'localhost';"
```

These commands will setup a new database called `digitalbeerwall`, and a user called `digitalbeerwall` with password `my_password`, and is only accessible locally.

## Setup the website

Next, clone the digitalbeerwall repository and run composer to install the requisite packages.

```
$ git clone https://github.com/tomwardio/digitalbeerwall
$ cd digitalbeerwall
$ composer install
$ symfony check:requirements
```

The final command will check that we meet all the requirements necessary to run the site.

## Configure the site

With the site and database setup, now we need to configure the site to connect to this database. Copy the `.env` file to `.env.local` and open in your favorite text editor.

The first entry we need to modify is the `DATABASE_URL`. If you setup the database as described above, you don't need to do anything, otherwise modify the constituent parts to match your setup.

```
DATABASE_URL='mysql://digitalbeerwall:my_password@localhost:3306/digitalbeerwall'
```

Next, we need to create the necessary tables to run the site. From the site root, run:

```
$ symfony console doctrine:schema:create
```

Finally, run the following to start running the site on http://127.0.0.1:8000

```
$ symfony server:start
```

## Email configuration (optional)

In order to send emails for user account activation, you'll need to setup a mailer. See the [mailer docs](https://symfony.com/doc/current/mailer.html) for more details. I've used [AWS SES](https://aws.amazon.com/ses/) without too much issue.

## AWS S3 configuration (optional)

To store beer images on AWS rather than locally, set the following in your `.env.local` config file:

```
AWS_S3_ENABLED=true
AWS_S3_BUCKET='my_bucket'
AWS_S3_REGION='eu-west-1'
AWS_S3_ACCESS_KEY=''
AWS_S3_ACCESS_SECRET=''
```

## Mapbox configuration (optional)

To setup the mapbox world tiles, set the following in your `.env.local` file:

```
MAPBOX_TOKEN='<token>'
```

## reCaptcha (optional)

To prevent bots from attempting to create lots of accounts, you can optionally enable reCAPTCHA. To do this, set the following in your `.env.local` file:

```
GOOGLE_RECAPTCHA_ENABLED=true
GOOGLE_RECAPTCHA_SITE_KEY='my_site_key'
GOOGLE_RECAPTCHA_SECRET='my_secret'
```
