<p align="center"><img src="https://laravel.com/assets/img/components/logo-laravel.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

# Test Tickets4Sale

## Installation
Fix file permissions:
```bash
$ sudo chmod -R 777 storage/*
$ sudo chmod -R 777 public/*
$ sudo chmod -R 777 bootstrap/*
$ php artisan storage:link
```

Install dependensies:
```bash
$ composer install
```

##User story #1
You can run CLI tool in console your server.

Run search query using data from CSV file:
```bash
$ php artisan inventory:status 2017-10-01 2017-12-15 shows.csv
```

Run search query using data from file path parameter:
```bash
$ php artisan inventory:status 2017-10-01 2017-12-15
```
In this case, CLI tool uses default CSV file specified in .env file. The same file is used by HTTP service.

File muet be upload in directory `/storage/app/public/`.
