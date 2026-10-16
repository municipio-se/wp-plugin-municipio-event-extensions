# Municipio Event Extensions

Server-rendered event modules for Municipio 6. Cards, filters and pagination
work without JavaScript; JavaScript adds in-place updates and mobile carousel
controls. The existing server-rendered list remains available. Import and
storage remain with Event Manager Integration.

Activate alongside Event Manager Integration and Quality Extensions.
Deactivating restores the existing views without a data migration.

Version 0.1.0 has been tested with Municipio 6.43.3, Event Manager Integration
3.1.4, WordPress 6.9.10 and PHP 8.3 on Nora and migrated Höör installations.
Municipio 7 is not supported. Other event flows, including the archive and
submission forms, retain the legacy frontend bundle where needed. This package
does not remove React or jQuery dependencies from the whole site.

Install `municipio/wp-plugin-municipio-event-extensions` through a Composer VCS
repository pointing to this GitHub repository, restricted with `only` to that
package. Versions follow immutable SemVer tags and matching GitHub releases;
Packagist registration is not required. Commit the consumer's Composer lockfile.

Verify with `composer validate --strict`, `composer format`, `composer lint`,
`node --test tests/events.test.mjs` and, on a local WordPress fixture,
`wp eval-file tests/integration.php MODULE_ID`. The integration check requires
current events and compares the adapter to the existing REST endpoint; its
taxonomy fixture must match the tested module's configured selection.
