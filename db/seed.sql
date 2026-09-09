-- Practice Onboarding Tracker - seed data
-- Everything here is editable in the admin UI. The four practices are
-- samples so the dashboard is not empty; delete them once you add real ones.
-- Contains no patient data.

SET NAMES utf8mb4;

-- Categories -----------------------------------------------------------
INSERT INTO categories (name, sort_order) VALUES
  ('Administrative', 10),
  ('Technical', 20),
  ('Configuration', 30),
  ('Cosmetic / Design', 40),
  ('Training', 50),
  ('Testing', 60),
  ('Go-Live', 70)
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order);

-- Products -------------------------------------------------------------
INSERT INTO products (name, slug, description, sort_order) VALUES
  ('Recall Health', 'recall-health', 'Automated patient recall and reactivation campaigns.', 10),
  ('Online Scheduler', 'online-scheduler', 'Self-service online appointment booking.', 20),
  ('AI Voice Agent', 'ai-voice-agent', 'AI phone agent for inbound call handling and booking.', 30),
  ('Payment Portal', 'payment-portal', 'Online patient payments, statements, and plans.', 40),
  ('Patient Intake', 'patient-intake', 'Digital intake forms and consents.', 50)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

-- Assignees ------------------------------------------------------------
INSERT INTO assignees (name, role_title) VALUES
  ('Implementation Lead', 'Implementation'),
  ('Onboarding Specialist', 'Onboarding'),
  ('Technical Integrations', 'Engineering'),
  ('Training Team', 'Training'),
  ('Practice Contact', 'Practice')
;

-- Global tasks ---------------------------------------------------------
-- Ordered within each product. sort_order leaves gaps so tasks can be
-- reordered from the admin screen without renumbering everything.
INSERT INTO tasks (product_id, category_id, name, sort_order) VALUES
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Signed product order form received', 10),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Confirm recall campaign scope and goals', 20),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Collect practice branding assets', 30),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Verify PM/EHR data connection', 40),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Confirm patient list extract fields', 50),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Configure SMS number', 60),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Verify email sending domain (SPF/DKIM)', 70),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Define recall intervals and rules', 80),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Build message templates', 90),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Set send windows and frequency caps', 100),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Configure opt-out handling', 110),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Cosmetic / Design'),
   'Apply practice logo and colors to templates', 120),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Cosmetic / Design'),
   'Review message copy with practice', 130),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Training'),
   'Train staff on recall dashboard', 140),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Training'),
   'Walk through reporting with practice', 150),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Send test campaign to internal list', 160),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Verify opt-out and reply handling', 170),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Confirm reporting accuracy', 180),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Confirm go-live date with practice', 190),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Enable live sending', 200),
  ((SELECT id FROM products WHERE slug = 'recall-health'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Seven-day post-launch check', 210),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Signed product order form received', 10),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Confirm locations and provider list', 20),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Confirm scheduling policies', 30),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Connect PM/EHR calendar integration', 40),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Verify appointment write-back', 50),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Install booking widget on website', 60),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Configure appointment types and durations', 70),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Set provider availability rules', 80),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Set buffer and lead times', 90),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Configure confirmation and reminder messages', 100),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Configure new vs existing patient flows', 110),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Cosmetic / Design'),
   'Brand the booking page', 120),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Cosmetic / Design'),
   'Review booking flow wording', 130),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Training'),
   'Train front desk on booking management', 140),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Training'),
   'Review cancellation and reschedule handling', 150),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'End-to-end test booking', 160),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Verify reminders fire correctly', 170),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Test double-booking prevention', 180),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Publish booking link', 190),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Enable live bookings', 200),
  ((SELECT id FROM products WHERE slug = 'online-scheduler'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Seven-day post-launch check', 210),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Signed product order form received', 10),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Confirm call handling scope', 20),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Collect practice FAQ source material', 30),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Confirm after-hours policy', 40),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Provision or port phone number', 50),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Configure call forwarding', 60),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Verify recording settings and consent notice', 70),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Integrate with Online Scheduler', 80),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Build call flow and intents', 90),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Configure escalation and transfer rules', 100),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Set voicemail and message routing', 110),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Configure business hours', 120),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Cosmetic / Design'),
   'Select voice and greeting script', 130),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Cosmetic / Design'),
   'Review tone with practice', 140),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Training'),
   'Train staff on transcripts and escalations', 150),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Test inbound call scenarios', 160),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Verify transfer to live staff', 170),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Verify appointment booking by phone', 180),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Cut over main or overflow line', 190),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Enable live call handling', 200),
  ((SELECT id FROM products WHERE slug = 'ai-voice-agent'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Seven-day post-launch check', 210),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Signed product order form received', 10),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Merchant account application submitted', 20),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Merchant underwriting approved', 30),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Confirm refund and receipt policy', 40),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Connect payment gateway credentials', 50),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Verify payment posting to PM/EHR', 60),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Install payment link on website', 70),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Configure accepted payment methods', 80),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Set up statement and invoice templates', 90),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Configure payment plans', 100),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Configure receipt delivery', 110),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Cosmetic / Design'),
   'Brand the payment page', 120),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Training'),
   'Train staff on payments and refunds', 130),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Training'),
   'Review daily reconciliation process', 140),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Process test transaction', 150),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Process test refund', 160),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Verify reconciliation report', 170),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Enable live payments', 180),
  ((SELECT id FROM products WHERE slug = 'payment-portal'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Seven-day post-launch check', 190),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Signed product order form received', 10),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Collect current paper forms', 20),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Confirm required intake fields', 30),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Administrative'),
   'Confirm consent form versions', 40),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Configure intake write-back to PM/EHR', 50),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Verify document upload storage', 60),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Technical'),
   'Configure secure link delivery', 70),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Build digital intake forms', 80),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Configure conditional logic', 90),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Map form fields to chart fields', 100),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Configuration'),
   'Configure reminders for incomplete forms', 110),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Cosmetic / Design'),
   'Brand intake forms', 120),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Cosmetic / Design'),
   'Review form wording with practice', 130),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Training'),
   'Train staff on reviewing submissions', 140),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Training'),
   'Review incomplete-form workflow', 150),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Complete a test intake submission', 160),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Verify data lands in the chart correctly', 170),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Testing'),
   'Test on a mobile device', 180),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Enable intake for new appointments', 190),
  ((SELECT id FROM products WHERE slug = 'patient-intake'),
   (SELECT id FROM categories WHERE name = 'Go-Live'),
   'Seven-day post-launch check', 200)
;

-- Sample practices -----------------------------------------------------
INSERT INTO practices (name, slug, location, onboarding_state, target_go_live_date) VALUES
  ('Northside Dermatology', 'northside-dermatology', 'Columbus, OH', 'active', '2026-10-15');
INSERT INTO practices (name, slug, location, onboarding_state, target_go_live_date) VALUES
  ('Lakeview Family Medicine', 'lakeview-family-medicine', 'Tampa, FL', 'active', '2026-09-30');
INSERT INTO practices (name, slug, location, onboarding_state, target_go_live_date) VALUES
  ('Summit Skin & Laser', 'summit-skin-laser', 'Denver, CO', 'active', '2026-11-20');
INSERT INTO practices (name, slug, location, onboarding_state, target_go_live_date) VALUES
  ('Harbor Point Primary Care', 'harbor-point-primary-care', 'Portland, ME', 'on_hold', '2026-12-01');

-- Product selections per practice --------------------------------------
INSERT INTO practice_products (practice_id, product_id) VALUES ((SELECT id FROM practices WHERE slug = 'northside-dermatology'), (SELECT id FROM products WHERE slug = 'recall-health'));
INSERT INTO practice_products (practice_id, product_id) VALUES ((SELECT id FROM practices WHERE slug = 'northside-dermatology'), (SELECT id FROM products WHERE slug = 'online-scheduler'));
INSERT INTO practice_products (practice_id, product_id) VALUES ((SELECT id FROM practices WHERE slug = 'northside-dermatology'), (SELECT id FROM products WHERE slug = 'ai-voice-agent'));
INSERT INTO practice_products (practice_id, product_id) VALUES ((SELECT id FROM practices WHERE slug = 'lakeview-family-medicine'), (SELECT id FROM products WHERE slug = 'payment-portal'));
INSERT INTO practice_products (practice_id, product_id) VALUES ((SELECT id FROM practices WHERE slug = 'lakeview-family-medicine'), (SELECT id FROM products WHERE slug = 'online-scheduler'));
INSERT INTO practice_products (practice_id, product_id) VALUES ((SELECT id FROM practices WHERE slug = 'summit-skin-laser'), (SELECT id FROM products WHERE slug = 'recall-health'));
INSERT INTO practice_products (practice_id, product_id) VALUES ((SELECT id FROM practices WHERE slug = 'summit-skin-laser'), (SELECT id FROM products WHERE slug = 'patient-intake'));
INSERT INTO practice_products (practice_id, product_id) VALUES ((SELECT id FROM practices WHERE slug = 'summit-skin-laser'), (SELECT id FROM products WHERE slug = 'payment-portal'));
INSERT INTO practice_products (practice_id, product_id) VALUES ((SELECT id FROM practices WHERE slug = 'summit-skin-laser'), (SELECT id FROM products WHERE slug = 'online-scheduler'));
INSERT INTO practice_products (practice_id, product_id) VALUES ((SELECT id FROM practices WHERE slug = 'harbor-point-primary-care'), (SELECT id FROM products WHERE slug = 'online-scheduler'));
INSERT INTO practice_products (practice_id, product_id) VALUES ((SELECT id FROM practices WHERE slug = 'harbor-point-primary-care'), (SELECT id FROM products WHERE slug = 'patient-intake'));

-- A little sample progress so the dashboard is not all zeros -----------
-- Northside: administrative and technical mostly done, one blocker.
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'completed', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'recall-health'
JOIN categories c ON c.name = 'Administrative'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'northside-dermatology'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'completed', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'recall-health'
JOIN categories c ON c.name = 'Technical'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'northside-dermatology'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'in_progress', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'recall-health'
JOIN categories c ON c.name = 'Configuration'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'northside-dermatology'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'completed', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'online-scheduler'
JOIN categories c ON c.name = 'Administrative'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'northside-dermatology'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'in_progress', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'online-scheduler'
JOIN categories c ON c.name = 'Technical'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'northside-dermatology'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'in_progress', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'ai-voice-agent'
JOIN categories c ON c.name = 'Administrative'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'northside-dermatology'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'completed', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'online-scheduler'
JOIN categories c ON c.name = 'Administrative'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'lakeview-family-medicine'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'completed', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'online-scheduler'
JOIN categories c ON c.name = 'Technical'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'lakeview-family-medicine'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'completed', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'online-scheduler'
JOIN categories c ON c.name = 'Configuration'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'lakeview-family-medicine'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'completed', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'online-scheduler'
JOIN categories c ON c.name = 'Cosmetic / Design'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'lakeview-family-medicine'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'in_progress', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'online-scheduler'
JOIN categories c ON c.name = 'Training'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'lakeview-family-medicine'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'waiting', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'payment-portal'
JOIN categories c ON c.name = 'Administrative'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'lakeview-family-medicine'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'completed', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'recall-health'
JOIN categories c ON c.name = 'Administrative'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'summit-skin-laser'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'completed', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'patient-intake'
JOIN categories c ON c.name = 'Administrative'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'summit-skin-laser'
ON DUPLICATE KEY UPDATE status = VALUES(status);
INSERT INTO practice_tasks (practice_id, task_id, status, assignee_id)
SELECT pr.id, t.id, 'in_progress', (SELECT id FROM assignees WHERE name = 'Implementation Lead')
FROM practices pr
JOIN products p ON p.slug = 'patient-intake'
JOIN categories c ON c.name = 'Technical'
JOIN tasks t ON t.product_id = p.id AND t.category_id = c.id
WHERE pr.slug = 'summit-skin-laser'
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- One blocked task and one overdue task, to exercise the dashboard flags.
UPDATE practice_tasks pt
JOIN tasks t ON t.id = pt.task_id
JOIN practices pr ON pr.id = pt.practice_id
SET pt.status = 'blocked', pt.notes = 'Waiting on carrier approval for the SMS short code.'
WHERE pr.slug = 'northside-dermatology' AND t.name = 'Configure SMS number';

UPDATE practice_tasks pt
JOIN tasks t ON t.id = pt.task_id
JOIN practices pr ON pr.id = pt.practice_id
SET pt.due_date = DATE_SUB(CURDATE(), INTERVAL 6 DAY)
WHERE pr.slug = 'lakeview-family-medicine' AND t.name = 'Merchant underwriting approved';

UPDATE practice_tasks pt
JOIN tasks t ON t.id = pt.task_id
JOIN practices pr ON pr.id = pt.practice_id
SET pt.status = 'blocked', pt.notes = 'Practice has not returned the signed merchant application.'
WHERE pr.slug = 'lakeview-family-medicine' AND t.name = 'Merchant account application submitted';

INSERT INTO app_settings (setting_key, setting_value) VALUES ('seeded_at', NOW())
ON DUPLICATE KEY UPDATE setting_value = NOW();
