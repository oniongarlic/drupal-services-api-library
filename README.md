A PHP class for accessing Drupal trough the services REST API module
====================================================================

https://www.drupal.org/project/services

Supported authentication schemes:
- Anonymous
- Session

Currently supported services:
- User (login, index, retrieve)
- Nodes (index, create, retrieve, delete)
- Files (upload, retrieve)

Only JSON response format is supported and must be enabled in services settings for this class to work.

Multivalue field bug in services module, see https://www.drupal.org/node/2224803
To make multivalue fields work you need to patch services (3.12 has this bug), see this comment 
https://www.drupal.org/node/2224803#comment-10183020

How to use the library ?
========================
For examples on how to use the class, see the various test scripts.

Have fun!
