# Municipio Event Extensions

Local prototype for Municipio 6 and Event Manager Integration 3.1.4. Import and
storage remain with the integration plugin. The first increment replaces index
cards without filters or a mobile carousel; other configurations retain legacy
rendering. Both legacy bundles are intentionally retained: Municipio captures
the footer before module views render, so asset selection needs a separate
pre-render inventory. This is not a production release.

Activate locally alongside Event Manager Integration and Quality Extensions.
Deactivating restores the existing views without a data migration.

Verify with `composer format`, `composer lint` and, on a local WordPress
fixture, `wp eval-file tests/integration.php MODULE_ID`. The integration check
requires current events and compares the adapter to the existing REST endpoint.
