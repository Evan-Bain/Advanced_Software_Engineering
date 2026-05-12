INSERT IGNORE INTO device_types (type_name, status)
SELECT DISTINCT product_type, 'active'
FROM products_import;

INSERT IGNORE INTO manufacturers (manufacturer_name, status)
SELECT DISTINCT brand, 'active'
FROM products_import;

INSERT IGNORE INTO equipment (device_type_id, manufacturer_id, serial_number, status)
SELECT
    dt.device_type_id,
    m.manufacturer_id,
    p.serial_number,
    'active'
FROM products_import p
JOIN device_types dt
    ON dt.type_name = p.product_type
JOIN manufacturers m
    ON m.manufacturer_name = p.brand;
