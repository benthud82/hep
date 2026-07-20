-- Replenishment Reslot Planner persistence (MySQL 5.6 / MariaDB compatible)
-- Apply to the HEP schema before enabling saved-plan creation.

CREATE TABLE IF NOT EXISTS hep.replen_reslot_config (
    config_id TINYINT UNSIGNED NOT NULL,
    replens_per_hour DECIMAL(8,2) NOT NULL DEFAULT 15.00,
    walk_meters_per_second DECIMAL(8,3) NOT NULL DEFAULT 1.400,
    operating_days SMALLINT UNSIGNED NOT NULL DEFAULT 253,
    minimum_ship_occurrences SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    maximum_days_since_sale SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    stale_after_hours SMALLINT UNSIGNED NOT NULL DEFAULT 48,
    updated_by VARCHAR(10) DEFAULT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (config_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO hep.replen_reslot_config (
    config_id,
    replens_per_hour,
    walk_meters_per_second,
    operating_days,
    minimum_ship_occurrences,
    maximum_days_since_sale,
    stale_after_hours,
    updated_by,
    updated_at
) VALUES (1, 15.00, 1.400, 253, 15, 15, 48, 'SYSTEM', NOW())
ON DUPLICATE KEY UPDATE config_id = VALUES(config_id);

CREATE TABLE IF NOT EXISTS hep.replen_reslot_model_runs (
    model_run_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    status VARCHAR(20) NOT NULL,
    started_at DATETIME DEFAULT NULL,
    finished_at DATETIME NOT NULL,
    my_npfmvc_rows INT UNSIGNED NOT NULL DEFAULT 0,
    optimalbay_rows INT UNSIGNED NOT NULL DEFAULT 0,
    slotmaster_rows INT UNSIGNED NOT NULL DEFAULT 0,
    item_location_rows INT UNSIGNED NOT NULL DEFAULT 0,
    message VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (model_run_id),
    KEY idx_replen_model_run_status (status, finished_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS hep.replen_reslot_plans (
    plan_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    plan_name VARCHAR(100) NOT NULL,
    warehouse VARCHAR(12) NOT NULL,
    planned_date DATE NOT NULL,
    plan_status VARCHAR(20) NOT NULL DEFAULT 'READY',
    owner_user VARCHAR(10) NOT NULL,
    created_by VARCHAR(10) NOT NULL,
    model_run_id BIGINT UNSIGNED DEFAULT NULL,
    model_as_of DATETIME DEFAULT NULL,
    replens_per_hour DECIMAL(8,2) NOT NULL,
    walk_meters_per_second DECIMAL(8,3) NOT NULL,
    operating_days SMALLINT UNSIGNED NOT NULL,
    expected_replens_day DECIMAL(12,4) NOT NULL DEFAULT 0,
    expected_walk_meters_day DECIMAL(14,3) NOT NULL DEFAULT 0,
    expected_net_minutes_day DECIMAL(14,4) NOT NULL DEFAULT 0,
    expected_annual_labor_hours DECIMAL(14,2) NOT NULL DEFAULT 0,
    action_count INT UNSIGNED NOT NULL DEFAULT 0,
    notes TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    completed_at DATETIME DEFAULT NULL,
    row_version INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (plan_id),
    KEY idx_replen_plan_status (warehouse, plan_status, planned_date),
    KEY idx_replen_plan_owner (owner_user, plan_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS hep.replen_reslot_plan_items (
    plan_item_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    plan_id BIGINT UNSIGNED NOT NULL,
    sequence_no INT UNSIGNED NOT NULL DEFAULT 0,
    dependency_item_id BIGINT UNSIGNED DEFAULT NULL,
    action_type VARCHAR(30) NOT NULL,
    warehouse VARCHAR(12) NOT NULL,
    item_number INT NOT NULL,
    package_unit INT NOT NULL,
    package_type VARCHAR(12) NOT NULL,
    level_code VARCHAR(3) NOT NULL,
    from_location VARCHAR(15) DEFAULT NULL,
    target_location VARCHAR(15) DEFAULT NULL,
    current_tier VARCHAR(12) DEFAULT NULL,
    current_grid VARCHAR(15) DEFAULT NULL,
    current_depth INT DEFAULT NULL,
    suggested_tier VARCHAR(12) DEFAULT NULL,
    suggested_grid VARCHAR(15) DEFAULT NULL,
    suggested_depth INT DEFAULT NULL,
    current_max INT DEFAULT NULL,
    current_min INT DEFAULT NULL,
    suggested_max INT DEFAULT NULL,
    suggested_min INT DEFAULT NULL,
    current_replens_day DECIMAL(12,6) NOT NULL DEFAULT 0,
    suggested_replens_day DECIMAL(12,6) NOT NULL DEFAULT 0,
    replen_reduction_day DECIMAL(12,6) NOT NULL DEFAULT 0,
    walk_reduction_meters_day DECIMAL(14,3) NOT NULL DEFAULT 0,
    net_minutes_day DECIMAL(14,4) NOT NULL DEFAULT 0,
    annual_labor_hours DECIMAL(14,2) NOT NULL DEFAULT 0,
    shipment_occurrences INT NOT NULL DEFAULT 0,
    days_since_sale INT NOT NULL DEFAULT 0,
    confidence_code VARCHAR(20) NOT NULL,
    override_reason VARCHAR(255) DEFAULT NULL,
    assigned_to VARCHAR(10) DEFAULT NULL,
    item_status VARCHAR(20) NOT NULL DEFAULT 'READY',
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    completed_by VARCHAR(10) DEFAULT NULL,
    actual_location VARCHAR(15) DEFAULT NULL,
    actual_max INT DEFAULT NULL,
    actual_min INT DEFAULT NULL,
    execution_note TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (plan_item_id),
    UNIQUE KEY uq_replen_plan_item_key (plan_id, warehouse, item_number, package_unit, package_type, level_code),
    KEY idx_replen_plan_item_status (plan_id, item_status, sequence_no),
    KEY idx_replen_plan_item_assignee (assigned_to, item_status),
    KEY idx_replen_plan_item_dependency (dependency_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS hep.replen_reslot_location_reservations (
    warehouse VARCHAR(12) NOT NULL,
    target_location VARCHAR(15) NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    plan_item_id BIGINT UNSIGNED NOT NULL,
    reserved_at DATETIME NOT NULL,
    PRIMARY KEY (warehouse, target_location),
    KEY idx_replen_reservation_plan (plan_id, plan_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS hep.replen_reslot_plan_events (
    event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    plan_id BIGINT UNSIGNED NOT NULL,
    plan_item_id BIGINT UNSIGNED DEFAULT NULL,
    event_type VARCHAR(30) NOT NULL,
    from_status VARCHAR(20) DEFAULT NULL,
    to_status VARCHAR(20) DEFAULT NULL,
    event_user VARCHAR(10) NOT NULL,
    event_note TEXT,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (event_id),
    KEY idx_replen_event_plan (plan_id, created_at),
    KEY idx_replen_event_item (plan_item_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
