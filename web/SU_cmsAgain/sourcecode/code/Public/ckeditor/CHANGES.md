CKEditor 4 Changelog
====================

## CKEditor 4.8

**Important Notes:**

* [#1249](https://github.com/ckeditor/ckeditor-dev/issues/1249): Enabled the [Upload Image](https://ckeditor.com/cke4/addon/uploadimage) plugin by default in standard and full presets. Also, it will no longer log an error in case of missing [`config.imageUploadUrl`](https://docs.ckeditor.com/ckeditor4/docs/#!/api/CKEDITOR.config-cfg-imageUploadUrl) property.

New Features:

* [#933](https://github.com/ckeditor/ckeditor-dev/issues/933): Introduced [Balloon Toolbar](https://ckeditor.com/cke4/addon/balloontoolbar) plugin.
* [#662](https://github.com/ckeditor/ckeditor-dev/issues/662): Introduced image inlining for the [Paste from Word](https://ckeditor.com/cke4/addon/pastefromword) plugin.
* [#468](https://github.com/ckeditor/ckeditor-dev/issues/468): [Edge] Introduced support for the Clipboard API.
* [#607](https://github.com/ckeditor/ckeditor-dev/issues/607): Manually inserted Hex color is prefixed with a hash character (`#`) if needed. It ensures a valid Hex color value is used when setting the table cell border or background color with the [Color Dialog](https://ckeditor.com/cke4/addon/colordialog) window.
* [#584](https://github.com/ckeditor/ckeditor-dev/issues/584): [Font size and Family](https://ckeditor.com/cke4/addon/font) and [Format](https://ckeditor.com/cke4/addon/format) drop-downs are not toggleable anymore. Default option to reset styles added.
* [#856](https://github.com/ckeditor/ckeditor-dev/issues/856): Introduced the [`CKEDITOR.tools.keystrokeToArray`](https://docs.ckeditor.com/ckeditor4/docs/#!/api/CKEDITOR.tools-method-keystrokeToArray) method. It converts a keystroke into its string representation, returning every key name as a separate array element.
* [#1053](https://github.com/ckeditor/ckeditor-dev/issues/1053): Introduced the [`CKEDITOR.tools.object.merge`](https://docs.ckeditor.com/ckeditor4/docs/#!/api/CKEDITOR.tools.object-method-merge) method. It allows to merge two objects, returning the new object with all properties from both objects deeply cloned.
* [#1073](https://github.com/ckeditor/ckeditor-dev/issues/1073): Introduced the [`CKEDITOR.tools.array.every`](https://docs.ckeditor.com/ckeditor4/docs/#!/api/CKEDITOR.tools.array-method-every) method. It invokes a given test function on every array element and returns `true` if all elements pass the test.
