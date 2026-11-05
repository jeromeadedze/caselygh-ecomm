<?php
// App settings on Railway. The image copies this over core/app_config.php.
// Keys come from Railway's variables, so nothing secret is in the repository.
// Keep the prices and limits the same as core/app_config.example.php.

define("APP_URL", rtrim(getenv("APP_URL") ?: "https://" . getenv("RAILWAY_PUBLIC_DOMAIN"), "/"));

define("PAYSTACK_SECRET_KEY", getenv("PAYSTACK_SECRET_KEY") ?: "");
define("PAYSTACK_PUBLIC_KEY", getenv("PAYSTACK_PUBLIC_KEY") ?: "");

// Pricing (GHS).
define("TOUGH_EXTRA", 25);
define("CUSTOM_CASE_PRICE", 150);
define("DELIVERY_FEE", 30);

define("SESSION_TIMEOUT", 60 * 60);
define("MAX_UPLOAD_BYTES", 8 * 1024 * 1024);
