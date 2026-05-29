=== EWEB - Smart Meetings Scheduler ===
Contributors: yisus_develop
Tags: meetings, bookings, scheduler, shortcode
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.1+
Stable tag: 1.3.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Meeting booking form with fixed 30-minute slots for event scheduling.

== Description ==
Provides shortcode [eweb_smart_meetings_form] for event scheduling with:
- Fixed slot windows
- Server-side slot locking
- Confirmation emails
- ICS attachments for calendar import

== Installation ==
1. Upload the plugin folder to /wp-content/plugins/.
2. Activate the plugin in WordPress.
3. Add shortcode [eweb_smart_meetings_form] to any page.

== Changelog ==
= 1.3.4 =
* Hardened submit button styling using high CSS specificity overrides and important flags to prevent global Elementor pink accents during active/focus states.

= 1.3.3 =
* Converted target_genders checkboxes into target_gender radio buttons (single option only: Men or Women).

= 1.3.2 =
* Fixed hidden checkboxes validation focus issue ('invalid form control is not focusable') in form.css.

= 1.3.1 =
* Changed default timezone from Europe/Lisbon to America/New_York (GMT-4).
* Updated email templates and summaries with GMT-4 labels.

= 1.2.1 =
* AI-Vault compliance alignment.
* Version synchronization and metadata hardening.
* GitHub updater integration.
