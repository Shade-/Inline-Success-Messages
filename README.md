Inline Success Messages
=======================

**IMPORTANT**: *Thanks to this GitHub Repo you can track bugfixes and keep your Inline Success Messages copy up to date, but keep in mind that this is a <strong>development version</strong>. Therefore, you may encounter errors and relevant bugs using this version, although I will try to leave its code as functional as possible.*

**WARNING**: This plugin edits MyBB's core files. I can ensure it doesn't hurt your MyBB copy but if you are scared about core edits, then you shouldn't use it.

> **Current version** beta 2  
> **Dependencies** PluginLibrary, a library which contains useful PHP functions for MyBB  
> **Author** Shade  

General
-------

Inline Success Messages replaces MyBB's default behavior when you update your User CP settings, returning a success message instead of a (un)friendly redirection page (or nothing at all if you disable friendly redirection pages). It's inspired by IPBoard's systems and it should be a 1.8 core feature I'll include in a Pull Request soon.

Inline Success Messages works **only** with 1.6.8/1.6.9 MyBB installations and comes with additional CSS styles loaded directly into its template.

If you have any feature request, suggestion, or you want to report any issue, please let me know opening a new issue on GitHub. Your contribute is very important to me and helps me into making Custom Alerts for MyAlerts more complete on every commit.

At the moment Inline Success Messages replaces the core redirect function on the following UCP sections:

* Username
* Password
* Email
* Avatar
* Signature (partially)
* Profile
* Options
* Cover (Profile Picture plugin)

If you want to extend it to other parts of the UCP or MCP, please let me know opening a new Issue here on GitHub.