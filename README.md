# Casely Store — CS442 E-commerce final project

A custom e-commerce application for **Casely** ([caselygh.store](https://caselygh.store/), [@casely.gh](https://www.instagram.com/casely.gh/)), a Ghanaian phone case brand. Customers shop ready-made cases for their exact phone, design their own case in a live studio, and pay with **Mobile Money or card through Paystack**. The Casely team runs products, stock, orders, custom-design approvals and the Community wall from an admin area.



## Main features

- Shop by collection, phone model, search and price sort. Each product × phone model × case type is a **variant** with its own price and stock.
- Product page with phone picker, Standard/Tough pricing, live stock warnings.
- **Custom case studio** (8 steps): phone, case type, photos (up to 36, with collages), layouts, filters, fonts, name and colours, plus a consent checkbox. Tap the photo or text on the case to move, resize or rotate it. "Add" exports a print-ready image.
- **Saved designs**: signed-in customers can save a studio design as a draft and reopen it from *My account*. Guests are asked to sign in first; their design is kept in the browser and saved right after they sign in. On an existing database, run `database/migrations/2026_11_04_design_drafts.sql` to add the drafts table.
- Database cart (guests too), merged into the account on login or registration.
- Checkout → Paystack → verified payment → order tracking (*Paid → Printing → Out for delivery → Delivered*).
- Casely Community wall: customers post photos, the admin approves them, and an approved post shows a reward code in the customer's account.
- Admin: dashboard (revenue, orders to fulfil, best sellers, low stock), orders with status updates, custom design approval queue (with print-file download), products with stock per phone, new phone models, community moderation.

