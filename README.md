Glial
=====

[![Latest Stable Version](https://poser.pugx.org/glial/glial/v/stable.png)](https://packagist.org/packages/glial/glial) [![Total Downloads](https://poser.pugx.org/glial/glial/downloads.png)](https://packagist.org/packages/glial/glial) [![Latest Unstable Version](https://poser.pugx.org/glial/glial/v/unstable.png)](https://packagist.org/packages/glial/glial) [![License](https://poser.pugx.org/glial/glial/license.png)](https://packagist.org/packages/glial/glial)
[![Reference Status](https://www.versioneye.com/php/glial:glial/reference_badge.svg?style=flat)](https://www.versioneye.com/php/glial:glial/references)
[![Dependency Status](https://www.versioneye.com/php/glial:glial/dev-master/badge.png)](https://www.versioneye.com/php/glial:glial/dev-master)

http://www.glial-framework-php.org/

<h2>Requirement ?</h2>

1. Linux (degraded on windows with daemon and cli)
1. **PHP 5.5** or better
1. modrewrite of Apache
1. curl
2. mcrypt

<h2>How to install?</h2>


<h3>To create a new project</h3>
<a href="https://github.com/Esysteme/glial-new">Have a look on glial-new</a>

<h3>To install as a library</h3>

Simply add a dependency on glial/glial to your project's composer.json file if you use Composer to manage the dependencies of your project. Here is a minimal example of a composer.json file that just defines a development-time dependency on Glial 2.1:

```json
{
    "require": {
        "glial/glial": "3.1.*"
    }
}
```

<h2>Why use Glial?</h2>

<h3>Build application quickly</h3>
Many common components are included: translation tools, database access, code profiling, encryption, validation, and more.

<h3>Use specific libraries and tools</h3>
* Full support to PSR-0~4
* Extending existing components and adding new libraries is very easy.


<h3>Write commercial applications</h3>
Uses the GU/GPL license, so you can use and modify it for commercial purposes.

<h3>A very fast framework</h3>
Benchmarking a framework is hard and rarely reflects the real world, but Glial is very efficient and carefully optimized for real world usage.

A simple page with core system loaded we turn around between 400 and 800 query by second. 


<h3>Good debugging and profiling tools</h3>
* Simple and effective tools help identify and solve performance issues quickly.
* In dev mode you have some tools which show you the execution time for each module and memory used, same for each databases connected


<h3>Know what the framework is doing</h3>
Very well commented code and a simple routing structure makes it easy to understand what is happening.

<h3>Works with objects and classes</h3>
This is an OOP framework that is extremely DRY. Everything is built using strict PHP 5 classes and objects.

<h3>Write you own code</h3>
There are no code generators and endless configuration files, so setting up is fast and easy.


<h2>Who use Glial ?</h2>

* Acsediate
* BNPPARIBAS
* Société Générale (SGCIB)
* Arkadin
* Alstom (Transport)
* Photobox


<h2>User Guide</h2>

Glial don't work as most of framework worked actually.


I think it's the first framework to work in a navigator and in CLI in same time.
When I developed Glial, the goal was to make some stuff usable and worked fine, it's possibly that it's not nice by moment.

First you have to know if you miss somethings about spelling and stuff, All in Glial was in lower case and in singular.
Why ? Like that we don't have transform any string and waste time of execution.
Since we decided to follow the SPR-X, all class name and directory are now in StudyCaps



<h3>About model.</h3>

To construct the model, we read the database to build the model. It's mean we use some convention to make a good parsing. and must respect these points :


* all tables must have the first field called id, with a primary index not null autoincremented
* all fk, have to start by "id_" fallowed by the name of the table

if for one table we need to reference two FK on the same table we have to add a "__" double underscore followed by detailled name.

example : id_user__customer, id_user__provider

When we require to create a table to join 2 others you have to start the name of the table by "link" fallowed by "__" fallowed by the 2 tables to link.
the names have to be sort by alphabetique.

example for table user and mail_message

the name will be : link__mail_message__user



<h3>About controller</h3>

For the moment no custom route are supported.

One specifity of Glial, it's the controller/action can fit together, this option will be used natively with ajax, to load specificly only one area at once.
Each controller/action, will be checked by auth, to decide to display or not. More each controller/action have 2 options :
- read (allow access to all in read)
- write (allow access to all in create / update / delete)


<h3>About view</h3>

We decided to use PHP, and not an engine of template because nobody can be faster than PHP only, and the goal of Glial it's to focus on developement.
And not spend time to learn a new language even it's easy.




