# ProBookings System

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg?style=for-the-badge&logo=wordpress)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![Version](https://img.shields.io/badge/Version-3.0-green.svg?style=for-the-badge)
![License](https://img.shields.io/badge/License-GPLv2-orange.svg?style=for-the-badge)

**ProBookings System** is a professional, high-performance WordPress plugin designed for managing bookings for boats, excursions, and daily rentals. It features a modern, responsive interface, Stripe payments, Google Calendar synchronization, and a dedicated frontend dashboard for staff.

---

## Features

### Frontend Booking System
- **Interactive Calendar:** Built with Flatpickr, featuring a color-coded legend (Available, Partially Booked, Full).
- **Smart Slots:** Manages Morning, Afternoon, and Full Day slots. Automatically handles partial availability.
- **Beautiful Themes:** Includes 5 preset themes (Default, Sea & Boats, Sunset, Forest, Elegant) to match your brand.
- **Responsive Design:** Fully optimized for mobile and desktop.

### Payments & Options
- **Stripe Integration:** Secure credit card payments via Stripe Checkout.
- **Pay on Site:** Option to allow customers to pay cash upon arrival.
- **Dynamic Pricing:** Set different prices for Morning, Afternoon, and Full Day slots.

### Powerful Admin Panel
- **Dashboard:** View, edit, and delete bookings with a modern interface.
- **Quick Contact:** Buttons to call or email customers directly from the panel.
- **Google Calendar Sync:** Automatically adds confirmed bookings to your Google Calendar.
- **Manual Bookings:** Staff can manually add bookings from the backend.
- **Date Blocking:** Easily block specific dates (e.g., holidays or maintenance).

### 📱 Staff Web App
- **Frontend Dashboard:** A dedicated mobile-friendly dashboard for staff to manage bookings without accessing WP Admin.
- **Shortcode:** `[mbs_dashboard]`

---

## Installation

1. **Download:** Get the plugin `.zip` file.
2. **Upload:** Go to WordPress Admin > Plugins > Add New > Upload Plugin.
3. **Activate:** Activate **ProBookings System**.
4. **Dependencies (Important):** If you are installing from source, run `composer install` inside the plugin folder to download the Google API client. If you installed a pre-packaged ZIP, this is already done.

---

## Configuration

Go to **ProBookings > Settings** to configure:

1. **Stripe Keys:** Enter your Publishable and Secret keys.
2. **Google Calendar:** Enter Client ID and Secret to enable 1-way sync.
3. **Themes:** Select the visual theme for the booking form.
4. **Prices:** Set your rates for different time slots.

---

## Shortcodes

| Shortcode | Description |
| :--- | :--- |
| `[pro_bookings]` | Displays the main booking form for customers. |
| `[mbs_dashboard]` | Displays the management dashboard for staff (requires login). |

---

## File Structure

- `ProBookings.php`: Core plugin file, database initialization.
- `admin-panel.php`: Backend administration UI.
- `booking.js`: Frontend logic, AJAX handling.
- `google-calendar-integration.php`: Logic for Google API sync.
- `cancellation-logic.php`: Handles user cancellations and refunds.

---

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Stripe Account (optional, for payments)
- Google Cloud Project (optional, for calendar sync)