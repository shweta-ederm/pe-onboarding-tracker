-- A colour per product, so a product is recognisable at a glance in
-- chips, tabs and charts. Editable in Admin > Products.
--
-- Values are the chart-1 accent from each product's theme in the
-- Practice Engine design system.
--
-- Paste into cPanel > phpMyAdmin > SQL if you cannot run migrate.php.

ALTER TABLE products
  ADD COLUMN color CHAR(7) DEFAULT NULL AFTER description;

-- Matched on slug, so this only touches products that exist.
UPDATE products SET color = '#F8941D' WHERE slug = 'recall-health'    AND color IS NULL;
UPDATE products SET color = '#0284C7' WHERE slug = 'online-scheduler' AND color IS NULL;
UPDATE products SET color = '#6E62A8' WHERE slug = 'ai-voice-agent'   AND color IS NULL;
UPDATE products SET color = '#5CB85C' WHERE slug = 'payment-portal'   AND color IS NULL;
UPDATE products SET color = '#B07AA1' WHERE slug = 'patient-intake'   AND color IS NULL;

-- Anything else gets the suite slate so no product is left colourless.
UPDATE products SET color = '#334155' WHERE color IS NULL;
