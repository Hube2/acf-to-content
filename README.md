# acf-to-content
Add ACF field content to the post_content column

What is this plugin for?

ACF fields are not searched by the standard WP search. Basically, WP only
searches for words in the post_title and post_content columns. There are plugins available that
will let you search custom fields and there are examples of making WP search custom fields by
altering the query. These work great if you only have a few fields to search. Once you start needing
to search many fields or you need to start searching sub fields of repeaters or flex fields, then you
generally run into issues. Sub fields are difficult to search because of how the fields are stored in
the database. On top of this if you try to do `LIKE` queries on too many meta fields you'll just cause
the query to time out.

This plugin solves the problem in another way. Instead of searching all these fields individually it
puts the content of the fields into the post_content field so that it's available for searching without
needing any special code or tools to do the work. In addition, the content that this plugin adds is never
visible anywhere but in the database. In addition the content is added inside of a div with 
`display: none;` so that even if this plugin needs to be disabled for a bit this content should
still not be visible on the front end of the site or even in the admin by the average user if they
never look at the content in text mode.

Right now the only ACF field types that can be used for this are the text, textarea and wysiwyg field
types. More field types that require more complicated handling will likely be added in the future as
I find they are needed and have the time to add them.

You must specify by setting the "To Content" setting of the fields when adding or editing them in ACF.

## How does it work
Every field that is saved by ACF triggers the action `acf/update_value`. This cause any value from a field to be stored.
When ACF is finsished saveing the post the `acf/save_post` action is triggerd. This triggers this plugin to add any stored 
values for the post to "post_content"

## Work with WP All Import ACF Add On
This plugin also includes all the neccessary integration to work with WP All Import and the ACF Add On