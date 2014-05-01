Glial don't work as most of framework worked actually.


I think it's the first framework to work in a navigator and in CLI in same time.
When I developed Glial, the goal was to make some stuff usable and worked fine, it's possibly that it's not nice by moment.

First you have to know if you miss somethings about spelling and stuff, All in Glial was in lower case and in singular.
Why ? Like that we don't have transform any string and waste time of execution.
Since we decided to follow the SPR-X, all class name and directory are now in StudyCaps



About model.

To construct the model, we read the database to build the model. It's mean we use some convention to make a good parsing. and must respect these points :


* all tables must have the first field called id, with a primary index not null autoincremented
* all fk, have to start by "id_" fallowed by the name of the table

if for one table we need to reference two FK on the same table we have to add a "__" double underscore followed by detailled name.

example : id_user__customer, id_user__provider

When we require to create a table to join 2 others you have to start the name of the table by "link" fallowed by "__" fallowed by the 2 tables to link.
the names have to be sort by alphabetique.

example for table user and mail_message

the name will be : link__mail_message__user



About controller.

For the moment no custom route are supported.

One specifity of Glial, it's the controller/action can fit together, this option will be used natively with ajax, to load specificly only one area at once.
Each controller/action, will be checked by auth, to decide to display or not. More each controller/action have 2 options :
- read (allow access to all in read)
- write (allow access to all in create / update / delete)


About view.

We decided to use PHP, and not an engine of template because nobody can be faster than PHP only, and the goal of Glial it's to focus on developement.
And not spend time to learn a new language even it's easy.
