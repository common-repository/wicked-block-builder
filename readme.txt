=== Wicked Block Builder ===
Contributors: wickedplugins
Tags: blocks, block builder, Gutenberg, administration, developer
Requires PHP: 7.0
Requires at least: 5.8
Tested up to: 6.3
Stable tag: 1.4.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create your own custom blocks and patterns in as little as a few minutes!

== Description ==

Create your own custom blocks with Wicked Block Builder!  There’s no setup required and you can build blocks in as little as a few minutes.

https://www.youtube.com/watch?v=xZ18r-w7C9k

== Features ==

= Native Blocks =
Blocks created with Wicked Block Builder are truly native blocks that don't use server-side rendering.

= Build Custom Blocks =
Build blocks using your own semantic markup.  Simply drag-and-drop HTML elements and components to build your block in minutes.

= Make Your Block Editable =
Add interactive components such as rich text and images so you can edit your block’s content directly in the editor.

= Customize Your Block’s Sidebar =
Add text boxes, checkboxes, radio buttons, color palettes, and more to your block’s sidebar.  Add panels or HTML elements to organize the sidebar.

= Dynamic Blocks =
Optionally make your blocks dynamic and use PHP to output your block.  Easily access your block’s data via an argument containing your block’s attributes.

= Flexible Front-end View =
Save time and skip the step of creating a similar (but slightly different) view for the front-end of your block (i.e. the “save” function if you’re a developer).

= Style Editor =
Add your block’s styles in a convenient CSS editor.

= Block Patterns =
Create block patterns with no code and easily update them as needed.

== 🚀 Get More With Wicked Block Builder Pro ==
Take your blocks to the next level with these additional features in Wicked Block Builder Pro.  [Learn more about Wicked Block Builder Pro](https://wickedplugins.com/plugins/wicked-block-builder/?utm_source=readme&utm_campaign=wicked_block_builder&utm_content=pro_learn_more_link).

= Repeater =
Add repeaters to your block.  Add, sort, and delete any number of items in your block.  Nest repeaters for even greater functionality.

= Conditional Logic =
Use conditions to do incredible things with your block.  Conditionally add classes, inline styles, and HTML attributes.  Even change the output of your block based on conditional logic.

= PostSelect =
Add a PostSelect component to your block to let people select one or more posts and sort them.

= TermSelect =
Add a TermSelect component to your block to let people choose one or more terms.  Choose from different display types like checkboxes, radios, or dropdown.

= InnerBlocks =
Add an InnerBlocks component to nest blocks within your block.

= Export Blocks to Plugin =
Export your blocks to a stand-alone plugin.  Install the plugin on any WordPress site to use your blocks without needing to have Wicked Block Builder installed.

[Get Wicked Block Builder Pro](https://wickedplugins.com/plugins/wicked-block-builder/?utm_source=readme&utm_campaign=wicked_block_builder&utm_content=pro_get_link).

== Who is Wicked Block Builder for? ==
Wicked Block Builder is for anyone who wants to create blocks that can be used in the WordPress editor.  This includes non-technical people (there’s no programming required) but also developers.

Non-technical people will appreciate the intuitive drag-and-drop interface.  For developers, complete control over the block’s output, conditional logic (pro version only), automatic deprecations, dynamic blocks, and more make it a powerful must-have time-saving development tool.

== Installation ==

1. Upload 'wicked-block-builder' to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen by searching for 'Wicked Block Builder'.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. To add your first block, go to Wicked Block Builder > Add New

== Frequently Asked Questions ==

= Is there documentation? =
Yes!  You can [view the documentation here](https://wickedplugins.com/support/wicked-block-builder/?utm_source=readme&utm_campaign=wicked_block_builder&utm_content=documentation_faq).

= How do I create a block pattern? =
When editing a page, select the blocks that you want to convert to a pattern, click the three dots in the toolbar that appears above the blocks, and select 'Save as pattern'.

= Do I have to know HTML or CSS to use Wicked Block Builder? =
There is no programming or HTML editing required but a basic understanding of HTML and CSS will go a long way.  Blocks are created by dragging and dropping HTML elements to form the HTML structure of your block.  So it does help to have a basic understanding of how HTML tags work and are nested.  You don’t need to know CSS to create a block but you’ll be able to take your blocks farther and make them look better with a little bit of knowledge.  The good news is that both are easy to learn!  If you’re just getting started, check out [HTML & CSS – The VERY Basics](https://css-tricks.com/video-screencasts/58-html-css-the-very-basics/) from css-tricks.com.

= Why am I seeing “This block contains invalid or unexpected content” when my block is loaded in the editor? =
This happens when the HTML of your block has unexpectedly changed.  This can happen for a number of reasons including: you made changes to the structure of your block; the HTML of the block was changed after it was saved (for example, perhaps a filter ran that added an extra HTML attribute to one of your tags).  You can [read more about how to troubleshoot this issue in the documentation](https://wickedplugins.com/support/wicked-block-builder/troubleshooting/this-block-contains-unexpected-or-invalid-content/?utm_source=readme&utm_campaign=wicked_block_builder&utm_content=invalid_content_faq).

= Is there a limit to the number of blocks I can create? =
No, you can create as many blocks as you want.

= Will my blocks keep working if I deactivate the plugin? =
Not really.  Your blocks will still appear on the front-end; however, if you used the styled editor in the block builder to add your block’s CSS, your blocks will not appear correctly.  Also, you will see an error if you try to edit a page containing one of your blocks.  If you just need to deactivate Wicked Block Builder temporarily though, your blocks will still be there when you reactivate the plugin.

= Can I export and import blocks? =
Yes, blocks can be exported and imported by going to Wicked Block Builder > Home.

= Can blocks be synced to/from JSON? =
Yes.  Simply create a folder named wbb-json in your theme and make sure the folder is writable.  Blocks will automatically be saved to JSON when the wbb-json folder exists.  [Learn more about syncing blocks to JSON in the documentation](https://wickedplugins.com/support/wicked-block-builder/json-sync/).

== Screenshots ==

1. Configure your block's settings
2. Add various attribute types to your block
3. Drag and drop HTML elements and components to build the editor view of your block
4. Add classes, styles, and HTML attributes to elements
5. Drag and drop HTML elements and components to build your block's sidebar
6. Add CSS to your block
7. Sample block created with Wicked Block Builder

== Changelog ==

= 1.4.4 (March 21, 2024) =
* Fix: props.tokens is readonly error preventing blocks from working in some instances

= 1.4.3 (August 3, 2023) =
* New: autocomplete input option for TermSelect (Wicked Block Builder Pro)
* Tweak: InnerBlocks and DynamicPreview components can now be added to the root level of the block
* Tweak: change TermSelect to display all terms (previously only displayed first 100 terms)
* Fix: invalid file type error when importing block JSON file in certain environments
* Fix: changes to a duplicated block containing an array attribute update the original block's array attribute also
* Fix: deprecation warnings in PHP 8.2

= 1.4.2 (March 29, 2023) =
* New: ability to set a custom SVG icon for block
* Fix: icon popup selector not anchored to icon button in block settings
* Tweak: change default value field for boolean attributes to a radio selection of true or false instead of text field
* Tweak: remove 'checked by default' option from Toggle component. This can now be controlled by setting the default value for the attribute associated with the Toggle
* Tweak: remove multiline property from RichText component (WordPress has deprecated this property)

= 1.4.1 (March 3, 2023) =
* New: add block support option for color
* New: wbb_builder_api_get_post_types_args filter can be used to adjust the arguments used to query post types available to the PostSelect component
* New: add support for dynamic blocks to plugin export feature
* Fix: 'the plugin does not have a valid header' error when installing pro version via Zip file
* Fix: PostSelect not working in blocks exported to plugin
* Tweak: re-organize source JavaScript to reduce generator script file size
* Tweak: attributes in dynamic blocks can (and should) now be accessed via $attributes variable

= 1.4.0 (February 20, 2023) = 
* New: ability to copy/paste elements and components between screens and other blocks
* New: re-style block builder to appear more code-like
* New: InnerBlocks component now supports specifying an HTML tag
* New: DynamicPreview component lets you view a dynamic block in the editor
* Fix: block builder crashes when Image component field mapping panel is left open before accessing the settings of a different Image component
* Fix: Sidebar screen in builder doesn't indicate which item is selected
* Fix: extraneous empty space in block toolbar
* Tweak: show tag attributes and styles in markup in block builder
 
= 1.3.0 (January 20, 2023) =
* New: change tokens to insert at last cursor position
* New: add support for tokens in block classes
* New: ability to duplicate items in a repeater
* New: ability to restrict block to parent and ancestor blocks
* Fix: token inserter dropdown appearing underneath right sidebar in block builder
* Fix: conditions not working when using array attributes
* Fix: TermSelect settings mislabeled
* Fix: item labels containing ampersands displaying HTML named entity in repeater controller
* Tweak: strip semicolons from styles

= 1.2.5 (November 4, 2022) =
* Test with WordPress 6.1 and update tested-up-to version

= 1.2.4 (August 15, 2022) =
* Fix: deprecation warnings in PHP 8

= 1.2.3 (July 25, 2022) =
* Fix: block crashes if a ColorPalette's attribute doesn't have a default value 

= 1.2.2 (May 24, 2022) =
* Really update tested-up-to version this time 🤦

= 1.2.1 (May 24, 2022) =
* Test with WordPress 6.0 and update tested-up-to version
* Fix: cursor in wrong location when dragging attributes, elements, and components in block builder

= 1.2.0 (April 6, 2022) =
* New: ability to change block slug in settings
* New: ability to import and export blocks
* New: ability to duplicate blocks
* New: JSON sync feature allowing blocks to be written to JSON files
* Fix: HTML entities appearing in TermSelect
* Fix: dynamic blocks only being output once per page
* Fix: inline styles stripping double hyphen prefixes

= 1.1.0 (January 25, 2022) =
* New: ability to select a block icon
* New: toggle component
* New: dropdown component
* New: settings to control block features for anchor, custom class name, font size, line height, multiple, inserter, margin, and padding
* Fix: conditions sometimes not working inside of repeater
* Fix: incorrect attribute type in help text for radio component
* Fix: PHP warning caused by misspelling
* Tweak: move alignment to new 'Feature Support' section

= 1.0.1 (January 5, 2022) =
* Fix: incorrect version number in main plugin file

= 1.0.0 (January 5, 2022) =
* New: add ability to create block patterns
* New: add placeholder component for InnerBlocks
* Fix: block titles not appearing in REST API
* Fix: block content not being passed to dynamic blocks
* Fix: Wicked Block Builder admin menu not expanded when viewing block categories

= 0.1.0 (November 12, 2021) =
* 🎉 Beta release 🎉
