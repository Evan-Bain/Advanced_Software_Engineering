ALTER TABLE equipment
    ADD INDEX idx_equipment_type_status_id (device_type_id, status, device_id),
    ADD INDEX idx_equipment_manufacturer_status_id (manufacturer_id, status, device_id),
    ADD INDEX idx_equipment_type_manufacturer_status_id (device_type_id, manufacturer_id, status, device_id);
