# About Media entity

Media entity provides a 'base' entity for a media element. This is a very basic
entity which can reference to all kinds of media-objects (local files, YouTube
videos, tweets, CDN-files, ...). This entity only provides a relation between
Drupal (because it is an entity) and the resource. You can reference to this
entity within any other Drupal entity.

## About Media entity D500px

This module provides 500px integration for Media entity (i.e. media type
provider plugin).

### Without 500px API
If you need just to embedded pics, you can use this module without using
500px's API. That will give you access to the only field available from the
embed code: pic id.

You will need to:

- Create a Media bundle with the type provider "D500px".
- On that bundle create a field for the 500px source (this should be a plain
  text).
- Return to the bundle configuration and set "Field with source information" to
  use that field.


### With 500px API
If you need to get other fields, you will need to use 500px's API. The
integration with the 500px's API is currently handled by another contributed
module.

- Download and enable 
  [d500px](https://www.drupal.org/project/d500px). Be sure to use composer as
  d500px requires a Guzzle library to work.
- Follow d500px's instructions to enable the integration.
- In your 500px bundle configuration set "Whether to use 500px api to fetch
  pics or not" to "Yes".

### Storing field values
If you want to store the fields that are retrieved from 500px you should create
appropriate fields on the created media bundle (camera, vote, etc) and map 
this to the fields provided by 500px API.


Project page: http://drupal.org/project/media_entity_d500px

Maintainers:
 - Vincent Bouchet (@vbouchet) drupal.org/u/vbouchet

IRC channel: #drupal-media
