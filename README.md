# acf-to-content
Add ACF field content to the post_content column

What is this plugin for?

ACF fields are not searched by the standard WP search. Basically, WP only
searches for words in the post_title and post_content columns. There are plugins available that
will let you search custom fields and there are examples of making WP search custom fields by
altering the query. These work great if you only have a few fields to search. Once you start needing
to search many fields or you need to start searching sub fields of repeaters or flex fields, then you
generally run into issues. Sub fields are difficult to search becasue of how the fields are stored in
the database. On top of this if you try to do `LIKE` queries on too many meta keys you'll just cause
the query to time out.

This plugin solves the problem in another way. Instead of searching all these fields individually it
puts the content of the fields into the post_content field so that it's available for searching without
needing any special code or tools to do the work. In addition, the content that this plugin is never
visible anywhere but in the database. On top of this, the content is added inside of a div with 
`display: none;` so that even if this plugin needs to be disabled for a bit this content should
still not be visible on the front end of the site or even in the admin by the average user if they
never look at the content in text mode.

Right now the only ACF field types that can be used for this are the text, textarea and wysiwyg field
types. More field types that require more complicated handling will likely be added in the future.

You must specify by setting the "To Content" setting of the fields when adding or editing them in ACF.

How does this work?

I want to expain how this works so that you can decide if you want to use this pluging or not. It uses
what I would refer to as the "Sledge Hammer Method." I have not tested this plugin on extremely large
sites yet, but I have tested the individual queries on a site and the data is listed at the end.

The "CORRECT" method would be something along the lines of:

1. Get all the field groups that are included for the page
2. Loop through all the field groups
3. Loop through all the fields in the field groups and recursively loop through sub field
to find all the fields that should be copied using ACF functions to get the values and have_rows()
loops to loop though repeaters, flex fields and clones
4. You would also need to look at any conditional logic for the field to determine if the value present
in the database is a value that is currently being used
5. collect all the content and update post_content for the post

The "CORRECT" way would likely take thousands of lines of code to build something generic enough that
could search all of the possible configurations of fields, repeaters, flex field and clones. This code
would be a nightmare to program and maintain. It will also likely time out the site trying to run.
*I've tried this method on sites that I've build, buiding the code in a way that exactly matched the
the field groups and field used on the site rather than build somthing generic and have run into timeout
almost every time I've done so after hours of coding. If a specific tool causes timeouts, a generic tool
would be more likely of causing timeouts*

This is the "Sledge Hammer Method"

**Before ACF saves values**

1. Using $wpdb query the _postmeta table for all rows matching where the `meta_value` matches the 
`field_key` of any field keys that are set to.
2. Using $wpdb, delete all of these `_postmeta` table rows where the meta key matches the keys used
by ACF to store your values and the field_key references *(This is only done if there have been changes
to ACF field content)*
3. Delete and refresh the post_meta cache for the post *(This is only done if there have been changes to
ACF field content. This is requried in order to force WP to update the DB with submitted values. While this causes the need for WP to run the queries needed to update the values, the number of queries done here is no more taxing than the number of queries neede to add an new post that contains all of the same data)*

*Steps 1, 2 & 3 ensure that any data that should not be included because of conditional logic or changes
in repeaters is removed so we don't need to worry about adding outdated content to post_content*

**After ACR saves values**

4. Using $wpdb get all of the content from all of the fields discovered in step 1 *(not including the
ACF field_key reference rows)*
5. Update post_content

*updating post_content is done on every save. The reason for this is that this plugin has filteres on
both `the_content` and `the_editor_content` that removes the content that was added to post_content
so that this is never content will never be seen by anyone editing or viewing the site.*

The method described above takes very few lines of code and with only a total net difference of 
+5 DB queries when ACF conetent is changed and +2 queries when ACF content is not changed.

I tested a query that is done on a site where 7 WYSIWYG fields in a repeater are used.
The query in step 1 took less 0.002 seconds to return 112 rows.
```
SELECT meta_key, meta_value 
FROM wp_2_postmeta 
WHERE post_id = "6768" 
  AND meta_value IN ("field_5525d5f2ad7a7", "field_5528687fa35b1", "field_55286b6d91f18", "field_55286ea4a9b21", "field_55288a5db841b", "field_55288f44bb74f", "field_552974dc4f00a")
```
Considering that the above query is using the meta_value field in the `WHERE` and this field is not
indexed, the query in step 2 should be faster since this uses the meta_key field and this field is 
indexed. The query in step 4 also uses meta_key and should be not slower. This means that, taking 
all things into account, on a site where a extreme amount of data is being used on an a 
large number of posts the update should be increased by less than 0.01 (1/100th) of a second.
Granded, this is not very accurate, but I don't really have any concerns about these queries slowing
down the admin of a large site.
