# Inline Success Messages

**IMPORTANT**: *Thanks to this GitHub Repo you can track bugfixes and keep your Inline Success Messages copy up to date, but keep in mind that this is a <strong>development version</strong>. Therefore, you may encounter errors and relevant bugs using this version, although I will try to leave its code as functional as possible.*

> **Current version** 1.0  
> **Dependencies** PluginLibrary, a library which contains useful PHP functions for MyBB  
> **Author** Shade  

## General

Inline Success Messages (from now on: ISM) replaces MyBB's default behavior when you update your User CP settings, returning a success message instead of a (un)friendly redirection page. It's inspired by IPBoard's systems.

ISM works **only** with 1.6 MyBB installations and comes with additional CSS styles loaded directly into its template.

If you have any feature request, suggestion, or you want to report any issue, please let me know opening a new issue on GitHub. Your contribute is very important to me and helps me into making ISM more complete on every commit.

## Integrating

If you want to integrate ISM into your plugin, just do as follows:

### 1. Add {$inlinesuccess} to your template

Just add it. Place a ```{$inlinesuccess}``` variable wherever you want the message to appear.

### 2. (optional) Globalize $inlinesuccess

If you have a stand-alone page and if you require the global.php file, then you're already done. Just redirect() as you would usually do. ISM will hijack the message you pass inside it, and will display it wherever you have put the $inlinesuccess variable.

If you are hooking into, let's say, the User Control Panel and therefore you have a function, you need to globalize the $inlinesuccess variable. Put this snippet at the very top of your function:

```php
global $inlinesuccess
```

Or add ```$inlinesuccess``` to your list of global variables.