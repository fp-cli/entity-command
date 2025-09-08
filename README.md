fp-cli/entity-command
=====================

Manage FinPress comments, menus, options, posts, sites, terms, and users.

[![Testing](https://github.com/fp-cli/entity-command/actions/workflows/testing.yml/badge.svg)](https://github.com/fp-cli/entity-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### fp comment

Creates, updates, deletes, and moderates comments.

~~~
fp comment
~~~

**EXAMPLES**

    # Create a new comment.
    $ fp comment create --comment_post_ID=15 --comment_content="hello blog" --comment_author="fp-cli"
    Success: Created comment 932.

    # Update an existing comment.
    $ fp comment update 123 --comment_author='That Guy'
    Success: Updated comment 123.

    # Delete an existing comment.
    $ fp comment delete 1337 --force
    Success: Deleted comment 1337.

    # Trash all spam comments.
    $ fp comment delete $(fp comment list --status=spam --format=ids)
    Success: Trashed comment 264.
    Success: Trashed comment 262.



### fp comment approve

Approves a comment.

~~~
fp comment approve <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to approve.

**EXAMPLES**

    # Approve comment.
    $ fp comment approve 1337
    Success: Approved comment 1337.



### fp comment count

Counts comments, on whole blog or on a given post.

~~~
fp comment count [<post-id>]
~~~

**OPTIONS**

	[<post-id>]
		The ID of the post to count comments in.

**EXAMPLES**

    # Count comments on whole blog.
    $ fp comment count
    approved:        33
    spam:            3
    trash:           1
    post-trashed:    0
    all:             34
    moderated:       1
    total_comments:  37

    # Count comments in a post.
    $ fp comment count 42
    approved:        19
    spam:            0
    trash:           0
    post-trashed:    0
    all:             19
    moderated:       0
    total_comments:  19



### fp comment create

Creates a new comment.

~~~
fp comment create [--<field>=<value>] [--porcelain]
~~~

**OPTIONS**

	[--<field>=<value>]
		Associative args for the new comment. See fp_insert_comment().

	[--porcelain]
		Output just the new comment id.

**EXAMPLES**

    # Create comment.
    $ fp comment create --comment_post_ID=15 --comment_content="hello blog" --comment_author="fp-cli"
    Success: Created comment 932.



### fp comment delete

Deletes a comment.

~~~
fp comment delete <id>... [--force]
~~~

**OPTIONS**

	<id>...
		One or more IDs of comments to delete.

	[--force]
		Skip the trash bin.

**EXAMPLES**

    # Delete comment.
    $ fp comment delete 1337 --force
    Success: Deleted comment 1337.

    # Delete multiple comments.
    $ fp comment delete 1337 2341 --force
    Success: Deleted comment 1337.
    Success: Deleted comment 2341.



### fp comment exists

Verifies whether a comment exists.

~~~
fp comment exists <id>
~~~

Displays a success message if the comment does exist.

**OPTIONS**

	<id>
		The ID of the comment to check.

**EXAMPLES**

    # Check whether comment exists.
    $ fp comment exists 1337
    Success: Comment with ID 1337 exists.



### fp comment generate

Generates some number of new dummy comments.

~~~
fp comment generate [--count=<number>] [--post_id=<post-id>] [--format=<format>]
~~~

Creates a specified number of new comments with dummy data.

**OPTIONS**

	[--count=<number>]
		How many comments to generate?
		---
		default: 100
		---

	[--post_id=<post-id>]
		Assign comments to a specific post.

	[--format=<format>]
		Render output in a particular format.
		---
		default: progress
		options:
		  - progress
		  - ids
		---

**EXAMPLES**

    # Generate comments for the given post.
    $ fp comment generate --format=ids --count=3 --post_id=123
    138 139 140

    # Add meta to every generated comment.
    $ fp comment generate --format=ids --count=3 | xargs -d ' ' -I % fp comment meta add % foo bar
    Success: Added custom field.
    Success: Added custom field.
    Success: Added custom field.



### fp comment get

Gets the data of a single comment.

~~~
fp comment get <id> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The comment to get.

	[--field=<field>]
		Instead of returning the whole comment, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get comment.
    $ fp comment get 21 --field=content
    Thanks for all the comments, everyone!



### fp comment list

Gets a list of comments.

~~~
fp comment list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

Display comments based on all arguments supported by
[FP_Comment_Query()](https://developer.finpress.org/reference/classes/FP_Comment_Query/__construct/).

**OPTIONS**

	[--<field>=<value>]
		One or more args to pass to FP_Comment_Query.

	[--field=<field>]
		Prints the value of a single field for each comment.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - ids
		  - csv
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each comment:

* comment_ID
* comment_post_ID
* comment_date
* comment_approved
* comment_author
* comment_author_email

These fields are optionally available:

* comment_author_url
* comment_author_IP
* comment_date_gmt
* comment_content
* comment_karma
* comment_agent
* comment_type
* comment_parent
* user_id
* url

**EXAMPLES**

    # List comment IDs.
    $ fp comment list --field=ID
    22
    23
    24

    # List comments of a post.
    $ fp comment list --post_id=1 --fields=ID,comment_date,comment_author
    +------------+---------------------+----------------+
    | comment_ID | comment_date        | comment_author |
    +------------+---------------------+----------------+
    | 1          | 2015-06-20 09:00:10 | Mr FinPress   |
    +------------+---------------------+----------------+

    # List approved comments.
    $ fp comment list --number=3 --status=approve --fields=ID,comment_date,comment_author
    +------------+---------------------+----------------+
    | comment_ID | comment_date        | comment_author |
    +------------+---------------------+----------------+
    | 1          | 2015-06-20 09:00:10 | Mr FinPress   |
    | 30         | 2013-03-14 12:35:07 | John Doe       |
    | 29         | 2013-03-14 11:56:08 | Jane Doe       |
    +------------+---------------------+----------------+

    # List unapproved comments.
    $ fp comment list --number=3 --status=hold --fields=ID,comment_date,comment_author
    +------------+---------------------+----------------+
    | comment_ID | comment_date        | comment_author |
    +------------+---------------------+----------------+
    | 8          | 2023-11-10 13:13:06 | John Doe       |
    | 7          | 2023-11-10 13:09:55 | Mr FinPress   |
    | 9          | 2023-11-10 11:22:31 | Jane Doe       |
    +------------+---------------------+----------------+

    # List comments marked as spam.
    $ fp comment list --status=spam --fields=ID,comment_date,comment_author
    +------------+---------------------+----------------+
    | comment_ID | comment_date        | comment_author |
    +------------+---------------------+----------------+
    | 2          | 2023-11-10 11:22:31 | Jane Doe       |
    +------------+---------------------+----------------+

    # List comments in trash.
    $ fp comment list --status=trash --fields=ID,comment_date,comment_author
    +------------+---------------------+----------------+
    | comment_ID | comment_date        | comment_author |
    +------------+---------------------+----------------+
    | 3          | 2023-11-10 11:22:31 | John Doe       |
    +------------+---------------------+----------------+



### fp comment meta

Adds, updates, deletes, and lists comment custom fields.

~~~
fp comment meta
~~~

**EXAMPLES**

    # Set comment meta
    $ fp comment meta set 123 description "Mary is a FinPress developer."
    Success: Updated custom field 'description'.

    # Get comment meta
    $ fp comment meta get 123 description
    Mary is a FinPress developer.

    # Update comment meta
    $ fp comment meta update 123 description "Mary is an awesome FinPress developer."
    Success: Updated custom field 'description'.

    # Delete comment meta
    $ fp comment meta delete 123 description
    Success: Deleted custom field.





### fp comment meta add

Add a meta field.

~~~
fp comment meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp comment meta delete

Delete a meta field.

~~~
fp comment meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### fp comment meta get

Get meta field value.

~~~
fp comment meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---



### fp comment meta list

List all metadata associated with an object.

~~~
fp comment meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>] [--unserialize]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

	[--unserialize]
		Unserialize meta_value output.



### fp comment meta patch

Update a nested value for a meta field.

~~~
fp comment meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp comment meta pluck

Get a nested value from a meta field.

~~~
fp comment meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### fp comment meta update

Update a meta field.

~~~
fp comment meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp comment recount

Recalculates the comment_count value for one or more posts.

~~~
fp comment recount <id>...
~~~

**OPTIONS**

	<id>...
		IDs for one or more posts to update.

**EXAMPLES**

    # Recount comment for the post.
    $ fp comment recount 123
    Updated post 123 comment count to 67.



### fp comment spam

Marks a comment as spam.

~~~
fp comment spam <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to mark as spam.

**EXAMPLES**

    # Spam comment.
    $ fp comment spam 1337
    Success: Marked as spam comment 1337.



### fp comment status

Gets the status of a comment.

~~~
fp comment status <id>
~~~

**OPTIONS**

	<id>
		The ID of the comment to check.

**EXAMPLES**

    # Get status of comment.
    $ fp comment status 1337
    approved



### fp comment trash

Trashes a comment.

~~~
fp comment trash <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to trash.

**EXAMPLES**

    # Trash comment.
    $ fp comment trash 1337
    Success: Trashed comment 1337.



### fp comment unapprove

Unapproves a comment.

~~~
fp comment unapprove <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to unapprove.

**EXAMPLES**

    # Unapprove comment.
    $ fp comment unapprove 1337
    Success: Unapproved comment 1337.



### fp comment unspam

Unmarks a comment as spam.

~~~
fp comment unspam <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to unmark as spam.

**EXAMPLES**

    # Unspam comment.
    $ fp comment unspam 1337
    Success: Unspammed comment 1337.



### fp comment untrash

Untrashes a comment.

~~~
fp comment untrash <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to untrash.

**EXAMPLES**

    # Untrash comment.
    $ fp comment untrash 1337
    Success: Untrashed comment 1337.



### fp comment update

Updates one or more comments.

~~~
fp comment update <id>... --<field>=<value>
~~~

**OPTIONS**

	<id>...
		One or more IDs of comments to update.

	--<field>=<value>
		One or more fields to update. See fp_update_comment().

**EXAMPLES**

    # Update comment.
    $ fp comment update 123 --comment_author='That Guy'
    Success: Updated comment 123.



### fp menu

Lists, creates, assigns, and deletes the active theme's navigation menus.

~~~
fp menu
~~~

See the [Navigation Menus](https://developer.finpress.org/themes/functionality/navigation-menus/) reference in the Theme Handbook.

**EXAMPLES**

    # Create a new menu
    $ fp menu create "My Menu"
    Success: Created menu 200.

    # List existing menus
    $ fp menu list
    +---------+----------+----------+-----------+-------+
    | term_id | name     | slug     | locations | count |
    +---------+----------+----------+-----------+-------+
    | 200     | My Menu  | my-menu  |           | 0     |
    | 177     | Top Menu | top-menu | primary   | 7     |
    +---------+----------+----------+-----------+-------+

    # Create a new menu link item
    $ fp menu item add-custom my-menu Apple http://apple.com --porcelain
    1922

    # Assign the 'my-menu' menu to the 'primary' location
    $ fp menu location assign my-menu primary
    Success: Assigned location primary to menu my-menu.



### fp menu create

Creates a new menu.

~~~
fp menu create <menu-name> [--porcelain]
~~~

**OPTIONS**

	<menu-name>
		A descriptive name for the menu.

	[--porcelain]
		Output just the new menu id.

**EXAMPLES**

    $ fp menu create "My Menu"
    Success: Created menu 200.



### fp menu delete

Deletes one or more menus.

~~~
fp menu delete <menu>...
~~~

**OPTIONS**

	<menu>...
		The name, slug, or term ID for the menu(s).

**EXAMPLES**

    $ fp menu delete "My Menu"
    Deleted menu 'My Menu'.
    Success: Deleted 1 of 1 menus.



### fp menu item

List, add, and delete items associated with a menu.

~~~
fp menu item
~~~

**EXAMPLES**

    # Add an existing post to an existing menu
    $ fp menu item add-post sidebar-menu 33 --title="Custom Test Post"
    Success: Menu item added.

    # Create a new menu link item
    $ fp menu item add-custom sidebar-menu Apple http://apple.com
    Success: Menu item added.

    # Delete menu item
    $ fp menu item delete 45
    Success: Deleted 1 of 1 menu items.





### fp menu item add-custom

Adds a custom menu item.

~~~
fp menu item add-custom <menu> <title> <link> [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>] [--porcelain]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<title>
		Title for the link.

	<link>
		Target URL for the link.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

	[--porcelain]
		Output just the new menu item id.

**EXAMPLES**

    $ fp menu item add-custom sidebar-menu Apple http://apple.com
    Success: Menu item added.



### fp menu item add-post

Adds a post as a menu item.

~~~
fp menu item add-post <menu> <post-id> [--title=<title>] [--link=<link>] [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>] [--porcelain]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<post-id>
		Post ID to add to the menu.

	[--title=<title>]
		Set a custom title for the menu item.

	[--link=<link>]
		Set a custom url for the menu item.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

	[--porcelain]
		Output just the new menu item id.

**EXAMPLES**

    $ fp menu item add-post sidebar-menu 33 --title="Custom Test Post"
    Success: Menu item added.



### fp menu item add-term

Adds a taxonomy term as a menu item.

~~~
fp menu item add-term <menu> <taxonomy> <term-id> [--title=<title>] [--link=<link>] [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>] [--porcelain]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<taxonomy>
		Taxonomy of the term to be added.

	<term-id>
		Term ID of the term to be added.

	[--title=<title>]
		Set a custom title for the menu item.

	[--link=<link>]
		Set a custom url for the menu item.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

	[--porcelain]
		Output just the new menu item id.

**EXAMPLES**

    $ fp menu item add-term sidebar-menu post_tag 24
    Success: Menu item added.



### fp menu item delete

Deletes one or more items from a menu.

~~~
fp menu item delete <db-id>...
~~~

**OPTIONS**

	<db-id>...
		Database ID for the menu item(s).

**EXAMPLES**

    $ fp menu item delete 45
    Success: Deleted 1 of 1 menu items.



### fp menu item list

Gets a list of items associated with a menu.

~~~
fp menu item list <menu> [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - ids
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each menu item:

* db_id
* type
* title
* link
* position

These fields are optionally available:

* menu_item_parent
* object_id
* object
* type
* type_label
* target
* attr_title
* description
* classes
* xfn

**EXAMPLES**

    $ fp menu item list main-menu
    +-------+-----------+-------------+---------------------------------+----------+
    | db_id | type      | title       | link                            | position |
    +-------+-----------+-------------+---------------------------------+----------+
    | 5     | custom    | Home        | http://example.com              | 1        |
    | 6     | post_type | Sample Page | http://example.com/sample-page/ | 2        |
    +-------+-----------+-------------+---------------------------------+----------+



### fp menu item update

Updates a menu item.

~~~
fp menu item update <db-id> [--title=<title>] [--link=<link>] [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>]
~~~

**OPTIONS**

	<db-id>
		Database ID for the menu item.

	[--title=<title>]
		Set a custom title for the menu item.

	[--link=<link>]
		Set a custom url for the menu item.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

**EXAMPLES**

    $ fp menu item update 45 --title=FinPress --link='http://finpress.org' --target=_blank --position=2
    Success: Menu item updated.



### fp menu list

Gets a list of menus.

~~~
fp menu list [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - ids
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each menu:

* term_id
* name
* slug
* count

These fields are optionally available:

* term_group
* term_taxonomy_id
* taxonomy
* description
* parent
* locations

**EXAMPLES**

    $ fp menu list
    +---------+----------+----------+-----------+-------+
    | term_id | name     | slug     | locations | count |
    +---------+----------+----------+-----------+-------+
    | 200     | My Menu  | my-menu  |           | 0     |
    | 177     | Top Menu | top-menu | primary   | 7     |
    +---------+----------+----------+-----------+-------+



### fp menu location

Assigns, removes, and lists a menu's locations.

~~~
fp menu location
~~~

**EXAMPLES**

    # List available menu locations
    $ fp menu location list
    +----------+-------------------+
    | location | description       |
    +----------+-------------------+
    | primary  | Primary Menu      |
    | social   | Social Links Menu |
    +----------+-------------------+

    # Assign the 'primary-menu' menu to the 'primary' location
    $ fp menu location assign primary-menu primary
    Success: Assigned location primary to menu primary-menu.

    # Remove the 'primary-menu' menu from the 'primary' location
    $ fp menu location remove primary-menu primary
    Success: Removed location from menu.





### fp menu location assign

Assigns a location to a menu.

~~~
fp menu location assign <menu> <location>
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<location>
		Location's slug.

**EXAMPLES**

    $ fp menu location assign primary-menu primary
    Success: Assigned location primary to menu primary-menu.



### fp menu location list

Lists locations for the current theme.

~~~
fp menu location list [--format=<format>]
~~~

**OPTIONS**

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each location:

* name
* description

**EXAMPLES**

    $ fp menu location list
    +----------+-------------------+
    | location | description       |
    +----------+-------------------+
    | primary  | Primary Menu      |
    | social   | Social Links Menu |
    +----------+-------------------+



### fp menu location remove

Removes a location from a menu.

~~~
fp menu location remove <menu> <location>
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<location>
		Location's slug.

**EXAMPLES**

    $ fp menu location remove primary-menu primary
    Success: Removed location from menu.



### fp network meta

Gets, adds, updates, deletes, and lists network custom fields.

~~~
fp network meta
~~~

**EXAMPLES**

    # Get a list of super-admins
    $ fp network meta get 1 site_admins
    array (
      0 => 'supervisor',
    )



### fp network meta add

Add a meta field.

~~~
fp network meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp network meta delete

Delete a meta field.

~~~
fp network meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### fp network meta get

Get meta field value.

~~~
fp network meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---



### fp network meta list

List all metadata associated with an object.

~~~
fp network meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>] [--unserialize]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

	[--unserialize]
		Unserialize meta_value output.



### fp network meta patch

Update a nested value for a meta field.

~~~
fp network meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp network meta pluck

Get a nested value from a meta field.

~~~
fp network meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### fp network meta update

Update a meta field.

~~~
fp network meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp option

Retrieves and sets site options, including plugin and FinPress settings.

~~~
fp option
~~~

See the [Plugin Settings API](https://developer.finpress.org/plugins/settings/settings-api/) and the [Theme Options](https://developer.finpress.org/themes/customize-api/) for more information on adding customized options.

**EXAMPLES**

    # Get site URL.
    $ fp option get siteurl
    http://example.com

    # Add option.
    $ fp option add my_option foobar
    Success: Added 'my_option' option.

    # Update option.
    $ fp option update my_option '{"foo": "bar"}' --format=json
    Success: Updated 'my_option' option.

    # Delete option.
    $ fp option delete my_option
    Success: Deleted 'my_option' option.



### fp option add

Adds a new option value.

~~~
fp option add <key> [<value>] [--format=<format>] [--autoload=<autoload>]
~~~

Errors if the option already exists.

**OPTIONS**

	<key>
		The name of the option to add.

	[<value>]
		The value of the option to add. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---

	[--autoload=<autoload>]
		Should this option be automatically loaded.
		---
		options:
		  - 'on'
		  - 'off'
		  - 'yes'
		  - 'no'
		---

**EXAMPLES**

    # Create an option by reading a JSON file.
    $ fp option add my_option --format=json < config.json
    Success: Added 'my_option' option.



### fp option delete

Deletes an option.

~~~
fp option delete <key>...
~~~

**OPTIONS**

	<key>...
		Key for the option.

**EXAMPLES**

    # Delete an option.
    $ fp option delete my_option
    Success: Deleted 'my_option' option.

    # Delete multiple options.
    $ fp option delete option_one option_two option_three
    Success: Deleted 'option_one' option.
    Success: Deleted 'option_two' option.
    Warning: Could not delete 'option_three' option. Does it exist?



### fp option get

Gets the value for an option.

~~~
fp option get <key> [--format=<format>]
~~~

**OPTIONS**

	<key>
		Key for the option.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get option.
    $ fp option get home
    http://example.com

    # Get blog description.
    $ fp option get blogdescription
    A random blog description

    # Get blog name
    $ fp option get blogname
    A random blog name

    # Get admin email.
    $ fp option get admin_email
    someone@example.com

    # Get option in JSON format.
    $ fp option get active_plugins --format=json
    {"0":"dynamically-dynamic-sidebar\/dynamically-dynamic-sidebar.php","1":"monster-widget\/monster-widget.php","2":"show-current-template\/show-current-template.php","3":"theme-check\/theme-check.php","5":"finpress-importer\/finpress-importer.php"}



### fp option list

Lists options and their values.

~~~
fp option list [--search=<pattern>] [--exclude=<pattern>] [--autoload=<value>] [--transients] [--unserialize] [--field=<field>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>]
~~~

**OPTIONS**

	[--search=<pattern>]
		Use wildcards ( * and ? ) to match option name.

	[--exclude=<pattern>]
		Pattern to exclude. Use wildcards ( * and ? ) to match option name.

	[--autoload=<value>]
		Match only autoload options when value is on, and only not-autoload option when off.

	[--transients]
		List only transients. Use `--no-transients` to ignore all transients.

	[--unserialize]
		Unserialize option values in output.

	[--field=<field>]
		Prints the value of a single field.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		The serialization format for the value. total_bytes displays the total size of matching options in bytes.
		---
		default: table
		options:
		  - table
		  - json
		  - csv
		  - count
		  - yaml
		  - total_bytes
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: option_id
		options:
		 - option_id
		 - option_name
		 - option_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

**AVAILABLE FIELDS**

This field will be displayed by default for each matching option:

* option_name
* option_value

These fields are optionally available:

* autoload
* size_bytes

**EXAMPLES**

    # Get the total size of all autoload options.
    $ fp option list --autoload=on --format=total_bytes
    33198

    # Find biggest transients.
    $ fp option list --search="*_transient_*" --fields=option_name,size_bytes | sort -n -k 2 | tail
    option_name size_bytes
    _site_transient_timeout_theme_roots 10
    _site_transient_theme_roots 76
    _site_transient_update_themes   181
    _site_transient_update_core 808
    _site_transient_update_plugins  6645

    # List all options beginning with "i2f_".
    $ fp option list --search="i2f_*"
    +-------------+--------------+
    | option_name | option_value |
    +-------------+--------------+
    | i2f_version | 0.1.0        |
    +-------------+--------------+

    # Delete all options beginning with "theme_mods_".
    $ fp option list --search="theme_mods_*" --field=option_name | xargs -I % fp option delete %
    Success: Deleted 'theme_mods_twentysixteen' option.
    Success: Deleted 'theme_mods_twentyfifteen' option.
    Success: Deleted 'theme_mods_twentyfourteen' option.



### fp option patch

Updates a nested value in an option.

~~~
fp option patch <action> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<key>
		The option name.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---

**EXAMPLES**

    # Add 'bar' to the 'foo' key on an option with name 'option_name'
    $ fp option patch insert option_name foo bar
    Success: Updated 'option_name' option.

    # Update the value of 'foo' key to 'new' on an option with name 'option_name'
    $ fp option patch update option_name foo new
    Success: Updated 'option_name' option.

    # Set nested value of 'bar' key to value we have in the patch file on an option with name 'option_name'.
    $ fp option patch update option_name foo bar < patch
    Success: Updated 'option_name' option.

    # Update the value for the key 'not-a-key' which is not exist on an option with name 'option_name'.
    $ fp option patch update option_name foo not-a-key new-value
    Error: No data exists for key "not-a-key"

    # Update the value for the key 'foo' without passing value on an option with name 'option_name'.
    $ fp option patch update option_name foo
    Error: Please provide value to update.

    # Delete the nested key 'bar' under 'foo' key on an option with name 'option_name'.
    $ fp option patch delete option_name foo bar
    Success: Updated 'option_name' option.



### fp option pluck

Gets a nested value from an option.

~~~
fp option pluck <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<key>
		The option name.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml
		---



### fp option update

Updates an option value.

~~~
fp option update <key> [<value>] [--autoload=<autoload>] [--format=<format>]
~~~

**OPTIONS**

	<key>
		The name of the option to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--autoload=<autoload>]
		Requires FP 4.2. Should this option be automatically loaded.
		---
		options:
		  - 'on'
		  - 'off'
		  - 'yes'
		  - 'no'
		---

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---

**EXAMPLES**

    # Update an option by reading from a file.
    $ fp option update my_option < value.txt
    Success: Updated 'my_option' option.

    # Update one option on multiple sites using xargs.
    $ fp site list --field=url | xargs -n1 -I {} sh -c 'fp --url={} option update my_option my_value'
    Success: Updated 'my_option' option.
    Success: Updated 'my_option' option.

    # Update site blog name.
    $ fp option update blogname "Random blog name"
    Success: Updated 'blogname' option.

    # Update site blog description.
    $ fp option update blogdescription "Some random blog description"
    Success: Updated 'blogdescription' option.

    # Update admin email address.
    $ fp option update admin_email someone@example.com
    Success: Updated 'admin_email' option.

    # Set the default role.
    $ fp option update default_role author
    Success: Updated 'default_role' option.

    # Set the timezone string.
    $ fp option update timezone_string "America/New_York"
    Success: Updated 'timezone_string' option.



### fp option set-autoload

Sets the 'autoload' value for an option.

~~~
fp option set-autoload <key> <autoload>
~~~

**OPTIONS**

	<key>
		The name of the option to set 'autoload' for.

	<autoload>
		Should this option be automatically loaded.
		---
		options:
		  - 'on'
		  - 'off'
		  - 'yes'
		  - 'no'
		---

**EXAMPLES**

    # Set the 'autoload' value for an option.
    $ fp option set-autoload abc_options no
    Success: Updated autoload value for 'abc_options' option.



### fp option get-autoload

Gets the 'autoload' value for an option.

~~~
fp option get-autoload <key>
~~~

**OPTIONS**

	<key>
		The name of the option to get 'autoload' of.

**EXAMPLES**

    # Get the 'autoload' value for an option.
    $ fp option get-autoload blogname
    yes



### fp post

Manages posts, content, and meta.

~~~
fp post
~~~

**EXAMPLES**

    # Create a new post.
    $ fp post create --post_type=post --post_title='A sample post'
    Success: Created post 123.

    # Update an existing post.
    $ fp post update 123 --post_status=draft
    Success: Updated post 123.

    # Delete an existing post.
    $ fp post delete 123
    Success: Trashed post 123.



### fp post create

Creates a new post.

~~~
fp post create [--post_author=<post_author>] [--post_date=<post_date>] [--post_date_gmt=<post_date_gmt>] [--post_content=<post_content>] [--post_content_filtered=<post_content_filtered>] [--post_title=<post_title>] [--post_excerpt=<post_excerpt>] [--post_status=<post_status>] [--post_type=<post_type>] [--comment_status=<comment_status>] [--ping_status=<ping_status>] [--post_password=<post_password>] [--post_name=<post_name>] [--from-post=<post_id>] [--to_ping=<to_ping>] [--pinged=<pinged>] [--post_modified=<post_modified>] [--post_modified_gmt=<post_modified_gmt>] [--post_parent=<post_parent>] [--menu_order=<menu_order>] [--post_mime_type=<post_mime_type>] [--guid=<guid>] [--post_category=<post_category>] [--tags_input=<tags_input>] [--tax_input=<tax_input>] [--meta_input=<meta_input>] [<file>] [--<field>=<value>] [--edit] [--porcelain]
~~~

**OPTIONS**

	[--post_author=<post_author>]
		The ID of the user who added the post. Default is the current user ID.

	[--post_date=<post_date>]
		The date of the post. Default is the current time.

	[--post_date_gmt=<post_date_gmt>]
		The date of the post in the GMT timezone. Default is the value of $post_date.

	[--post_content=<post_content>]
		The post content. Default empty.

	[--post_content_filtered=<post_content_filtered>]
		The filtered post content. Default empty.

	[--post_title=<post_title>]
		The post title. Default empty.

	[--post_excerpt=<post_excerpt>]
		The post excerpt. Default empty.

	[--post_status=<post_status>]
		The post status. Default 'draft'.

	[--post_type=<post_type>]
		The post type. Default 'post'.

	[--comment_status=<comment_status>]
		Whether the post can accept comments. Accepts 'open' or 'closed'. Default is the value of 'default_comment_status' option.

	[--ping_status=<ping_status>]
		Whether the post can accept pings. Accepts 'open' or 'closed'. Default is the value of 'default_ping_status' option.

	[--post_password=<post_password>]
		The password to access the post. Default empty.

	[--post_name=<post_name>]
		The post name. Default is the sanitized post title when creating a new post.

	[--from-post=<post_id>]
		Post id of a post to be duplicated.

	[--to_ping=<to_ping>]
		Space or carriage return-separated list of URLs to ping. Default empty.

	[--pinged=<pinged>]
		Space or carriage return-separated list of URLs that have been pinged. Default empty.

	[--post_modified=<post_modified>]
		The date when the post was last modified. Default is the current time.

	[--post_modified_gmt=<post_modified_gmt>]
		The date when the post was last modified in the GMT timezone. Default is the current time.

	[--post_parent=<post_parent>]
		Set this for the post it belongs to, if any. Default 0.

	[--menu_order=<menu_order>]
		The order the post should be displayed in. Default 0.

	[--post_mime_type=<post_mime_type>]
		The mime type of the post. Default empty.

	[--guid=<guid>]
		Global Unique ID for referencing the post. Default empty.

	[--post_category=<post_category>]
		Array of category names, slugs, or IDs. Defaults to value of the 'default_category' option.

	[--tags_input=<tags_input>]
		Array of tag names, slugs, or IDs. Default empty.

	[--tax_input=<tax_input>]
		Array of taxonomy terms keyed by their taxonomy name. Default empty.

	[--meta_input=<meta_input>]
		Array in JSON format of post meta values keyed by their post meta key. Default empty.

	[<file>]
		Read post content from <file>. If this value is present, the
		    `--post_content` argument will be ignored.

  Passing `-` as the filename will cause post content to
  be read from STDIN.

	[--<field>=<value>]
		Associative args for the new post. See fp_insert_post().

	[--edit]
		Immediately open system's editor to write or edit post content.

  If content is read from a file, from STDIN, or from the `--post_content`
  argument, that text will be loaded into the editor.

	[--porcelain]
		Output just the new post id.


**EXAMPLES**

    # Create post and schedule for future
    $ fp post create --post_type=post --post_title='A future post' --post_status=future --post_date='2030-12-01 07:00:00'
    Success: Created post 1921.

    # Create post with content from given file
    $ fp post create ./post-content.txt --post_category=201,345 --post_title='Post from file'
    Success: Created post 1922.

    # Create a post with multiple meta values.
    $ fp post create --post_title='A post' --post_content='Just a small post.' --meta_input='{"key1":"value1","key2":"value2"}'
    Success: Created post 1923.

    # Create a duplicate post from existing posts.
    $ fp post create --from-post=123 --post_title='Different Title'
    Success: Created post 2350.



### fp post delete

Deletes an existing post.

~~~
fp post delete <id>... [--force] [--defer-term-counting]
~~~

**OPTIONS**

	<id>...
		One or more IDs of posts to delete.

	[--force]
		Skip the trash bin.

	[--defer-term-counting]
		Recalculate term count in batch, for a performance boost.

**EXAMPLES**

    # Delete post skipping trash
    $ fp post delete 123 --force
    Success: Deleted post 123.

    # Delete multiple posts
    $ fp post delete 123 456 789
    Success: Trashed post 123.
    Success: Trashed post 456.
    Success: Trashed post 789.

    # Delete all pages
    $ fp post delete $(fp post list --post_type='page' --format=ids)
    Success: Trashed post 1164.
    Success: Trashed post 1186.

    # Delete all posts in the trash
    $ fp post delete $(fp post list --post_status=trash --format=ids)
    Success: Deleted post 1268.
    Success: Deleted post 1294.



### fp post edit

Launches system editor to edit post content.

~~~
fp post edit <id>
~~~

**OPTIONS**

	<id>
		The ID of the post to edit.

**EXAMPLES**

    # Launch system editor to edit post
    $ fp post edit 123



### fp post exists

Verifies whether a post exists.

~~~
fp post exists <id>
~~~

Displays a success message if the post does exist.

**OPTIONS**

	<id>
		The ID of the post to check.

**EXAMPLES**

    # The post exists.
    $ fp post exists 1337
    Success: Post with ID 1337 exists.
    $ echo $?
    0

    # The post does not exist.
    $ fp post exists 10000
    $ echo $?
    1



### fp post generate

Generates some posts.

~~~
fp post generate [--count=<number>] [--post_type=<type>] [--post_status=<status>] [--post_title=<post_title>] [--post_author=<login>] [--post_date=<yyyy-mm-dd-hh-ii-ss>] [--post_date_gmt=<yyyy-mm-dd-hh-ii-ss>] [--post_content] [--max_depth=<number>] [--format=<format>]
~~~

Creates a specified number of new posts with dummy data.

**OPTIONS**

	[--count=<number>]
		How many posts to generate?
		---
		default: 100
		---

	[--post_type=<type>]
		The type of the generated posts.
		---
		default: post
		---

	[--post_status=<status>]
		The status of the generated posts.
		---
		default: publish
		---

	[--post_title=<post_title>]
		The post title.
		---
		default:
		---

	[--post_author=<login>]
		The author of the generated posts.
		---
		default:
		---

	[--post_date=<yyyy-mm-dd-hh-ii-ss>]
		The date of the post. Default is the current time.

	[--post_date_gmt=<yyyy-mm-dd-hh-ii-ss>]
		The date of the post in the GMT timezone. Default is the value of --post_date.

	[--post_content]
		If set, the command reads the post_content from STDIN.

	[--max_depth=<number>]
		For hierarchical post types, generate child posts down to a certain depth.
		---
		default: 1
		---

	[--format=<format>]
		Render output in a particular format.
		---
		default: progress
		options:
		  - progress
		  - ids
		---

**EXAMPLES**

    # Generate posts.
    $ fp post generate --count=10 --post_type=page --post_date=1999-01-04
    Generating posts  100% [================================================] 0:01 / 0:04

    # Generate posts with fetched content.
    $ curl -N https://loripsum.net/api/5 | fp post generate --post_content --count=10
      % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                     Dload  Upload   Total   Spent    Left  Speed
    100  2509  100  2509    0     0    616      0  0:00:04  0:00:04 --:--:--   616
    Generating posts  100% [================================================] 0:01 / 0:04

    # Add meta to every generated posts.
    $ fp post generate --format=ids | xargs -d ' ' -I % fp post meta add % foo bar
    Success: Added custom field.
    Success: Added custom field.
    Success: Added custom field.



### fp post get

Gets details about a post.

~~~
fp post get <id> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the post to get.

	[--field=<field>]
		Instead of returning the whole post, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Save the post content to a file
    $ fp post get 123 --field=content > file.txt



### fp post list

Gets a list of posts.

~~~
fp post list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

Display posts based on all arguments supported by [FP_Query()](https://developer.finpress.org/reference/classes/fp_query/).
Only shows post types marked as post by default.

**OPTIONS**

	[--<field>=<value>]
		One or more args to pass to FP_Query.

	[--field=<field>]
		Prints the value of a single field for each post.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - ids
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each post:

* ID
* post_title
* post_name
* post_date
* post_status

These fields are optionally available:

* post_author
* post_date_gmt
* post_content
* post_excerpt
* comment_status
* ping_status
* post_password
* to_ping
* pinged
* post_modified
* post_modified_gmt
* post_content_filtered
* post_parent
* guid
* menu_order
* post_type
* post_mime_type
* comment_count
* filter
* url

**EXAMPLES**

    # List post
    $ fp post list --field=ID
    568
    829
    1329
    1695

    # List posts in JSON
    $ fp post list --post_type=post --posts_per_page=5 --format=json
    [{"ID":1,"post_title":"Hello world!","post_name":"hello-world","post_date":"2015-06-20 09:00:10","post_status":"publish"},{"ID":1178,"post_title":"Markup: HTML Tags and Formatting","post_name":"markup-html-tags-and-formatting","post_date":"2013-01-11 20:22:19","post_status":"draft"}]

    # List all pages
    $ fp post list --post_type=page --fields=post_title,post_status
    +-------------+-------------+
    | post_title  | post_status |
    +-------------+-------------+
    | Sample Page | publish     |
    +-------------+-------------+

    # List ids of all pages and posts
    $ fp post list --post_type=page,post --format=ids
    15 25 34 37 198

    # List given posts
    $ fp post list --post__in=1,3
    +----+--------------+-------------+---------------------+-------------+
    | ID | post_title   | post_name   | post_date           | post_status |
    +----+--------------+-------------+---------------------+-------------+
    | 3  | Lorem Ipsum  | lorem-ipsum | 2016-06-01 14:34:36 | publish     |
    | 1  | Hello world! | hello-world | 2016-06-01 14:31:12 | publish     |
    +----+--------------+-------------+---------------------+-------------+

    # List given post by a specific author
    $ fp post list --author=2
    +----+-------------------+-------------------+---------------------+-------------+
    | ID | post_title        | post_name         | post_date           | post_status |
    +----+-------------------+-------------------+---------------------+-------------+
    | 14 | New documentation | new-documentation | 2021-06-18 21:05:11 | publish     |
    +----+-------------------+-------------------+---------------------+-------------+



### fp post meta

Adds, updates, deletes, and lists post custom fields.

~~~
fp post meta
~~~

**EXAMPLES**

    # Set post meta
    $ fp post meta set 123 _fp_page_template about.php
    Success: Updated custom field '_fp_page_template'.

    # Get post meta
    $ fp post meta get 123 _fp_page_template
    about.php

    # Update post meta
    $ fp post meta update 123 _fp_page_template contact.php
    Success: Updated custom field '_fp_page_template'.

    # Delete post meta
    $ fp post meta delete 123 _fp_page_template
    Success: Deleted custom field.





### fp post meta add

Add a meta field.

~~~
fp post meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp post meta clean-duplicates

Cleans up duplicate post meta values on a post.

~~~
fp post meta clean-duplicates <id> <key>
~~~

**OPTIONS**

	<id>
		ID of the post to clean.

	<key>
		Meta key to clean up.

**EXAMPLES**

    # Delete duplicate post meta.
    fp post meta clean-duplicates 1234 enclosure
    Success: Cleaned up duplicate 'enclosure' meta values.



### fp post meta delete

Delete a meta field.

~~~
fp post meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### fp post meta get

Get meta field value.

~~~
fp post meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---



### fp post meta list

List all metadata associated with an object.

~~~
fp post meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>] [--unserialize]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

	[--unserialize]
		Unserialize meta_value output.



### fp post meta patch

Update a nested value for a meta field.

~~~
fp post meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp post meta pluck

Get a nested value from a meta field.

~~~
fp post meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### fp post meta update

Update a meta field.

~~~
fp post meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp post term

Adds, updates, removes, and lists post terms.

~~~
fp post term
~~~

**EXAMPLES**

    # Set category post term `test` to the post ID 123
    $ fp post term set 123 test category
    Success: Set term.

    # Set category post terms `test` and `apple` to the post ID 123
    $ fp post term set 123 test apple category
    Success: Set terms.

    # List category post terms for the post ID 123
    $ fp post term list 123 category --fields=term_id,slug
    +---------+-------+
    | term_id | slug  |
    +---------+-------+
    | 2       | apple |
    | 3       | test  |
    +----------+------+

    # Remove category post terms `test` and `apple` for the post ID 123
    $ fp post term remove 123 category test apple
    Success: Removed terms.





### fp post term add

Add a term to an object.

~~~
fp post term add <id> <taxonomy> <term>... [--by=<field>]
~~~

Append the term to the existing set of terms on the object.

**OPTIONS**

	<id>
		The ID of the object.

	<taxonomy>
		The name of the taxonomy type to be added.

	<term>...
		The slug of the term or terms to be added.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: slug
		options:
		  - slug
		  - id
		---



### fp post term list

List all terms associated with an object.

~~~
fp post term list <id> <taxonomy>... [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		ID for the object.

	<taxonomy>...
		One or more taxonomies to list.

	[--field=<field>]
		Prints the value of a single field for each term.

	[--fields=<fields>]
		Limit the output to specific row fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each term:

* term_id
* name
* slug
* taxonomy

These fields are optionally available:

* term_taxonomy_id
* description
* term_group
* parent
* count



### fp post term remove

Remove a term from an object.

~~~
fp post term remove <id> <taxonomy> [<term>...] [--by=<field>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<taxonomy>
		The name of the term's taxonomy.

	[<term>...]
		The slug of the term or terms to be removed from the object.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: slug
		options:
		  - slug
		  - id
		---

	[--all]
		Remove all terms from the object.



### fp post term set

Set object terms.

~~~
fp post term set <id> <taxonomy> <term>... [--by=<field>]
~~~

Replaces existing terms on the object.

**OPTIONS**

	<id>
		The ID of the object.

	<taxonomy>
		The name of the taxonomy type to be updated.

	<term>...
		The slug of the term or terms to be updated.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: slug
		options:
		  - slug
		  - id
		---



### fp post update

Updates one or more existing posts.

~~~
fp post update <id>... [--post_author=<post_author>] [--post_date=<post_date>] [--post_date_gmt=<post_date_gmt>] [--post_content=<post_content>] [--post_content_filtered=<post_content_filtered>] [--post_title=<post_title>] [--post_excerpt=<post_excerpt>] [--post_status=<post_status>] [--post_type=<post_type>] [--comment_status=<comment_status>] [--ping_status=<ping_status>] [--post_password=<post_password>] [--post_name=<post_name>] [--to_ping=<to_ping>] [--pinged=<pinged>] [--post_modified=<post_modified>] [--post_modified_gmt=<post_modified_gmt>] [--post_parent=<post_parent>] [--menu_order=<menu_order>] [--post_mime_type=<post_mime_type>] [--guid=<guid>] [--post_category=<post_category>] [--tags_input=<tags_input>] [--tax_input=<tax_input>] [--meta_input=<meta_input>] [<file>] --<field>=<value> [--defer-term-counting]
~~~

**OPTIONS**

	<id>...
		One or more IDs of posts to update.

	[--post_author=<post_author>]
		The ID of the user who added the post. Default is the current user ID.

	[--post_date=<post_date>]
		The date of the post. Default is the current time.

	[--post_date_gmt=<post_date_gmt>]
		The date of the post in the GMT timezone. Default is the value of $post_date.

	[--post_content=<post_content>]
		The post content. Default empty.

	[--post_content_filtered=<post_content_filtered>]
		The filtered post content. Default empty.

	[--post_title=<post_title>]
		The post title. Default empty.

	[--post_excerpt=<post_excerpt>]
		The post excerpt. Default empty.

	[--post_status=<post_status>]
		The post status. Default 'draft'.

	[--post_type=<post_type>]
		The post type. Default 'post'.

	[--comment_status=<comment_status>]
		Whether the post can accept comments. Accepts 'open' or 'closed'. Default is the value of 'default_comment_status' option.

	[--ping_status=<ping_status>]
		Whether the post can accept pings. Accepts 'open' or 'closed'. Default is the value of 'default_ping_status' option.

	[--post_password=<post_password>]
		The password to access the post. Default empty.

	[--post_name=<post_name>]
		The post name. Default is the sanitized post title when creating a new post.

	[--to_ping=<to_ping>]
		Space or carriage return-separated list of URLs to ping. Default empty.

	[--pinged=<pinged>]
		Space or carriage return-separated list of URLs that have been pinged. Default empty.

	[--post_modified=<post_modified>]
		The date when the post was last modified. Default is the current time.

	[--post_modified_gmt=<post_modified_gmt>]
		The date when the post was last modified in the GMT timezone. Default is the current time.

	[--post_parent=<post_parent>]
		Set this for the post it belongs to, if any. Default 0.

	[--menu_order=<menu_order>]
		The order the post should be displayed in. Default 0.

	[--post_mime_type=<post_mime_type>]
		The mime type of the post. Default empty.

	[--guid=<guid>]
		Global Unique ID for referencing the post. Default empty.

	[--post_category=<post_category>]
		Array of category names, slugs, or IDs. Defaults to value of the 'default_category' option.

	[--tags_input=<tags_input>]
		Array of tag names, slugs, or IDs. Default empty.

	[--tax_input=<tax_input>]
		Array of taxonomy terms keyed by their taxonomy name. Default empty.

	[--meta_input=<meta_input>]
		Array in JSON format of post meta values keyed by their post meta key. Default empty.

	[<file>]
		Read post content from <file>. If this value is present, the
		    `--post_content` argument will be ignored.

  Passing `-` as the filename will cause post content to
  be read from STDIN.

	--<field>=<value>
		One or more fields to update. See fp_insert_post().

	[--defer-term-counting]
		Recalculate term count in batch, for a performance boost.

**EXAMPLES**

    $ fp post update 123 --post_name=something --post_status=draft
    Success: Updated post 123.

    # Update a post with multiple meta values.
    $ fp post update 123 --meta_input='{"key1":"value1","key2":"value2"}'
    Success: Updated post 123.

    # Update multiple posts at once.
    $ fp post update 123 456 --post_author=789
    Success: Updated post 123.
    Success: Updated post 456.

    # Update all posts of a given post type at once.
    $ fp post update $(fp post list --post_type=page --format=ids) --post_author=123
    Success: Updated post 123.
    Success: Updated post 456.



### fp post url-to-id

Gets the post ID for a given URL.

~~~
fp post url-to-id <url>
~~~

**OPTIONS**

	<url>
		The URL of the post to get.

**EXAMPLES**

    # Get post ID by URL
    $ fp post url-to-id https://example.com/?p=1
    1



### fp post-type

Retrieves details on the site's registered post types.

~~~
fp post-type
~~~

Get information on FinPress' built-in and the site's [custom post types](https://developer.finpress.org/plugins/post-types/).

**EXAMPLES**

    # Get details about a post type
    $ fp post-type get page --fields=name,label,hierarchical --format=json
    {"name":"page","label":"Pages","hierarchical":true}

    # List post types with 'post' capability type
    $ fp post-type list --capability_type=post --fields=name,public
    +---------------+--------+
    | name          | public |
    +---------------+--------+
    | post          | 1      |
    | attachment    | 1      |
    | revision      |        |
    | nav_menu_item |        |
    +---------------+--------+



### fp post-type get

Gets details about a registered post type.

~~~
fp post-type get <post-type> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<post-type>
		Post type slug

	[--field=<field>]
		Instead of returning the whole taxonomy, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for the specified post type:

* name
* label
* description
* hierarchical
* public
* capability_type
* labels
* cap
* supports

These fields are optionally available:

* count

**EXAMPLES**

    # Get details about the 'page' post type.
    $ fp post-type get page --fields=name,label,hierarchical --format=json
    {"name":"page","label":"Pages","hierarchical":true}



### fp post-type list

Lists registered post types.

~~~
fp post-type list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--<field>=<value>]
		Filter by one or more fields (see get_post_types() first parameter for a list of available fields).

	[--field=<field>]
		Prints the value of a single field for each post type.

	[--fields=<fields>]
		Limit the output to specific post type fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each post type:

* name
* label
* description
* hierarchical
* public
* capability_type

These fields are optionally available:

* count

**EXAMPLES**

    # List registered post types
    $ fp post-type list --format=csv
    name,label,description,hierarchical,public,capability_type
    post,Posts,,,1,post
    page,Pages,,1,1,page
    attachment,Media,,,1,post
    revision,Revisions,,,,post
    nav_menu_item,"Navigation Menu Items",,,,post

    # List post types with 'post' capability type
    $ fp post-type list --capability_type=post --fields=name,public
    +---------------+--------+
    | name          | public |
    +---------------+--------+
    | post          | 1      |
    | attachment    | 1      |
    | revision      |        |
    | nav_menu_item |        |
    +---------------+--------+



### fp site

Creates, deletes, empties, moderates, and lists one or more sites on a multisite installation.

~~~
fp site
~~~

**EXAMPLES**

    # Create site
    $ fp site create --slug=example
    Success: Site 3 created: www.example.com/example/

    # Output a simple list of site URLs
    $ fp site list --field=url
    http://www.example.com/
    http://www.example.com/subdir/

    # Delete site
    $ fp site delete 123
    Are you sure you want to delete the 'http://www.example.com/example' site? [y/n] y
    Success: The site at 'http://www.example.com/example' was deleted.



### fp site activate

Activates one or more sites.

~~~
fp site activate [<id>...] [--slug=<slug>]
~~~

**OPTIONS**

	[<id>...]
		One or more IDs of sites to activate. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the site to be activated. Subdomain on subdomain installs, directory on subdirectory installs.

**EXAMPLES**

    $ fp site activate 123
    Success: Site 123 activated.

     $ fp site activate --slug=demo
     Success: Site 123 marked as activated.



### fp site archive

Archives one or more sites.

~~~
fp site archive [<id>...] [--slug=<slug>]
~~~

**OPTIONS**

	[<id>...]
		One or more IDs of sites to archive. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the site to archive. Subdomain on subdomain installs, directory on subdirectory installs.

**EXAMPLES**

    $ fp site archive 123
    Success: Site 123 archived.

    $ fp site archive --slug=demo
    Success: Site 123 archived.



### fp site create

Creates a site in a multisite installation.

~~~
fp site create --slug=<slug> [--title=<title>] [--email=<email>] [--network_id=<network-id>] [--private] [--porcelain]
~~~

**OPTIONS**

	--slug=<slug>
		Path for the new site. Subdomain on subdomain installs, directory on subdirectory installs.

	[--title=<title>]
		Title of the new site. Default: prettified slug.

	[--email=<email>]
		Email for admin user. User will be created if none exists. Assignment to super admin if not included.

	[--network_id=<network-id>]
		Network to associate new site with. Defaults to current network (typically 1).

	[--private]
		If set, the new site will be non-public (not indexed)

	[--porcelain]
		If set, only the site id will be output on success.

**EXAMPLES**

    $ fp site create --slug=example
    Success: Site 3 created: http://www.example.com/example/



### fp site generate

Generate some sites.

~~~
fp site generate [--count=<number>] [--slug=<slug>] [--email=<email>] [--network_id=<network-id>] [--private] [--format=<format>]
~~~

Creates a specified number of new sites.

**OPTIONS**

	[--count=<number>]
		How many sites to generates?
		---
		default: 100
		---

	[--slug=<slug>]
		Path for the new site. Subdomain on subdomain installs, directory on subdirectory installs.

	[--email=<email>]
		Email for admin user. User will be created if none exists. Assignment to super admin if not included.

	[--network_id=<network-id>]
		Network to associate new site with. Defaults to current network (typically 1).

	[--private]
		If set, the new site will be non-public (not indexed)

	[--format=<format>]
		Render output in a particular format.
		---
		default: progress
		options:
		 - progress
		 - ids
		---

**EXAMPLES**

   # Generate 10 sites.
   $ fp site generate --count=10
   Generating sites  100% [================================================] 0:01 / 0:04



### fp site deactivate

Deactivates one or more sites.

~~~
fp site deactivate [<id>...] [--slug=<slug>]
~~~

**OPTIONS**

	[<id>...]
		One or more IDs of sites to deactivate. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the site to be deactivated. Subdomain on subdomain installs, directory on subdirectory installs.

**EXAMPLES**

    $ fp site deactivate 123
    Success: Site 123 deactivated.

    $ fp site deactivate --slug=demo
    Success: Site 123 deactivated.



### fp site delete

Deletes a site in a multisite installation.

~~~
fp site delete [<site-id>] [--slug=<slug>] [--yes] [--keep-tables]
~~~

**OPTIONS**

	[<site-id>]
		The id of the site to delete. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the site to be deleted. Subdomain on subdomain installs, directory on subdirectory installs.

	[--yes]
		Answer yes to the confirmation message.

	[--keep-tables]
		Delete the blog from the list, but don't drop its tables.

**EXAMPLES**

    $ fp site delete 123
    Are you sure you want to delete the http://www.example.com/example site? [y/n] y
    Success: The site at 'http://www.example.com/example' was deleted.



### fp site empty

Empties a site of its content (posts, comments, terms, and meta).

~~~
fp site empty [--uploads] [--yes]
~~~

Truncates posts, comments, and terms tables to empty a site of its
content. Doesn't affect site configuration (options) or users.

If running a persistent object cache, make sure to flush the cache
after emptying the site, as the cache values will be invalid otherwise.

To also empty custom database tables, you'll need to hook into command
execution:

```
FP_CLI::add_hook( 'after_invoke:site empty', function(){
    global $fpdb;
    foreach( array( 'p2p', 'p2pmeta' ) as $table ) {
        $table = $fpdb->$table;
        $fpdb->query( "TRUNCATE $table" );
    }
});
```

**OPTIONS**

	[--uploads]
		Also delete *all* files in the site's uploads directory.

	[--yes]
		Proceed to empty the site without a confirmation prompt.

**EXAMPLES**

    $ fp site empty
    Are you sure you want to empty the site at http://www.example.com of all posts, links, comments, and terms? [y/n] y
    Success: The site at 'http://www.example.com' was emptied.



### fp site list

Lists all sites in a multisite installation.

~~~
fp site list [--network=<id>] [--<field>=<value>] [--site__in=<value>] [--site_user=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--network=<id>]
		The network to which the sites belong.

	[--<field>=<value>]
		Filter by one or more fields (see "Available Fields" section). However,
		'url' isn't an available filter, as it comes from 'home' in fp_options.

	[--site__in=<value>]
		Only list the sites with these blog_id values (comma-separated).

	[--site_user=<value>]
		Only list the sites with this user.

	[--field=<field>]
		Prints the value of a single field for each site.

	[--fields=<fields>]
		Comma-separated list of fields to show.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - count
		  - ids
		  - json
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each site:

* blog_id
* url
* last_updated
* registered

These fields are optionally available:

* site_id
* domain
* path
* public
* archived
* mature
* spam
* deleted
* lang_id

**EXAMPLES**

    # Output a simple list of site URLs
    $ fp site list --field=url
    http://www.example.com/
    http://www.example.com/subdir/



### fp site mature

Sets one or more sites as mature.

~~~
fp site mature [<id>...] [--slug=<slug>]
~~~

**OPTIONS**

	[<id>...]
		One or more IDs of sites to set as mature. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the site to be set as mature. Subdomain on subdomain installs, directory on subdirectory installs.

**EXAMPLES**

    $ fp site mature 123
    Success: Site 123 marked as mature.

    $ fp site mature --slug=demo
    Success: Site 123 marked as mature.



### fp site meta

Adds, updates, deletes, and lists site custom fields.

~~~
fp site meta
~~~

**EXAMPLES**

    # Set site meta
    $ fp site meta set 123 bio "Mary is a FinPress developer."
    Success: Updated custom field 'bio'.

    # Get site meta
    $ fp site meta get 123 bio
    Mary is a FinPress developer.

    # Update site meta
    $ fp site meta update 123 bio "Mary is an awesome FinPress developer."
    Success: Updated custom field 'bio'.

    # Delete site meta
    $ fp site meta delete 123 bio
    Success: Deleted custom field.





### fp site meta add

Add a meta field.

~~~
fp site meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp site meta delete

Delete a meta field.

~~~
fp site meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### fp site meta get

Get meta field value.

~~~
fp site meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---



### fp site meta list

List all metadata associated with an object.

~~~
fp site meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>] [--unserialize]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

	[--unserialize]
		Unserialize meta_value output.



### fp site meta patch

Update a nested value for a meta field.

~~~
fp site meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp site meta pluck

Get a nested value from a meta field.

~~~
fp site meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### fp site meta update

Update a meta field.

~~~
fp site meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp site option

Adds, updates, deletes, and lists site options in a multisite installation.

~~~
fp site option
~~~

**EXAMPLES**

    # Get site registration
    $ fp site option get registration
    none

    # Add site option
    $ fp site option add my_option foobar
    Success: Added 'my_option' site option.

    # Update site option
    $ fp site option update my_option '{"foo": "bar"}' --format=json
    Success: Updated 'my_option' site option.

    # Delete site option
    $ fp site option delete my_option
    Success: Deleted 'my_option' site option.





### fp site private

Sets one or more sites as private.

~~~
fp site private [<id>...] [--slug=<slug>]
~~~

**OPTIONS**

	[<id>...]
		One or more IDs of sites to set as private. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the site to be set as private. Subdomain on subdomain installs, directory on subdirectory installs.

**EXAMPLES**

    $ fp site private 123
    Success: Site 123 marked as private.

    $ fp site private --slug=demo
    Success: Site 123 marked as private.



### fp site public

Sets one or more sites as public.

~~~
fp site public [<id>...] [--slug=<slug>]
~~~

**OPTIONS**

	[<id>...]
		One or more IDs of sites to set as public. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the site to be set as public. Subdomain on subdomain installs, directory on subdirectory installs.

**EXAMPLES**

    $ fp site public 123
    Success: Site 123 marked as public.

     $ fp site public --slug=demo
     Success: Site 123 marked as public.



### fp site spam

Marks one or more sites as spam.

~~~
fp site spam [<id>...] [--slug=<slug>]
~~~

**OPTIONS**

	[<id>...]
		One or more IDs of sites to be marked as spam. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the site to be marked as spam. Subdomain on subdomain installs, directory on subdirectory installs.

**EXAMPLES**

    $ fp site spam 123
    Success: Site 123 marked as spam.



### fp site unarchive

Unarchives one or more sites.

~~~
fp site unarchive [<id>...] [--slug=<slug>]
~~~

**OPTIONS**

	[<id>...]
		One or more IDs of sites to unarchive. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the site to unarchive. Subdomain on subdomain installs, directory on subdirectory installs.

**EXAMPLES**

    $ fp site unarchive 123
    Success: Site 123 unarchived.

    $ fp site unarchive --slug=demo
    Success: Site 123 unarchived.



### fp site unmature

Sets one or more sites as immature.

~~~
fp site unmature [<id>...] [--slug=<slug>]
~~~

**OPTIONS**

	[<id>...]
		One or more IDs of sites to set as unmature. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the site to be set as unmature. Subdomain on subdomain installs, directory on subdirectory installs.

**EXAMPLES**

    $ fp site unmature 123
    Success: Site 123 marked as unmature.

    $ fp site unmature --slug=demo
    Success: Site 123 marked as unmature.



### fp site unspam

Removes one or more sites from spam.

~~~
fp site unspam [<id>...] [--slug=<slug>]
~~~

**OPTIONS**

	[<id>...]
		One or more IDs of sites to remove from spam. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the site to be removed from spam. Subdomain on subdomain installs, directory on subdirectory installs.

**EXAMPLES**

    $ fp site unspam 123
    Success: Site 123 removed from spam.



### fp taxonomy

Retrieves information about registered taxonomies.

~~~
fp taxonomy
~~~

See references for [built-in taxonomies](https://developer.finpress.org/themes/basics/categories-tags-custom-taxonomies/) and [custom taxonomies](https://developer.finpress.org/plugins/taxonomies/working-with-custom-taxonomies/).

**EXAMPLES**

    # List all taxonomies with 'post' object type.
    $ fp taxonomy list --object_type=post --fields=name,public
    +-------------+--------+
    | name        | public |
    +-------------+--------+
    | category    | 1      |
    | post_tag    | 1      |
    | post_format | 1      |
    +-------------+--------+

    # Get capabilities of 'post_tag' taxonomy.
    $ fp taxonomy get post_tag --field=cap
    {"manage_terms":"manage_categories","edit_terms":"manage_categories","delete_terms":"manage_categories","assign_terms":"edit_posts"}



### fp taxonomy get

Gets details about a registered taxonomy.

~~~
fp taxonomy get <taxonomy> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy slug.

	[--field=<field>]
		Instead of returning the whole taxonomy, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for the specified taxonomy:

* name
* label
* description
* object_type
* show_tagcloud
* hierarchical
* public
* labels
* cap

These fields are optionally available:

* count

**EXAMPLES**

    # Get details of `category` taxonomy.
    $ fp taxonomy get category --fields=name,label,object_type
    +-------------+------------+
    | Field       | Value      |
    +-------------+------------+
    | name        | category   |
    | label       | Categories |
    | object_type | ["post"]   |
    +-------------+------------+

    # Get capabilities of 'post_tag' taxonomy.
    $ fp taxonomy get post_tag --field=cap
    {"manage_terms":"manage_categories","edit_terms":"manage_categories","delete_terms":"manage_categories","assign_terms":"edit_posts"}



### fp taxonomy list

Lists registered taxonomies.

~~~
fp taxonomy list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--<field>=<value>]
		Filter by one or more fields (see get_taxonomies() first parameter for a list of available fields).

	[--field=<field>]
		Prints the value of a single field for each taxonomy.

	[--fields=<fields>]
		Limit the output to specific taxonomy fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each term:

* name
* label
* description
* object_type
* show_tagcloud
* hierarchical
* public

These fields are optionally available:

* count

**EXAMPLES**

    # List all taxonomies.
    $ fp taxonomy list --format=csv
    name,label,description,object_type,show_tagcloud,hierarchical,public
    category,Categories,,post,1,1,1
    post_tag,Tags,,post,1,,1
    nav_menu,"Navigation Menus",,nav_menu_item,,,
    link_category,"Link Categories",,link,1,,
    post_format,Format,,post,,,1

    # List all taxonomies with 'post' object type.
    $ fp taxonomy list --object_type=post --fields=name,public
    +-------------+--------+
    | name        | public |
    +-------------+--------+
    | category    | 1      |
    | post_tag    | 1      |
    | post_format | 1      |
    +-------------+--------+



### fp term

Manages taxonomy terms and term meta, with create, delete, and list commands.

~~~
fp term
~~~

See reference for [taxonomies and their terms](https://codex.finpress.org/Taxonomies).

**EXAMPLES**

    # Create a new term.
    $ fp term create category Apple --description="A type of fruit"
    Success: Created category 199.

    # Get details about a term.
    $ fp term get category 199 --format=json --fields=term_id,name,slug,count
    {"term_id":199,"name":"Apple","slug":"apple","count":1}

    # Update an existing term.
    $ fp term update category 15 --name=Apple
    Success: Term updated.

    # Get the term's URL.
    $ fp term list post_tag --include=123 --field=url
    http://example.com/tag/tips-and-tricks

    # Delete post category
    $ fp term delete category 15
    Success: Deleted category 15.

    # Recount posts assigned to each categories and tags
    $ fp term recount category post_tag
    Success: Updated category term count
    Success: Updated post_tag term count



### fp term create

Creates a new term.

~~~
fp term create <taxonomy> <term> [--slug=<slug>] [--description=<description>] [--parent=<term-id>] [--porcelain]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy for the new term.

	<term>
		A name for the new term.

	[--slug=<slug>]
		A unique slug for the new term. Defaults to sanitized version of name.

	[--description=<description>]
		A description for the new term.

	[--parent=<term-id>]
		A parent for the new term.

	[--porcelain]
		Output just the new term id.

**EXAMPLES**

    # Create a new category "Apple" with a description.
    $ fp term create category Apple --description="A type of fruit"
    Success: Created category 199.



### fp term delete

Deletes an existing term.

~~~
fp term delete <taxonomy> <term>... [--by=<field>]
~~~

Errors if the term doesn't exist, or there was a problem in deleting it.

**OPTIONS**

	<taxonomy>
		Taxonomy of the term to delete.

	<term>...
		One or more IDs or slugs of terms to delete.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: id
		options:
		  - slug
		  - id
		---

**EXAMPLES**

    # Delete post category by id
    $ fp term delete category 15
    Deleted category 15.
    Success: Deleted 1 of 1 terms.

    # Delete post category by slug
    $ fp term delete category apple --by=slug
    Deleted category 15.
    Success: Deleted 1 of 1 terms.

    # Delete all post tags
    $ fp term list post_tag --field=term_id | xargs fp term delete post_tag
    Deleted post_tag 159.
    Deleted post_tag 160.
    Deleted post_tag 161.
    Success: Deleted 3 of 3 terms.



### fp term generate

Generates some terms.

~~~
fp term generate <taxonomy> [--count=<number>] [--max_depth=<number>] [--format=<format>]
~~~

Creates a specified number of new terms with dummy data.

**OPTIONS**

	<taxonomy>
		The taxonomy for the generated terms.

	[--count=<number>]
		How many terms to generate?
		---
		default: 100
		---

	[--max_depth=<number>]
		Generate child terms down to a certain depth.
		---
		default: 1
		---

	[--format=<format>]
		Render output in a particular format.
		---
		default: progress
		options:
		  - progress
		  - ids
		---

**EXAMPLES**

    # Generate post categories.
    $ fp term generate category --count=10
    Generating terms  100% [=========] 0:02 / 0:02

    # Add meta to every generated term.
    $ fp term generate category --format=ids --count=3 | xargs -d ' ' -I % fp term meta add % foo bar
    Success: Added custom field.
    Success: Added custom field.
    Success: Added custom field.



### fp term get

Gets details about a term.

~~~
fp term get <taxonomy> <term> [--by=<field>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy of the term to get

	<term>
		ID or slug of the term to get

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: id
		options:
		  - slug
		  - id
		---

	[--field=<field>]
		Instead of returning the whole term, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get details about a category with id 199.
    $ fp term get category 199 --format=json
    {"term_id":199,"name":"Apple","slug":"apple","term_group":0,"term_taxonomy_id":199,"taxonomy":"category","description":"A type of fruit","parent":0,"count":0,"filter":"raw"}

    # Get details about a category with slug apple.
    $ fp term get category apple --by=slug --format=json
    {"term_id":199,"name":"Apple","slug":"apple","term_group":0,"term_taxonomy_id":199,"taxonomy":"category","description":"A type of fruit","parent":0,"count":0,"filter":"raw"}



### fp term list

Lists terms in a taxonomy.

~~~
fp term list <taxonomy>... [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<taxonomy>...
		List terms of one or more taxonomies

	[--<field>=<value>]
		Filter by one or more fields (see get_terms() $args parameter for a list of fields).

	[--field=<field>]
		Prints the value of a single field for each term.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - ids
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each term:

* term_id
* term_taxonomy_id
* name
* slug
* description
* parent
* count

These fields are optionally available:

* url

**EXAMPLES**

    # List post categories
    $ fp term list category --format=csv
    term_id,term_taxonomy_id,name,slug,description,parent,count
    2,2,aciform,aciform,,0,1
    3,3,antiquarianism,antiquarianism,,0,1
    4,4,arrangement,arrangement,,0,1
    5,5,asmodeus,asmodeus,,0,1

    # List post tags
    $ fp term list post_tag --fields=name,slug
    +-----------+-------------+
    | name      | slug        |
    +-----------+-------------+
    | 8BIT      | 8bit        |
    | alignment | alignment-2 |
    | Articles  | articles    |
    | aside     | aside       |
    +-----------+-------------+



### fp term meta

Adds, updates, deletes, and lists term custom fields.

~~~
fp term meta
~~~

**EXAMPLES**

    # Set term meta
    $ fp term meta set 123 bio "Mary is a FinPress developer."
    Success: Updated custom field 'bio'.

    # Get term meta
    $ fp term meta get 123 bio
    Mary is a FinPress developer.

    # Update term meta
    $ fp term meta update 123 bio "Mary is an awesome FinPress developer."
    Success: Updated custom field 'bio'.

    # Delete term meta
    $ fp term meta delete 123 bio
    Success: Deleted custom field.





### fp term meta add

Add a meta field.

~~~
fp term meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp term meta delete

Delete a meta field.

~~~
fp term meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### fp term meta get

Get meta field value.

~~~
fp term meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---



### fp term meta list

List all metadata associated with an object.

~~~
fp term meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>] [--unserialize]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

	[--unserialize]
		Unserialize meta_value output.



### fp term meta patch

Update a nested value for a meta field.

~~~
fp term meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp term meta pluck

Get a nested value from a meta field.

~~~
fp term meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### fp term meta update

Update a meta field.

~~~
fp term meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp term recount

Recalculates number of posts assigned to each term.

~~~
fp term recount <taxonomy>...
~~~

In instances where manual updates are made to the terms assigned to
posts in the database, the number of posts associated with a term
can become out-of-sync with the actual number of posts.

This command runs fp_update_term_count() on the taxonomy's terms
to bring the count back to the correct value.

**OPTIONS**

	<taxonomy>...
		One or more taxonomies to recalculate.

**EXAMPLES**

    # Recount posts assigned to each categories and tags
    $ fp term recount category post_tag
    Success: Updated category term count.
    Success: Updated post_tag term count.

    # Recount all listed taxonomies
    $ fp taxonomy list --field=name | xargs fp term recount
    Success: Updated category term count.
    Success: Updated post_tag term count.
    Success: Updated nav_menu term count.
    Success: Updated link_category term count.
    Success: Updated post_format term count.



### fp term update

Updates an existing term.

~~~
fp term update <taxonomy> <term> [--by=<field>] [--name=<name>] [--slug=<slug>] [--description=<description>] [--parent=<term-id>]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy of the term to update.

	<term>
		ID or slug for the term to update.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: id
		options:
		  - slug
		  - id
		---

	[--name=<name>]
		A new name for the term.

	[--slug=<slug>]
		A new slug for the term.

	[--description=<description>]
		A new description for the term.

	[--parent=<term-id>]
		A new parent for the term.

**EXAMPLES**

    # Change category with id 15 to use the name "Apple"
    $ fp term update category 15 --name=Apple
    Success: Term updated.

    # Change category with slug apple to use the name "Apple"
    $ fp term update category apple --by=slug --name=Apple
    Success: Term updated.



### fp user

Manages users, along with their roles, capabilities, and meta.

~~~
fp user
~~~

See references for [Roles and Capabilities](https://codex.finpress.org/Roles_and_Capabilities) and [FP User class](https://codex.finpress.org/Class_Reference/FP_User).

**EXAMPLES**

    # List user IDs
    $ fp user list --field=ID
    1

    # Create a new user.
    $ fp user create bob bob@example.com --role=author
    Success: Created user 3.
    Password: k9**&I4vNH(&

    # Update an existing user.
    $ fp user update 123 --display_name=Mary --user_pass=marypass
    Success: Updated user 123.

    # Delete user 123 and reassign posts to user 567
    $ fp user delete 123 --reassign=567
    Success: Removed user 123 from http://example.com.



### fp user add-cap

Adds a capability to a user.

~~~
fp user add-cap <user> <cap>
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	<cap>
		The capability to add.

**EXAMPLES**

    # Add a capability for a user
    $ fp user add-cap john create_premium_item
    Success: Added 'create_premium_item' capability for john (16).

    # Add a capability for a user
    $ fp user add-cap 15 edit_product
    Success: Added 'edit_product' capability for johndoe (15).



### fp user add-role

Adds a role for a user.

~~~
fp user add-role <user> [<role>...]
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	[<role>...]
		Add the specified role(s) to the user.

**EXAMPLES**

    $ fp user add-role 12 author
    Success: Added 'author' role for johndoe (12).

    $ fp user add-role 12 author editor
    Success: Added 'author', 'editor' roles for johndoe (12).



### fp user application-password

Creates, updates, deletes, lists and retrieves application passwords.

~~~
fp user application-password
~~~

**EXAMPLES**

    # List user application passwords and only show app name and password hash
    $ fp user application-password list 123 --fields=name,password
    +--------+------------------------------------+
    | name   | password                           |
    +--------+------------------------------------+
    | myapp  | $P$BVGeou1CUot114YohIemgpwxQCzb8O/ |
    +--------+------------------------------------+

    # Get a specific application password and only show app name and created timestamp
    $ fp user application-password get 123 6633824d-c1d7-4f79-9dd5-4586f734d69e --fields=name,created
    +--------+------------+
    | name   | created    |
    +--------+------------+
    | myapp  | 1638395611 |
    +--------+------------+

    # Create user application password
    $ fp user application-password create 123 myapp
    Success: Created application password.
    Password: ZG1bxdxdzjTwhsY8vK8l1C65

    # Only print the password without any chrome
    $ fp user application-password create 123 myapp --porcelain
    ZG1bxdxdzjTwhsY8vK8l1C65

    # Update an existing application password
    $ fp user application-password update 123 6633824d-c1d7-4f79-9dd5-4586f734d69e --name=newappname
    Success: Updated application password.

    # Delete an existing application password
    $ fp user application-password delete 123 6633824d-c1d7-4f79-9dd5-4586f734d69e
    Success: Deleted 1 of 1 application password.

    # Check if an application password for a given application exists
    $ fp user application-password exists 123 myapp
    $ echo $?
    1

    # Bash script for checking whether an application password exists and creating one if not
    if ! fp user application-password exists 123 myapp; then
        PASSWORD=$(fp user application-password create 123 myapp --porcelain)
    fi





### fp user application-password create

Creates a new application password.

~~~
fp user application-password create <user> <app-name> [--app-id=<app-id>] [--porcelain]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to create a new application password for.

	<app-name>
		Unique name of the application to create an application password for.

	[--app-id=<app-id>]
		Application ID to attribute to the application password.

	[--porcelain]
		Output just the new password.

**EXAMPLES**

    # Create user application password
    $ fp user application-password create 123 myapp
    Success: Created application password.
    Password: ZG1bxdxdzjTwhsY8vK8l1C65

    # Only print the password without any chrome
    $ fp user application-password create 123 myapp --porcelain
    ZG1bxdxdzjTwhsY8vK8l1C65

    # Create user application with a custom application ID for internal tracking
    $ fp user application-password create 123 myapp --app-id=42 --porcelain
    ZG1bxdxdzjTwhsY8vK8l1C65



### fp user application-password delete

Delete an existing application password.

~~~
fp user application-password delete <user> [<uuid>...] [--all]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to delete the application password for.

	[<uuid>...]
		Comma-separated list of UUIDs of the application passwords to delete.

	[--all]
		Delete all of the user's application password.

**EXAMPLES**

    # Delete an existing application password
    $ fp user application-password delete 123 6633824d-c1d7-4f79-9dd5-4586f734d69e
    Success: Deleted 1 of 1 application password.

    # Delete all of the user's application passwords
    $ fp user application-password delete 123 --all
    Success: Deleted all application passwords.



### fp user application-password exists

Checks whether an application password for a given application exists.

~~~
fp user application-password exists <user> <app-name>
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to check the existence of an application password for.

	<app-name>
		Name of the application to check the existence of an application password for.

**EXAMPLES**

    # Check if an application password for a given application exists
    $ fp user application-password exists 123 myapp
    $ echo $?
    1

    # Bash script for checking whether an application password exists and creating one if not
    if ! fp user application-password exists 123 myapp; then
        PASSWORD=$(fp user application-password create 123 myapp --porcelain)
    fi



### fp user application-password get

Gets a specific application password.

~~~
fp user application-password get <user> <uuid> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to get the application password for.

	<uuid>
		The universally unique ID of the application password.

	[--field=<field>]
		Prints the value of a single field for the application password.

	[--fields=<fields>]
		Limit the output to specific fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get a specific application password and only show app name and created timestamp
    $ fp user application-password get 123 6633824d-c1d7-4f79-9dd5-4586f734d69e --fields=name,created
    +--------+------------+
    | name   | created    |
    +--------+------------+
    | myapp  | 1638395611 |
    +--------+------------+



### fp user application-password list

Lists all application passwords associated with a user.

~~~
fp user application-password list <user> [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to get application passwords for.

	[--<field>=<value>]
		Filter the list by a specific field.

	[--field=<field>]
		Prints the value of a single field for each application password.

	[--fields=<fields>]
		Limit the output to specific fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		  - ids
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: created
		options:
		 - uuid
		 - app_id
		 - name
		 - password
		 - created
		 - last_used
		 - last_ip
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: desc
		options:
		 - asc
		 - desc
		---

**EXAMPLES**

    # List user application passwords and only show app name and password hash
    $ fp user application-password list 123 --fields=name,password
    +--------+------------------------------------+
    | name   | password                           |
    +--------+------------------------------------+
    | myapp  | $P$BVGeou1CUot114YohIemgpwxQCzb8O/ |
    +--------+------------------------------------+



### fp user application-password record-usage

Record usage of an application password.

~~~
fp user application-password record-usage <user> <uuid>
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to update the application password for.

	<uuid>
		The universally unique ID of the application password.

**EXAMPLES**

    # Record usage of an application password
    $ fp user application-password record-usage 123 6633824d-c1d7-4f79-9dd5-4586f734d69e
    Success: Recorded application password usage.



### fp user application-password update

Updates an existing application password.

~~~
fp user application-password update <user> <uuid> [--<field>=<value>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to update the application password for.

	<uuid>
		The universally unique ID of the application password.

	[--<field>=<value>]
		Update the <field> with a new <value>. Currently supported fields: name.

**EXAMPLES**

    # Update an existing application password
    $ fp user application-password update 123 6633824d-c1d7-4f79-9dd5-4586f734d69e --name=newappname
    Success: Updated application password.



### fp user create

Creates a new user.

~~~
fp user create <user-login> <user-email> [--role=<role>] [--user_pass=<password>] [--user_registered=<yyyy-mm-dd-hh-ii-ss>] [--display_name=<name>] [--user_nicename=<nice_name>] [--user_url=<url>] [--nickname=<nickname>] [--first_name=<first_name>] [--last_name=<last_name>] [--description=<description>] [--rich_editing=<rich_editing>] [--send-email] [--porcelain]
~~~

**OPTIONS**

	<user-login>
		The login of the user to create.

	<user-email>
		The email address of the user to create.

	[--role=<role>]
		The role of the user to create. Default: default role. Possible values
		include 'administrator', 'editor', 'author', 'contributor', 'subscriber'.

	[--user_pass=<password>]
		The user password. Default: randomly generated.

	[--user_registered=<yyyy-mm-dd-hh-ii-ss>]
		The date the user registered. Default: current date.

	[--display_name=<name>]
		The display name.

	[--user_nicename=<nice_name>]
		A string that contains a URL-friendly name for the user. The default is the user's username.

	[--user_url=<url>]
		A string containing the user's URL for the user's web site.

	[--nickname=<nickname>]
		The user's nickname, defaults to the user's username.

	[--first_name=<first_name>]
		The user's first name.

	[--last_name=<last_name>]
		The user's last name.

	[--description=<description>]
		A string containing content about the user.

	[--rich_editing=<rich_editing>]
		A string for whether to enable the rich editor or not. False if not empty.

	[--send-email]
		Send an email to the user with their new account details.

	[--porcelain]
		Output just the new user id.

**EXAMPLES**

    # Create user
    $ fp user create bob bob@example.com --role=author
    Success: Created user 3.
    Password: k9**&I4vNH(&

    # Create user without showing password upon success
    $ fp user create ann ann@example.com --porcelain
    4



### fp user delete

Deletes one or more users from the current site.

~~~
fp user delete <user>... [--network] [--reassign=<user-id>] [--yes]
~~~

On multisite, `fp user delete` only removes the user from the current
site. Include `--network` to also remove the user from the database, but
make sure to reassign their posts prior to deleting the user.

**OPTIONS**

	<user>...
		The user login, user email, or user ID of the user(s) to delete.

	[--network]
		On multisite, delete the user from the entire network.

	[--reassign=<user-id>]
		User ID to reassign the posts to.

	[--yes]
		Answer yes to any confirmation prompts.

**EXAMPLES**

    # Delete user 123 and reassign posts to user 567
    $ fp user delete 123 --reassign=567
    Success: Removed user 123 from http://example.com.

    # Delete all contributors and reassign their posts to user 2
    $ fp user delete $(fp user list --role=contributor --field=ID) --reassign=2
    Success: Removed user 813 from http://example.com.
    Success: Removed user 578 from http://example.com.

    # Delete all contributors in batches of 100 (avoid error: argument list too long: fp)
    $ fp user delete $(fp user list --role=contributor --field=ID | head -n 100)



### fp user exists

Verifies whether a user exists.

~~~
fp user exists <id>
~~~

Displays a success message if the user does exist.

**OPTIONS**

	<id>
		The ID of the user to check.

**EXAMPLES**

    # The user exists.
    $ fp user exists 1337
    Success: User with ID 1337 exists.
    $ echo $?
    0

    # The user does not exist.
    $ fp user exists 10000
    $ echo $?
    1



### fp user generate

Generates some users.

~~~
fp user generate [--count=<number>] [--role=<role>] [--format=<format>]
~~~

Creates a specified number of new users with dummy data.

**OPTIONS**

	[--count=<number>]
		How many users to generate?
		---
		default: 100
		---

	[--role=<role>]
		The role of the generated users. Default: default role from FP

	[--format=<format>]
		Render output in a particular format.
		---
		default: progress
		options:
		  - progress
		  - ids
		---

**EXAMPLES**

    # Add meta to every generated users.
    $ fp user generate --format=ids --count=3 | xargs -d ' ' -I % fp user meta add % foo bar
    Success: Added custom field.
    Success: Added custom field.
    Success: Added custom field.



### fp user get

Gets details about a user.

~~~
fp user get <user> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	[--field=<field>]
		Instead of returning the whole user, returns the value of a single field.

	[--fields=<fields>]
		Get a specific subset of the user's fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get user
    $ fp user get 12 --field=login
    supervisor

    # Get user and export to JSON file
    $ fp user get bob --format=json > bob.json



### fp user import-csv

Imports users from a CSV file.

~~~
fp user import-csv <file> [--send-email] [--skip-update]
~~~

If the user already exists (matching the email address or login), then
the user is updated unless the `--skip-update` flag is used.

**OPTIONS**

	<file>
		The local or remote CSV file of users to import. If '-', then reads from STDIN.

	[--send-email]
		Send an email to new users with their account details.

	[--skip-update]
		Don't update users that already exist.

**EXAMPLES**

    # Import users from local CSV file
    $ fp user import-csv /path/to/users.csv
    Success: bobjones created.
    Success: newuser1 created.
    Success: existinguser created.

    # Import users from remote CSV file
    $ fp user import-csv http://example.com/users.csv

    Sample users.csv file:

    user_login,user_email,display_name,role
    bobjones,bobjones@example.com,Bob Jones,contributor
    newuser1,newuser1@example.com,New User,author
    existinguser,existinguser@example.com,Existing User,administrator



### fp user list

Lists users.

~~~
fp user list [--role=<role>] [--<field>=<value>] [--network] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

Display FinPress users based on all arguments supported by
[FP_User_Query()](https://developer.finpress.org/reference/classes/fp_user_query/prepare_query/).

**OPTIONS**

	[--role=<role>]
		Only display users with a certain role.

	[--<field>=<value>]
		Control output by one or more arguments of FP_User_Query().

	[--network]
		List all users in the network for multisite.

	[--field=<field>]
		Prints the value of a single field for each user.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - ids
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each user:

* ID
* user_login
* display_name
* user_email
* user_registered
* roles

These fields are optionally available:

* user_pass
* user_nicename
* user_url
* user_activation_key
* user_status
* spam
* deleted
* caps
* cap_key
* allcaps
* filter
* url

**EXAMPLES**

    # List user IDs
    $ fp user list --field=ID
    1

    # List users with administrator role
    $ fp user list --role=administrator --format=csv
    ID,user_login,display_name,user_email,user_registered,roles
    1,supervisor,supervisor,supervisor@gmail.com,"2016-06-03 04:37:00",administrator

    # List users with only given fields
    $ fp user list --fields=display_name,user_email --format=json
    [{"display_name":"supervisor","user_email":"supervisor@gmail.com"}]

    # List users ordered by the 'last_activity' meta value.
    $ fp user list --meta_key=last_activity --orderby=meta_value_num



### fp user list-caps

Lists all capabilities for a user.

~~~
fp user list-caps <user> [--format=<format>] [--origin=<origin>] [--exclude-role-names]
~~~

**OPTIONS**

	<user>
		User ID, user email, or login.

	[--format=<format>]
		Render output in a particular format.
		---
		default: list
		options:
		  - list
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		---

	[--origin=<origin>]
		Render output in a particular format.
		---
		default: all
		options:
		  - all
		  - user
		  - role
		---

	[--exclude-role-names]
		Exclude capabilities that match role names from output.

**EXAMPLES**

    $ fp user list-caps 21
    edit_product
    create_premium_item



### fp user meta

Adds, updates, deletes, and lists user custom fields.

~~~
fp user meta
~~~

**EXAMPLES**

    # Add user meta
    $ fp user meta add 123 bio "Mary is an FinPress developer."
    Success: Added custom field.

    # List user meta
    $ fp user meta list 123 --keys=nickname,description,fp_capabilities
    +---------+-----------------+--------------------------------+
    | user_id | meta_key        | meta_value                     |
    +---------+-----------------+--------------------------------+
    | 123     | nickname        | supervisor                     |
    | 123     | description     | Mary is a FinPress developer. |
    | 123     | fp_capabilities | {"administrator":true}         |
    +---------+-----------------+--------------------------------+

    # Update user meta
    $ fp user meta update 123 bio "Mary is an awesome FinPress developer."
    Success: Updated custom field 'bio'.

    # Delete user meta
    $ fp user meta delete 123 bio
    Success: Deleted custom field.





### fp user meta add

Adds a meta field.

~~~
fp user meta add <user> <key> <value> [--format=<format>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to add metadata for.

	<key>
		The metadata key.

	<value>
		The new metadata value.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---

**EXAMPLES**

    # Add user meta
    $ fp user meta add 123 bio "Mary is an FinPress developer."
    Success: Added custom field.



### fp user meta delete

Deletes a meta field.

~~~
fp user meta delete <user> <key> [<value>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to delete metadata from.

	<key>
		The metadata key.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

**EXAMPLES**

    # Delete user meta
    $ fp user meta delete 123 bio
    Success: Deleted custom field.



### fp user meta get

Gets meta field value.

~~~
fp user meta get <user> <key> [--format=<format>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to get metadata for.

	<key>
		The metadata key.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get user meta
    $ fp user meta get 123 bio
    Mary is an FinPress developer.

    # Get the primary site of a user (for multisite)
    $ fp user meta get 2 primary_blog
    3



### fp user meta list

Lists all metadata associated with a user.

~~~
fp user meta list <user> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>] [--unserialize]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to get metadata for.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

	[--unserialize]
		Unserialize meta_value output.

**EXAMPLES**

    # List user meta
    $ fp user meta list 123 --keys=nickname,description,fp_capabilities
    +---------+-----------------+--------------------------------+
    | user_id | meta_key        | meta_value                     |
    +---------+-----------------+--------------------------------+
    | 123     | nickname        | supervisor                     |
    | 123     | description     | Mary is a FinPress developer. |
    | 123     | fp_capabilities | {"administrator":true}         |
    +---------+-----------------+--------------------------------+



### fp user meta patch

Update a nested value for a meta field.

~~~
fp user meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### fp user meta pluck

Get a nested value from a meta field.

~~~
fp user meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### fp user meta update

Updates a meta field.

~~~
fp user meta update <user> <key> <value> [--format=<format>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to update metadata for.

	<key>
		The metadata key.

	<value>
		The new metadata value.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---

**EXAMPLES**

    # Update user meta
    $ fp user meta update 123 bio "Mary is an awesome FinPress developer."
    Success: Updated custom field 'bio'.



### fp user remove-cap

Removes a user's capability.

~~~
fp user remove-cap <user> <cap>
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	<cap>
		The capability to be removed.

**EXAMPLES**

    $ fp user remove-cap 11 publish_newsletters
    Success: Removed 'publish_newsletters' cap for supervisor (11).

    $ fp user remove-cap 11 publish_posts
    Error: The 'publish_posts' cap for supervisor (11) is inherited from a role.

    $ fp user remove-cap 11 nonexistent_cap
    Error: No such 'nonexistent_cap' cap for supervisor (11).



### fp user remove-role

Removes a user's role.

~~~
fp user remove-role <user> [<role>...]
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	[<role>...]
		Remove the specified role(s) from the user.

**EXAMPLES**

    $ fp user remove-role 12 author
    Success: Removed 'author' role for johndoe (12).

    $ fp user remove-role 12 author editor
    Success: Removed 'author', 'editor' roles for johndoe (12).



### fp user reset-password

Resets the password for one or more users.

~~~
fp user reset-password <user>... [--skip-email] [--show-password] [--porcelain]
~~~

**OPTIONS**

	<user>...
		one or more user logins or IDs.

	[--skip-email]
		Don't send an email notification to the affected user(s).

	[--show-password]
		Show the new password(s).

	[--porcelain]
		Output only the new password(s).

**EXAMPLES**

    # Reset the password for two users and send them the change email.
    $ fp user reset-password admin editor
    Reset password for admin.
    Reset password for editor.
    Success: Passwords reset for 2 users.

    # Reset and display the password.
    $ fp user reset-password editor --show-password
    Reset password for editor.
    Password: N6hAau0fXZMN#rLCIirdEGOh
    Success: Password reset for 1 user.

    # Reset the password for one user, displaying only the new password, and not sending the change email.
    $ fp user reset-password admin --skip-email --porcelain
    yV6BP*!d70wg

    # Reset password for all users.
    $ fp user reset-password $(fp user list --format=ids)
    Reset password for admin.
    Reset password for editor.
    Reset password for subscriber.
    Success: Passwords reset for 3 users.

    # Reset password for all users with a particular role.
    $ fp user reset-password $(fp user list --format=ids --role=administrator)
    Reset password for admin.
    Success: Password reset for 1 user.



### fp user session

Destroys and lists a user's sessions.

~~~
fp user session
~~~

**EXAMPLES**

    # List a user's sessions.
    $ fp user session list admin@example.com --format=csv
    login_time,expiration_time,ip,ua
    "2016-01-01 12:34:56","2016-02-01 12:34:56",127.0.0.1,"Mozilla/5.0..."

    # Destroy the most recent session of the given user.
    $ fp user session destroy admin
    Success: Destroyed session. 3 sessions remaining.





### fp user session destroy

Destroy a session for the given user.

~~~
fp user session destroy <user> [<token>] [--all]
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	[<token>]
		The token of the session to destroy. Defaults to the most recently created session.

	[--all]
		Destroy all of the user's sessions.

**EXAMPLES**

    # Destroy the most recent session of the given user.
    $ fp user session destroy admin
    Success: Destroyed session. 3 sessions remaining.

    # Destroy a specific session of the given user.
    $ fp user session destroy admin e073ad8540a9c2...
    Success: Destroyed session. 2 sessions remaining.

    # Destroy all the sessions of the given user.
    $ fp user session destroy admin --all
    Success: Destroyed all sessions.

    # Destroy all sessions for all users.
    $ fp user list --field=ID | xargs -n 1 fp user session destroy --all
    Success: Destroyed all sessions.
    Success: Destroyed all sessions.



### fp user session list

List sessions for the given user.

~~~
fp user session list <user> [--fields=<fields>] [--format=<format>]
~~~

Note: The `token` field does not return the actual token, but a hash of
it. The real token is not persisted and can only be found in the
corresponding cookies on the client side.

**OPTIONS**

	<user>
		User ID, user email, or user login.

	[--fields=<fields>]
		Limit the output to specific fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each session:

* token
* login_time
* expiration_time
* ip
* ua

These fields are optionally available:

* expiration
* login

**EXAMPLES**

    # List a user's sessions.
    $ fp user session list admin@example.com --format=csv
    login_time,expiration_time,ip,ua
    "2016-01-01 12:34:56","2016-02-01 12:34:56",127.0.0.1,"Mozilla/5.0..."



### fp user set-role

Sets the user role.

~~~
fp user set-role <user> [<role>]
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	[<role>]
		Make the user have the specified role. If not passed, the default role is
		used.

**EXAMPLES**

    $ fp user set-role 12 author
    Success: Added johndoe (12) to http://example.com as author.



### fp user signup

Manages signups on a multisite installation.

~~~
fp user signup
~~~

**EXAMPLES**

    # List signups.
    $ fp user signup list
    +-----------+------------+---------------------+---------------------+--------+------------------+
    | signup_id | user_login | user_email          | registered          | active | activation_key   |
    +-----------+------------+---------------------+---------------------+--------+------------------+
    | 1         | bobuser    | bobuser@example.com | 2024-03-13 05:46:53 | 1      | 7320b2f009266618 |
    | 2         | johndoe    | johndoe@example.com | 2024-03-13 06:24:44 | 0      | 9068d859186cd0b5 |
    +-----------+------------+---------------------+---------------------+--------+------------------+

    # Activate signup.
    $ fp user signup activate 2
    Signup 2 activated. Password: bZFSGsfzb9xs
    Success: Activated 1 of 1 signups.

    # Delete signup.
    $ fp user signup delete 3
    Signup 3 deleted.
    Success: Deleted 1 of 1 signups.





### fp user signup activate

Activates one or more signups.

~~~
fp user signup activate <signup>...
~~~

**OPTIONS**

	<signup>...
		The signup ID, user login, user email, or activation key of the signup(s) to activate.

**EXAMPLES**

    # Activate signup.
    $ fp user signup activate 2
    Signup 2 activated. Password: bZFSGsfzb9xs
    Success: Activated 1 of 1 signups.



### fp user signup delete

Deletes one or more signups.

~~~
fp user signup delete [<signup>...] [--all]
~~~

**OPTIONS**

	[<signup>...]
		The signup ID, user login, user email, or activation key of the signup(s) to delete.

	[--all]
		If set, all signups will be deleted.

**EXAMPLES**

    # Delete signup.
    $ fp user signup delete 3
    Signup 3 deleted.
    Success: Deleted 1 of 1 signups.



### fp user signup get

Gets details about a signup.

~~~
fp user signup get <signup> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<signup>
		The signup ID, user login, user email, or activation key.

	[--field=<field>]
		Instead of returning the whole signup, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get signup.
    $ fp user signup get 1 --field=user_login
    bobuser

    # Get signup and export to JSON file.
    $ fp user signup get bobuser --format=json > bobuser.json



### fp user signup list

Lists signups.

~~~
fp user signup list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>] [--per_page=<per_page>]
~~~

	[--<field>=<value>]
		Filter the list by a specific field.

	[--field=<field>]
		Prints the value of a single field for each signup.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - ids
		  - json
		  - count
		  - yaml
		---

	[--per_page=<per_page>]
		Limits the signups to the given number. Defaults to none.

**AVAILABLE FIELDS**

These fields will be displayed by default for each signup:

* signup_id
* user_login
* user_email
* registered
* active
* activation_key

These fields are optionally available:

* domain
* path
* title
* activated
* meta

**EXAMPLES**

    # List signup IDs.
    $ fp user signup list --field=signup_id
    1

    # List all signups.
    $ fp user signup list
    +-----------+------------+---------------------+---------------------+--------+------------------+
    | signup_id | user_login | user_email          | registered          | active | activation_key   |
    +-----------+------------+---------------------+---------------------+--------+------------------+
    | 1         | bobuser    | bobuser@example.com | 2024-03-13 05:46:53 | 1      | 7320b2f009266618 |
    | 2         | johndoe    | johndoe@example.com | 2024-03-13 06:24:44 | 0      | 9068d859186cd0b5 |
    +-----------+------------+---------------------+---------------------+--------+------------------+



### fp user spam

Marks one or more users as spam on multisite.

~~~
fp user spam <user>...
~~~

**OPTIONS**

	<user>...
		The user login, user email, or user ID of the user(s) to mark as spam.

**EXAMPLES**

    # Mark user as spam.
    $ fp user spam 123
    User 123 marked as spam.
    Success: Spammed 1 of 1 users.



### fp user term

Adds, updates, removes, and lists user terms.

~~~
fp user term
~~~

**EXAMPLES**

    # Set user terms
    $ fp user term set 123 test category
    Success: Set terms.





### fp user term add

Add a term to an object.

~~~
fp user term add <id> <taxonomy> <term>... [--by=<field>]
~~~

Append the term to the existing set of terms on the object.

**OPTIONS**

	<id>
		The ID of the object.

	<taxonomy>
		The name of the taxonomy type to be added.

	<term>...
		The slug of the term or terms to be added.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: slug
		options:
		  - slug
		  - id
		---



### fp user term list

List all terms associated with an object.

~~~
fp user term list <id> <taxonomy>... [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		ID for the object.

	<taxonomy>...
		One or more taxonomies to list.

	[--field=<field>]
		Prints the value of a single field for each term.

	[--fields=<fields>]
		Limit the output to specific row fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each term:

* term_id
* name
* slug
* taxonomy

These fields are optionally available:

* term_taxonomy_id
* description
* term_group
* parent
* count



### fp user term remove

Remove a term from an object.

~~~
fp user term remove <id> <taxonomy> [<term>...] [--by=<field>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<taxonomy>
		The name of the term's taxonomy.

	[<term>...]
		The slug of the term or terms to be removed from the object.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: slug
		options:
		  - slug
		  - id
		---

	[--all]
		Remove all terms from the object.



### fp user term set

Set object terms.

~~~
fp user term set <id> <taxonomy> <term>... [--by=<field>]
~~~

Replaces existing terms on the object.

**OPTIONS**

	<id>
		The ID of the object.

	<taxonomy>
		The name of the taxonomy type to be updated.

	<term>...
		The slug of the term or terms to be updated.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: slug
		options:
		  - slug
		  - id
		---



### fp user unspam

Removes one or more users from spam on multisite.

~~~
fp user unspam <user>...
~~~

**OPTIONS**

	<user>...
		The user login, user email, or user ID of the user(s) to remove from spam.

**EXAMPLES**

    # Remove user from spam.
    $ fp user unspam 123
    User 123 removed from spam.
    Success: Unspamed 1 of 1 users.



### fp user update

Updates an existing user.

~~~
fp user update <user>... [--user_pass=<password>] [--user_nicename=<nice_name>] [--user_url=<url>] [--user_email=<email>] [--display_name=<display_name>] [--nickname=<nickname>] [--first_name=<first_name>] [--last_name=<last_name>] [--description=<description>] [--rich_editing=<rich_editing>] [--user_registered=<yyyy-mm-dd-hh-ii-ss>] [--role=<role>] --<field>=<value> [--skip-email]
~~~

**OPTIONS**

	<user>...
		The user login, user email or user ID of the user(s) to update.

	[--user_pass=<password>]
		A string that contains the plain text password for the user.

	[--user_nicename=<nice_name>]
		A string that contains a URL-friendly name for the user. The default is the user's username.

	[--user_url=<url>]
		A string containing the user's URL for the user's web site.

	[--user_email=<email>]
		A string containing the user's email address.

	[--display_name=<display_name>]
		A string that will be shown on the site. Defaults to user's username.

	[--nickname=<nickname>]
		The user's nickname, defaults to the user's username.

	[--first_name=<first_name>]
		The user's first name.

	[--last_name=<last_name>]
		The user's last name.

	[--description=<description>]
		A string containing content about the user.

	[--rich_editing=<rich_editing>]
		A string for whether to enable the rich editor or not. False if not empty.

	[--user_registered=<yyyy-mm-dd-hh-ii-ss>]
		The date the user registered.

	[--role=<role>]
		A string used to set the user's role.

	--<field>=<value>
		One or more fields to update. For accepted fields, see fp_update_user().

	[--skip-email]
		Don't send an email notification to the user.

**EXAMPLES**

    # Update user
    $ fp user update 123 --display_name=Mary --user_pass=marypass
    Success: Updated user 123.

## Installing

This package is included with FP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in FP-CLI, run:

    fp package install git@github.com:fp-cli/entity-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out FP-CLI's guide to contributing](https://make.finpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/fp-cli/entity-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/fp-cli/entity-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.finpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/fp-cli/entity-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.finpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.finpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://fp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `fp scaffold package-readme` ([doc](https://github.com/fp-cli/scaffold-package-command#fp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
