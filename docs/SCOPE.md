# Project scope

## Included

- Google People API as the contact source
- read-only OAuth scope
- normalized local contact cache
- Google phone-number labels
- multiple Yealink devices
- independent device token per Yealink
- Yealink Remote Phone Book XML
- central blocklist as optional second Yealink directory
- manual and scheduled synchronization
- synchronization history
- per-device access status
- admin JSON/CSV diagnostic exports
- responsive admin UI

## Deliberately not included

- write access to Google Contacts
- bidirectional contact editing
- contact photos
- untested manufacturer-specific integrations or placeholders
- CardDAV
- PWA / WEG portal functionality
- database server dependency

New manufacturers should be added only when a real target device is available for testing.
