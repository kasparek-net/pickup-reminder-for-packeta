# Pickup Reminder for Packeta

E-mails WooCommerce customers before the last day their Packeta (Zásilkovna) parcel can be picked up, so fewer cash-on-delivery parcels come back. See [readme.txt](readme.txt) for the full feature list.

## How it works

1. The official [Packeta plugin](https://wordpress.org/plugins/packeta/) keeps `packet_status` and `stored_until` in `{prefix}packetery_order` in sync with the Packeta API (packet status synchronization must be enabled there).
2. Every hour (Action Scheduler, e-mails only 8:00–20:00 site time) this plugin finds parcels `ready for pickup` whose `stored_until` is N days away (default 1), cash on delivery only by default.
3. It sends the `prp_pickup_reminder` WooCommerce e-mail and stores the storage date in the order meta `_prp_reminded`, so each date is reminded once.
4. Only order meta is saved (`save_meta_data()`), never the whole order: Packeta deletes its row on `woocommerce_update_order` when an order has no Packeta shipping method.

## Development

- Unit tests (no WordPress needed): `php tests/harness.php`
- Translations: `wp i18n make-pot . languages/pickup-reminder-for-packeta.pot --exclude=tests,languages,.nahledy`, then `python3 languages/build.py` (add new strings to its dictionary) and `wp i18n make-mo languages && wp i18n make-php languages`.
- `tests/`, `.wordpress-org/` and `.github/` are not part of the release ZIP (`.distignore`).

License: GPLv2 or later.
