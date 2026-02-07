# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

MT Hotel Booking System is a WordPress plugin for hotel reservation quote requests. It does NOT handle real-time availability or payment processing. Instead, it collects booking requests, stores them in a custom database table, and sends notification emails to both hotel staff and guests.

**Key Characteristics:**
- Quote-based system (no payment gateway integration)
- Custom database table for bookings (`wp_hbs_bookings`)
- Email notifications for staff and guests
- Dynamic pricing engine (server-side validation)
- Frontend shortcode `[hotel_booking_form]` for embedding forms
- Optional floating quick-booking widget
- Configurable pricing, styling, and email templates via WordPress admin

## Development Environment

This is a WordPress plugin developed in a XAMPP environment on Windows.

**WordPress Integration:**
- Main bootstrap file: `hotel-booking-system.php`
- Text domain: `hotel-booking-system`
- Translation path: `languages/`

**Testing the Plugin:**
1. Ensure WordPress is running (Apache + MySQL via XAMPP)
2. Activate the plugin in WordPress Admin > Plugins
3. Configure settings in WordPress Admin > Hotel Booking > Ajustes
4. Test frontend form by adding shortcode `[hotel_booking_form]` to a page
5. Test AJAX submissions using browser DevTools network tab

**Database:**
- Custom table created on activation: `{prefix}hbs_bookings`
- Settings stored in wp_options: `hbs_settings`

## Architecture

### Plugin Bootstrap Flow
`hotel-booking-system.php` loads classes and registers activation/deactivation hooks:
1. Loads all class files from `includes/`, `admin/`, and `public/`
2. Registers activation hook → `HBS_Activator::activate()` (creates DB table)
3. Initializes `HBS_Loader` to register WordPress hooks
4. Instantiates `HBS_Admin_Menu` and `HBS_Public` which register their hooks via the loader
5. Calls `$loader->run()` to execute all registered hooks

### Core Components

**HBS_Loader** (`includes/class-hbs-loader.php`)
- Hook registration system (actions only)
- Stores hooks in array and applies them via `add_action()` in `run()` method

**HBS_Config** (`includes/class-hbs-config.php`)
- Global constants for option keys and nonce actions
- `OPTION_KEY`: 'hbs_settings' (stores all plugin settings)
- `NONCE_ACTION` and `NONCE_KEY`: Security tokens

**HBS_Booking** (`includes/class-hbs-booking.php`)
- Model class for database operations
- `insert($data)`: Validates, sanitizes, calculates pricing, inserts booking
- `get($booking_id)`: Retrieves single booking
- `get_recent($limit)`: Retrieves recent bookings
- `delete($booking_id)`: Deletes a booking
- `calculate_total()`: Server-side pricing engine (mirrors frontend JS logic)

**HBS_Emails** (`includes/class-hbs-emails.php`)
- `send_staff($booking_id)`: Sends notification to hotel staff emails
- `send_guest($booking_id)`: Sends confirmation to guest
- Supports customizable HTML templates with placeholder replacement
- Placeholders: `{booking_id}`, `{guest_name}`, `{guest_email}`, `{guest_phone}`, `{check_in_date}`, `{check_out_date}`, `{room_type}`, `{adults_count}`, `{kids_count}`, `{total_price}`

**HBS_Admin_Menu** (`admin/class-hbs-admin-menu.php`)
- Registers admin menu pages (Settings, Recent Bookings)
- Handles `admin_post_hbs_save_settings` action for settings form
- Handles `admin_post_hbs_delete_booking` action for deleting bookings
- Enqueues admin assets (wp-color-picker, select2 for multi-select)

**HBS_Public** (`public/class-hbs-public.php`)
- Frontend functionality
- Registers shortcode `[hotel_booking_form]`
- Enqueues frontend CSS/JS with localized pricing data
- Handles AJAX endpoint `wp_ajax_hbs_submit_booking` (for both logged-in and non-logged-in users)
- Renders optional floating form in footer via `wp_footer` hook
- Validates honeypot field for spam prevention
- Applies custom color styles if enabled in settings

### Pricing Logic

**Server-side** (`HBS_Booking::calculate_total()`) is the source of truth:
- **Single room**: Base price covers up to 2 guests. Any guests beyond 2 charged as extra adults.
- **Double room**: Base price covers up to 2 guests. Additional adults and kids charged separately (extra adults first, then extra kids).
- Maximum capacity: 4 guests total
- Nightly rate multiplied by number of nights

**Client-side** (`assets/js/hotel-booking.js`) mirrors this logic for live price preview but is never trusted by the server.

### Room Selection Rules

Enforced by frontend JavaScript (`applyRoomRules()` function):
- **1 adult, 0 kids**: Auto-select Single room
- **More than 2 adults OR more than 3 total guests**: Force Double room
- **2 adults with 0-1 kids OR 1 adult with 1-2 kids (≤3 total)**: User can choose
- **More than 4 guests**: Block submission with error

## File Structure

```
hotel-booking-system.php     # Main plugin file (bootstrap)
uninstall.php                 # Cleanup on plugin deletion

includes/
  class-hbs-config.php        # Global constants
  class-hbs-loader.php        # Hook registration system
  class-hbs-activator.php     # DB table creation on activation
  class-hbs-deactivator.php   # Deactivation hook (minimal)
  class-hbs-booking.php       # Booking model & pricing engine
  class-hbs-emails.php        # Email notification system

admin/
  class-hbs-admin-menu.php    # Admin menu & settings handler
  views/
    settings-page.php         # Settings form view
    recent-bookings-page.php  # Recent bookings table view

public/
  class-hbs-public.php        # Frontend shortcode, AJAX, floating form
  views/
    booking-form.php          # Main booking form template

assets/
  css/hotel-booking.css       # Frontend styles
  js/hotel-booking.js         # Frontend logic (prefill, pricing, AJAX)

languages/                    # Translation files (if any)
```

## Common Development Patterns

### Adding New Form Fields
1. Update `public/views/booking-form.php` to add HTML input
2. Modify `assets/js/hotel-booking.js` AJAX submission to include new field
3. Update `HBS_Public::handle_booking()` to sanitize and include in `$data` array
4. Modify `HBS_Booking::insert()` to handle new field in validation/sanitization
5. Update database schema in `HBS_Activator::activate()` if persisting to DB
6. If relevant to emails, add placeholder to `HBS_Emails::replace_placeholders()`

### Modifying Pricing Rules
1. Update server-side logic in `HBS_Booking::calculate_total()` (source of truth)
2. Mirror changes in client-side `assets/js/hotel-booking.js` (`computeTotals()` function)
3. Update breakdown rendering if UI display changes

### Security Considerations
- All AJAX submissions validated with nonce (`HBS_Config::NONCE_ACTION`)
- Honeypot field (`hbs_hp_field`) checked for spam
- All inputs sanitized using WordPress functions (`sanitize_text_field`, `sanitize_email`, etc.)
- Admin actions check `manage_options` capability and admin referer nonces
- Database queries use `$wpdb->prepare()` for SQL injection prevention

### WordPress Coding Standards
- Use WordPress escaping functions: `esc_html()`, `esc_attr()`, `esc_url()`
- Use WordPress sanitization: `sanitize_text_field()`, `sanitize_email()`, `wp_kses_post()`
- Translation ready: Use `__()`, `_e()`, `esc_html__()` with text domain `hotel-booking-system`
- Nonce verification for all form submissions and AJAX requests

## Testing Approach

**No automated tests exist in this codebase.** Manual testing required:

1. **Activation/Deactivation**: Verify DB table creation and cleanup
2. **Settings Form**: Test all admin settings save correctly in wp_options
3. **Frontend Form**: Test shortcode rendering and all field validations
4. **AJAX Submission**: Verify booking creation, email sending, and error handling
5. **Floating Form**: Test on different pages and verify exclusion logic
6. **Email Templates**: Send test bookings and verify staff/guest emails
7. **Pricing Calculations**: Test edge cases (max capacity, different room types)
8. **Browser Compatibility**: Test form on major browsers (Chrome, Firefox, Safari, Edge)

**Debug Mode**: Enable WordPress debug logging by setting `WP_DEBUG` and `WP_DEBUG_LOG` to true in `wp-config.php`.

## Common Tasks

### Viewing Recent Bookings
Navigate to: WordPress Admin > Hotel Booking > Reservaciones recientes

### Configuring Pricing
Navigate to: WordPress Admin > Hotel Booking > Ajustes
Update: Precio habitación sencilla, Precio habitación doble, Precio adulto extra, Precio niño extra

### Customizing Email Templates
Navigate to: WordPress Admin > Hotel Booking > Ajustes
Scroll to: Configuración de Emails
Edit subject and content fields with HTML and placeholders

### Debugging AJAX Issues
1. Open browser DevTools > Network tab
2. Submit booking form
3. Find `admin-ajax.php` request
4. Check request payload (Form Data) and response (Preview/Response tab)
5. Verify nonce is included and action is `hbs_submit_booking`

### Adding Floating Form Exclusions
Navigate to: WordPress Admin > Hotel Booking > Ajustes
Find: "Excepciones para formulario flotante"
Add page/post IDs (comma-separated or use multi-select if configured)
