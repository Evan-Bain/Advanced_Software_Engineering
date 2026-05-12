INSERT INTO equipment (device_type_id, manufacturer_id, serial_number, status)
SELECT
    dt.device_type_id,
    m.manufacturer_id,
    p.serial_number,
    'active'
FROM products_import p
JOIN device_types dt
    ON dt.type_name = p.product_type
JOIN manufacturers m
    ON m.manufacturer_name = p.brand
WHERE p.id BETWEEN 1 AND 10000;
