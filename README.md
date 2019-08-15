# ACF to Content
Add ACF field content to the post_content column for easier and more efficiient searching of ACF fields.

## What is this plugin for?

ACF fields are not searched by the standard WP search. Basically, WP only
searches for words in the post_title and post_content columns. There are plugins available that
will let you search custom fields and there are examples of making WP search custom fields by
altering the query. These work great if you only have a few fields to search. Once you start needing
to search many fields or you need to start searching sub fields of repeaters, flex fields, or other types 
of nested and non standard fields then you generally run into issues. Sub fields and fields that are stored 
as arrays are difficult to search because of how the fields are stored in the database. On top of this if you 
try to do `LIKE` queries on too many meta fields you'll just cause the query to time out.

This plugin solves the problem in another way. Instead of searching all these fields individually it
puts the content of the fields into the post_content column in the DB so that it's available for searching without
needing any special code or tools to do the work. In addition, the content that this plugin adds is never
visible anywhere but in the database. On top of this the content is added inside of a div with 
`display: none;` so that even if this plugin needs to be disabled for a bit this content should
still not be visible on the front end of the site or even in the admin by the average user if they
never look at the content in text mode.

There is on instance when this content will be visible and that is when displaying an automatically
generated post excerpt. The reason for this is that I do not use "post_content" for anything at this point.
Do to the new block editor (~~guberbug~~gutenberg) I have decided to replace all WP editor fields, both classic and block editor, 
with ACF WYSIWYG fields. This means that the only thing that is in "post_content" are ACF field values that
I've chosen to put there. This causes and issue when displaying excerpts on pages like the search results
page because the content really contains nothing. It is a good idea to explain the importance of creating
good post excerpts to clients, but showing ACF content that is added is better than showing nothing at all.

## Field Types Included

* Text Based Fields
  * Text
  * Text Area
  * Number
  * Range
  * Wysiwyg Editor
* Choice Fields: allow you to choose to store the label, value, or both for any selected choice
  * Select
  * Checkbox
  * Radio Button
  * Radio Group
* Taxonomy Field: allows term name, slug and descirption. Can choose multiple, defaults to term name.

You must specify by setting the "To Content" setting of the fields when adding or editing them in ACF.

## How does it work
Every field that is saved by ACF triggers the action `acf/update_value`. This causes any value from a field 
with the "To Content" setting on to be stored into comment delimeted block along with the post content. When 
ACF is finsished saveing the post the `acf/save_post` action is triggerd. This triggers this plugin to add any 
stored values for the post to "post_content"

## Works with WP All Import ACF Add On
This plugin also includes all the neccessary integration to work with WP All Import and the ACF Add On. The reason 
this is included is that this is the plugin that I use for imports. To integrate this with other plugins that can 
update ACF field values see the next section.

## Integration w/Other plugins

I have added filters to allow you to integrate this with other plugins that create posts 
or update ACF field values.

Before attempting to create code to integrate this in some other way the first thing you need to understand is 
the process that you must use. The only field values that will be added to the post_content for searching are 
values currently being updated by ACF. This means that you must update all fields that need to be added to the 
post_content.

Let's look at an example. Suppose you have two text fields and you want both of these fields to be added 
to the post_content. You must update both of these fields even if no value is being changed. So let's also 
say that for some reason you are updating 1 of the fields dynamically in php but the other field is not being 
updated, in this case you must still update the second field.

```
// php example
// the first text field you want to update
// this is the field you are modifying
$value = 'set a value dynamically using any method you want';
update_field('field_12345678', $value, $post_id);

// now get the value from the other field and update it
// this method uses ACF to update the value
$value = get_field('second_text_field', $post_id);
update_value('second_text_field', $post_id);
```
In the above example, calling `update_field()` tells ACF to update the value and this triggers this plugin to 
add the value to the content that will be added to the post_content. Once you have updated all of the values then 
you must trigger this plugin to store that content. A special action hook has been added to this plugin for this 
purpose.
```
do_action('acf_to_content/save_post', $post_id);
```

*Updating values by hook*

You can also update values using a hook. Using this hook will not cause ACF to update the value and will 
instead cause this plugin to add the value that will be added to the post_content. Basically it's just telling 
this pluging that a value needs to be stored
```
$value = apply_filters('acf_to_content/update_value', $value, $post_id, $field_name);
```

$field_name can be the field key or the field name. *Please note that the use of field key VS field name follows 
the same rules as those for ACF.* If the field does not already have a value then you must use the field key. 
Again, this does not cause ACF to actually update the value. This can be used to replace the code in the first example.
```
// the first text field you want to update
$value = 'set a value dynamically using any method you want';
update_field('field_12345678', $value, $post_id);

// now get the value from the other field and update it
$value = get_field('second_text_field', $post_id);
$value = apply_filters('acf_to_content/update_value', $value, $post_id, 'second_text_field');

// trigger the update
do_action('acf_to_content/save_post', $post_id);
```

## Custom filtering of any field

This plugin does not manage all field types as yet. However, you can make it store any value to the_content 
by building your own filter for any field. You can filter all fields, by field type, field key or field name. 
Here are the hooks:

* all fields: `"acf_to_content/custom_process"`
* by field type: `"acf_to_content/custom_process/type=$field['type']"`
* by field name: `"acf_to_content/custom_process/name=.$field['name']"`
* by field key: `"acf_to_content/custom_process/key='.$field['key']"`

The arguments supplied are
* $to_content: The (STRING) value that you want to insert into the_content (default value is `false`)
* $value: The original value(s) of the ACF field being saved
* $post_id: The post ID of the post being saved
* $field: The ACF field array for the current field

If you return any string value from this filter in `$to_content` this will cause your value to be stored and 
it will also cause this plugin to not further filter the value. In other words, your filter will completely 
override any filters built into this plugin, now and in the future.

The following is an example for all fields
```
add_filter('acf_to_content/custom_process', 'my_to_content_process', 10, 4);
function ($to_content, $value, $post_id, $field) {
  /*
    do whatever you want to set the value of $to_content and return it
  */
  return $to_content;
}
```
