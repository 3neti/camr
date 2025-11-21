-- Valid SQL dump with required tables
INSERT INTO `meter_site` (`site_code`, `site_name`, `date_modified`) VALUES
('SITE001', 'Main Site', '2024-01-01 10:00:00'),
('SITE002', 'Branch Site', '2024-01-02 10:00:00');

INSERT INTO `meter_details` (`meter_name`, `meter_site_name`, `meter_type`, `meter_model`, `customer_name`, `last_log_update`) VALUES
('METER001', 'SITE001', 'Electric', 'Model A', 'Customer 1', '2024-01-01 10:00:00'),
('METER002', 'SITE002', 'Electric', 'Model B', 'Customer 2', '2024-01-02 10:00:00');

INSERT INTO `user_tb` (`user_name`, `user_real_name`) VALUES
('admin', 'Administrator'),
('user1', 'Test User');

INSERT INTO `meter_data` (`meter_id`, `datetime`, `watt`, `wh_total`, `wh_del`) VALUES
('METER001', '2024-01-01 10:00:00', '1000', '50000', '25000'),
('METER001', '2024-01-01 11:00:00', '1200', '51200', '25600');
