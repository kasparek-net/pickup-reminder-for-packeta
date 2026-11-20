=== Pickup Reminder for Packeta ===
Contributors: kasparek
Tags: packeta, zasilkovna, woocommerce, cash on delivery, reminder
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

E-mails customers before the last day their Packeta parcel can be picked up, so fewer cash-on-delivery parcels come back.

== Description ==

Unpicked cash-on-delivery parcels cost the shop postage twice and bring no money. This plugin sends the customer a friendly WooCommerce e-mail before the last day of pickup storage.

* **Uses the official Packeta plugin** – parcel status and the "stored until" date are already synchronized by the official Packeta plugin; no extra API calls, no extra keys.
* **When** – on the last day, or 1, 2 or 3 days before it (default: 1 day before). E-mails go out between 8:00 and 20:00 site time.
* **Which parcels** – cash on delivery only (default) or all parcels at pickup points.
* **Once per storage date** – if you extend the storage in Packeta, the customer is reminded again for the new date, never twice for the same one.
* **Test mode first** – reminders go only to your address until you switch to live.
* **Standard WooCommerce e-mail** – subject, heading and text are edited in WooCommerce → Settings → Emails, the e-mail uses your store's e-mail design and sender name. Templates can be overridden in the theme (`woocommerce/emails/prp-pickup-reminder.php`).
* **Overview** – WooCommerce → Pickup reminder lists parcels waiting at a pickup point and whether the reminder was sent.
* **Czech first** – the plugin speaks Czech everywhere (Slovak on Slovak sites), regardless of the site language. Both translations are bundled.

== Installation ==

1. Install and set up the official Packeta plugin, including packet status synchronization.
2. Install and activate this plugin.
3. Go to WooCommerce → Pickup reminder, check the settings and the test e-mails, then switch to live.

== Frequently Asked Questions ==

= Where does the storage date come from? =

From the official Packeta plugin, which downloads packet statuses and the "stored until" date several times a day.

= Does it change orders? =

It only adds an order note and a hidden meta field after sending the reminder.

== Changelog ==

= 1.0.1 =
* Czech everywhere (Slovak on Slovak sites), regardless of the site language.

= 1.0.0 =
* First version.
