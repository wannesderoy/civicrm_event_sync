## Module civicrm_event_sync

#### Enables a full sync between the CivCRM Event entity and a Drupal node type event.


Sync method names follow the following logic.

"eventSync\<Origin>\<Operation>\<Destination>".

Depending on the provided destination the method is located in the class
EventSyncCivicrm (destination: CiviCRM) or
EventSyncDrupal (destination: Drupal).

Examples:
event_sync_civicrm_create_drupal is called when an event in civicrm is
created and must be synced to drupal and is located in class EventSyncDrupal.

event_sync_drupal_delete_civicrm is called when an even in drupal is deleted
and must be synced to civicrm and is located in class EventSyncCivicrm.

#### Dependencies:
- https://github.com/kewljuice/civicrm_fields