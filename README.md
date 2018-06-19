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

## Works with WP All Import ACF Add On
This plugin also includes all the neccessary integration to work with WP All Import and the ACF Add On

## Using in your own code

Before attempting to use create code to integrate this is some other way the first thing you need to understand is 
the process that you must use. The only field values that will be added to the post_content for searching are values 
currently being updated. This means that you must update all fields that need to be added to the post_content.

Let's look at an example. Suppose you have to text fields and you want both of these fields to be added to the post_content.
You must update both of these fields even if no value is being changed. So let's also say that for some reason you are
updating 1 of the fields dynamically in php but the other field is not being updated, in this case you must still update
the second field.

```
// php example
// the first text field you want to update
$value = 'set a value dynamically using any method you want';
update_field('field_12345678', $value, $post_id);

// now get the value from the other field and update it
$value = get_field('second_text_field', $post_id);
update_value('second_text_field', $post_id);
```
Calling `update_field()` triggers this plugin to add the value to the content that will be added to the post_content. 
Once you have updated all of the values then you must trigger this plugin to store that content. A special action hook 
has been added to this plugin for that purpose.
```
do_action('acf_to_content/save_post', $post_id);
```

*Updating values by hook*
You can also update values using a hook. Using this hook will not cause ACF to update the value and will instead cause
this plugin to add the value that will be added to the post_content.
`
$value = apply_filters($value, $post_id, $field_name);
`
$field_name can be the field key or the field name. Please not that the use of field key VS field name follows the same
rules as those for ACF. If the field does not already have a value then you must use the field key. Again, this does not 
cause ACF to actually update the value. This can be used to replace the code in the first examples
```
// the first text field you want to update
$value = 'set a value dynamically using any method you want';
update_field('field_12345678', $value, $post_id);

// now get the value from the other field and update it
$value = get_field('second_text_field', $post_id);
$value = apply_filters($value, $post_id, 'second_text_field');

// trigger the update
do_action('acf_to_content/save_post', $post_id);
```