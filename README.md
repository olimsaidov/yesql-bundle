Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require olimsaidov/yesql-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Ox\YesqlBundle\YesqlBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Configure the Bundle
-------------------------

Then, configure the bundle by adding the following lines
in the `app/config/config.yml` file of your project:

```yaml
yesql:
  connection: default # optional, doctrine custom connection name
  services:
    -
      path: "%kernel.root_dir%/../src/Acme/BlogBundle/Resources/blog.sql" # path to sql file
      name: "blog" # service name
```

Each query in your SQL file must be commented like this:

```sql
-- name: getAllPosts*
-- This will fetch all rows from posts
select * from posts;

-- name: getPostById
--
select * from posts where id = ?;

-- name: insertPost
-- You can use parametrized placeholder
insert into post (title, body) values (:title, :body);
```

Query name must end with ```*``` symbol to query multiple rows.

Step 4: Use the Bundle
-------------------------

Execute your queries by calling the service:

```php
$this->get('yesql.blog')->getAllPosts(); // returns all posts as array

$this->get('yesql.blog')->getPostById(3); // returns single post

$this->get('yesql.blog')->insertPost([':title' => 'Hello', ':body' => 'World']); // returns last insert id
```


