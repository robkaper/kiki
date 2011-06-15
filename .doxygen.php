<?

/// @mainpage Kiki framework
/// Kiki is a website framework.
/// @author Rob Kaper <http://robkaper.nl/>
/// @warning This documentation might not be up-to-date. Run
/// @code
/// doxygen .doxygen.conf
/// @endcode
/// in Kiki's source tree to update.
///
/// @section download_sec Download
/// Kiki is currently only available as Github repository:
/// http://github.com/robkaper/kiki/
/// @warning Although open source, Kiki's development process is currently
///   based on a single developer.  There is only a single branch, which
///   should be stable, but potential regressions and issues are usually
///   only checked against my own websites and often only my primary site. 
///   Potential users are advised to test updates in a development or
///   staging environment prior to deployment.
///
/// @section install_sec Installation
/// @warning These instructions are not complete. Kiki does not yet offer an
///   easy-to-use and user-friendly installation, although any LAMP
///   administrator should be able to figure it out with these instructions. 
///   But documentation is considered incomplete at this stage.  Please send
///   me your comments, questions or amend these instructions on Github.
///
/// @subsection install_source_sec Install sources
/// Place the Kiki source tree somewhere on your filesystem. Any location is
/// fine, as long as your Apache user has read access.
///
/// @subsection install_files_dirs_sec Create required files and directories
/// Directly under your document root, create a file called debug.txt and a
/// file storage directory, both with write access for your Apache user.
/// @code
/// cd /www/example.com
/// touch debug.txt
/// mkdir storage
/// chgrp www-data debug.txt storage && chmod g+w debug.txt storage
/// @endcode
///
/// Also directly under your document root, create a file called config.php
/// with read access for your Apache user and configure your database
/// settings.
/// @see config/config.php-sample
///
/// @subsection install_htdocs_sec Make Kiki's htdocs available as /kiki/
/// Edit config/htaccess and adjust to match your Kiki install path.
///
/// Then place the contents in your VirtualHost directive.
///
/// @note The sample file contains two rewrite rules not directly related to
///   /kiki/ documents: one for blog/article rewrites and one for the
///   storage directory. Adjust as desired.
/// @warning Only tested within the Apache configuration itself, not as .htaccess file.
///
/// @subsection install_database_sec Install required modules and database schema
///
/// Kiki offers a setup/status/update page, available as http://example.com/kiki/ 
///
/// This page lists (and checks) required PHP modules and extensions, tests your
/// database configuration, and installs or updates your data model.
///
/// @section config_sec Configuration
/// Your Facebook callback URL is:
///
/// http://example.com/kiki/facebook-callback.php
///
/// Your Twitter callback URL is:
///
/// http://example.com/kiki/twitter-callback.php
/// @section customise_sec Customisation
/// @todo Write this...

?>