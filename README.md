EasyAdmin Demo Application
==========================

This project is the official [EasyAdmin][1] Demo application that showcases the
main features of EasyAdmin, a popular admin generator for [Symfony][2] applications.

Requirements
------------

  * PHP 8.2 or higher;
  * PDO-SQLite PHP extension enabled;
  * and the [usual Symfony application requirements][2].

Installation
------------

Run this command with [Composer][4]:

```bash
$ composer create-project easyCorp/easyadmin-demo my_project
```

Usage
-----

There's no need to configure anything to run the application. If you have
[installed Symfony CLI][5], run this command:

```bash
$ cd my_project/
$ symfony serve
```

Then access the application in your browser at the given URL (<https://localhost:8000> by default).

If you don't have the Symfony binary installed, run `php -S localhost:8000 -t public/`
to use the built-in PHP web server or [configure a web server][6] to run the application.

Contributing
------------

This demo application is open source but it does not accept pull requests with
unsolicited features. If you have a feature idea, create an issue to discuss it
before implementing it. Pull requests that fix bugs are welcome.

License
-------

This demo application is published under the MIT license. See LICENSE.md for details.

[1]: https://github.com/EasyCorp/EasyAdminBundle/
[2]: https://symfony.com
[3]: https://github.com/symfony/demo
[4]: https://getcomposer.org/
[5]: https://symfony.com/download
[6]: https://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html
