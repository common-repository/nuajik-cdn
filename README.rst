nuajik-wordpress
==================


Plugin to enable nuajik CDN on your Wordpress instance. 

This repo contain necessary file and reference dependencies to build a compatible Wordpress.org plugin.


Installation
--------------

- Install PHP and module dependencies:

.. code-block:: shell

  apt install php php-curl php-json

- Download and install ``composer.php`` from `official site <https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos>`_

- Install composer dependencies (we assumes you have composer.phar installed globally):

.. code-block:: shell

  cd nuajik-wordpress/ && composer install

Dev Guide
--------------

Before posting on wp.org, `validate your readme.txt on official WP validator <https://wordpress.org/plugins/developers/readme-validator/>`_

.. code-block:: shell

  ./sync.sh \
    --plugin-name="nuajik" \
    --git-repo="https://hexack.nuajik.io/nuajik/nuajik-wordpress.git" \
    --svn-user=nuajik
