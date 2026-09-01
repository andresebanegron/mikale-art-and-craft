-- Apply this to an existing database before using the cleaned payment flow.
-- Skip any statement for a column/index that already exists.

ALTER TABLE orders
    ADD COLUMN stripe_session_id VARCHAR(255) NULL AFTER total,
    ADD UNIQUE KEY uq_orders_stripe_session_id (stripe_session_id);

ALTER TABLE order_items
    ADD COLUMN custom_text VARCHAR(255) NULL;
