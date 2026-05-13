=== Vedika Astrology ===
Contributors: vedikaintelligence
Donate link: https://vedika.io
Tags: astrology, horoscope, tarot, kundali, numerology, panchang, zodiac, birth chart, compatibility
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add astrology, tarot, numerology, horoscope, panchang, and birth chart features to your WordPress site. Powered by the Vedika AI API with 516+ endpoints.

== Description ==

**Vedika Astrology** brings professional-grade astrology features to any WordPress site. No coding required.

Drop in a shortcode or Gutenberg block and immediately offer your visitors:

* **Daily, Weekly, and Monthly Horoscopes** for all 12 zodiac signs
* **Tarot Card of the Day** with keywords, meaning, and element
* **Panchang** (Hindu calendar) with tithi, nakshatra, yoga, karana, sunrise/sunset, and festivals
* **Birth Chart Calculator** with planetary positions, ascendant, and house details
* **Compatibility Checker** (Ashtakoot / Kundli Matching) with category-level scoring
* **Numerology Calculator** for life path, destiny, personality, and soul urge numbers

= Works Without an API Key =

The plugin works out of the box using free sandbox endpoints with sample data. No sign-up required. When you are ready for live data, enter your API key from [vedika.io](https://vedika.io/pricing) and the plugin automatically switches to production endpoints.

= Features =

* **7 shortcodes** for embedding content anywhere
* **2 Gutenberg blocks** (Horoscope, Tarot) with visual editor previews
* **3 sidebar widgets** (Horoscope, Tarot, Panchang)
* **15 languages** including Hindi, Tamil, Telugu, Kannada, Malayalam, and more
* **Smart caching** via WordPress transients (configurable from 15 min to 24 hours)
* **Light and dark themes** that respect your site design
* **Responsive design** works on all screen sizes
* **Clean HTML output** with vedika- prefixed CSS classes that do not conflict with your theme
* **Settings page** under Settings > Vedika API for easy configuration

= Shortcodes =

* `[vedika_horoscope sign="aries" lang="en" period="daily"]` -- Daily horoscope for a sign
* `[vedika_horoscope_all]` -- All 12 signs in a tabbed view
* `[vedika_tarot]` -- Tarot card of the day
* `[vedika_panchang date="" lat="28.6139" lng="77.209"]` -- Today's panchang
* `[vedika_birth_chart]` -- Interactive birth chart calculator form
* `[vedika_compatibility]` -- Compatibility checker form
* `[vedika_numerology]` -- Numerology calculator form

= About Vedika AI =

Vedika Intelligence provides the most comprehensive astrology API available, covering Vedic, Western, and KP astrology systems with 516+ endpoints. Built for accuracy with Swiss Ephemeris-grade calculations.

Learn more at [vedika.io](https://vedika.io).

== Installation ==

1. Upload the `vedika-astrology` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. (Optional) Go to Settings > Vedika API to enter your API key.
4. Add shortcodes or blocks to your posts and pages.

= Minimum Requirements =

* WordPress 6.0 or later
* PHP 7.4 or later

== Frequently Asked Questions ==

= Do I need an API key? =

No. The plugin works without a key using sandbox endpoints that return sample data. This is great for testing and development. For live data, get an API key at [vedika.io/pricing](https://vedika.io/pricing).

= What plans are available? =

Vedika API offers Starter ($12/mo), Professional ($60/mo), Business ($120/mo), and Enterprise ($240/mo) plans. Each includes wallet credits for API calls. Visit [vedika.io/pricing](https://vedika.io/pricing) for details.

= Does the plugin slow down my site? =

No. API responses are cached using WordPress transients. You can configure the cache duration from 15 minutes to 24 hours. Cached content loads instantly without any API calls.

= Can I style the output? =

Yes. All HTML output uses `.vedika-` prefixed CSS classes. You can override styles in your theme. The plugin also supports light and dark themes from Settings > Vedika API.

= What languages are supported? =

15 languages: English, Hindi, Tamil, Telugu, Kannada, Malayalam, Marathi, Gujarati, Bengali, Punjabi, Odia, Assamese, Urdu, Nepali, and Sanskrit. Set the default in Settings or override per shortcode with the `lang` attribute.

= Is this compatible with my theme? =

Yes. The plugin uses clean, prefixed CSS with no !important overrides. It is designed to work with any properly coded WordPress theme.

== Screenshots ==

1. Daily horoscope with sign selector
2. All 12 signs with tabbed navigation
3. Tarot card of the day
4. Panchang display
5. Birth chart calculator form
6. Settings page

== Changelog ==

= 1.0.0 =
* Initial release
* 7 shortcodes: horoscope, horoscope_all, tarot, panchang, birth_chart, compatibility, numerology
* 2 Gutenberg blocks: Horoscope, Tarot
* 3 sidebar widgets: Horoscope, Tarot, Panchang
* Admin settings page with API key validation
* Light and dark theme support
* WordPress transient caching
* 15-language support
* Sandbox fallback (no API key required)

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install and start adding astrology features to your site.
