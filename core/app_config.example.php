<?php
// Copy this file to core/app_config.php and fill in your values.
// core/app_config.php is git-ignored so keys never reach GitHub.

// Full URL of the app's root folder, no trailing slash.
// Paystack sends customers back here after they pay.
define("APP_URL", "http://localhost/casely");

// Paystack keys: Dashboard > Settings > API Keys & Webhooks.
// Use the sk_test_/pk_test_ keys while building, live keys for the demo.
define("PAYSTACK_SECRET_KEY", "sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");
define("PAYSTACK_PUBLIC_KEY", "pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");

// Pricing (GH₵). Confirm these with the owner.
define("TOUGH_EXTRA", 25);          // extra cost of a Tough case over Standard
define("CUSTOM_CASE_PRICE", 150);   // custom case, Standard
define("DELIVERY_FEE", 30);         // flat delivery fee

// Log users out after this many seconds of inactivity.
define("SESSION_TIMEOUT", 60 * 60);

// Upload limit for photos (bytes).
define("MAX_UPLOAD_BYTES", 8 * 1024 * 1024);
