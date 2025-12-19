# ProBookings System

A custom WordPress plugin for managing boat and excursion bookings. It includes an interactive calendar, time slot management (Morning/Afternoon/Full Day), and Stripe payment integration.

## Features

- **Frontend:** Interactive calendar based on Flatpickr.
- **Smart Slots:** Partial availability management (e.g., if the morning is booked, the afternoon remains bookable).
- **Payments:** Native integration with Stripe Checkout.
- **Admin Panel:** Dashboard to view bookings, payment status, and block dates (e.g., for holidays).
- **Database:** Custom SQL tables for maximum performance.

## Installation

1. Download the plugin folder.
2. Upload it to the `/wp-content/plugins/` directory of your WordPress site.
3. Activate the plugin from the WordPress Dashboard.
4. Insert the shortcode `[pro_bookings]` into a page.
5. Configure Stripe API keys in the plugin settings page.

## File Structure

- `ProBookings.php`: Core logic, database setup, and API Hooks.
- `admin-panel.php`: Administration interface.
- `booking.js`: Frontend management, AJAX, and Stripe Redirect.
- `style.css`: Custom styling.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Stripe Account (for payments)