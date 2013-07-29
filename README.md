Static Content Generator for Symfony HttpKernel
===============================================

This library provides tools for dumping the routes of a Symfony HttpKernel based application 
(Symfony2, Silex, etc.) to static html files.

Installation
------------

The easiest installation method is through Composer. Add the following lines to your composer.json:

    "require": {
        "salla/content-generator": "dev-master"
    }

Basic usage
-----------

    <?php

    $generator = new Generator($kernel, $urlGenerator, $routeCollection);
    $generator->addRoute('list', new EntityDataSource($posts, EntityDataSource::USE_METHODS));

    // or add multiple routes with:

    $generator->addRoutes(array(
        'list',
        'show',
        'category_list'
    ), new EntityDataSource($posts, EntityDataSource::USE_METHODS));

    // then write this into a directory:

    $generator->dump(__DIR__ . '/../www');

Data sources
------------

To generate all possible pages the generator relies on data sources. These can be arrays, anonymous functions or objects
implementing the provided DataSourceInterface. A data source must return an array of associative arrays, with the route
params as keys and data as values. For example:

    $dataSource = array(
        array('name' => 'Gyula'),
        array('name' => 'John'),
        array('name' => 'Matt'),
    ));

If you are using a closure or an object as a data source, the generator will pass a list variables expected by the
particurlar route.

    $generator->addRoute('post_list', function ($variables) {
        // ...
    });


### EntityDataSource ###

EntityDataSource matches route params with the public properties and/or public methods of objects.

    $flags = EntityDataSource::USE_METHODS | EntityDataSource::USE_PROPERTIES; // default setting

    $dataSource = new EntityDataSource($posts, $flags);

### DoctrineDataSource ###

DoctrineDataSource matches route params with the columns of a table, view or result set of an SQL query.

    $dataSource = new DoctrineDataSource($doctrine, 'post');

    $dataSource = new DoctrineDataSource($doctrine, 'SELECT * FROM post p WHERE p.is_published = 1');

If you wish to use a parameterized query you can do so by passing a Statement object to the constructor:

    $stmt = $doctrine->prepare('SELECT * FROM post p WHERE p.author = ?');
    $stmt->bindValue(1, 'Gyula');

    $dataSource = new DoctrineDataSource($doctrine, $stmt);


License
=======

Copyright (C) 2013 Sallai Gyula

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
