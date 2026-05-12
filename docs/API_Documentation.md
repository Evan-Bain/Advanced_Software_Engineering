# Equipment API Documentation

Base URL: `http://ec2-3-138-156-77.us-east-2.compute.amazonaws.com/api`

All endpoints return JSON with `success`, `message`, and `data`. Error responses never return blank data.

## Common Status Codes

- `200`: Successful read or update.
- `201`: Successful create.
- `400`: Missing or invalid input.
- `404`: Requested row was not found.
- `405`: HTTP method is not allowed.
- `409`: Duplicate name or serial number.
- `500`: Database error.

## Add New Equipment

Endpoint: `POST /equipment.php`

Required parameters:

- `device_type_id`: active device type ID.
- `manufacturer_id`: active manufacturer ID.
- `serial_number`: must match `SN-` followed by 64 uppercase hex characters.

Success output:

```json
{
  "success": true,
  "message": "Equipment was added successfully.",
  "data": {
    "equipment": {
      "device_id": 1,
      "type_name": "Laptop",
      "manufacturer_name": "Dell",
      "serial_number": "SN-AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA",
      "status": "active"
    }
  }
}
```

Failure examples:

- Invalid serial characters: `Serial number must match SN- followed by 64 uppercase hex characters.`
- Duplicate serial number: `That serial number already exists.`
- Inactive device type: `Selected device type is not active.`
- Inactive manufacturer: `Selected manufacturer is not active.`

## Add New Device Type

Endpoint: `POST /device_types.php`

Required parameters:

- `type_name`: alphabet letters and spaces only.

Success message: `Device type was added successfully.`

Failure examples:

- Invalid characters: `Device type name may only contain alphabet letters and spaces.`
- Duplicate name: `That device type already exists.`

## Add New Manufacturer

Endpoint: `POST /manufacturers.php`

Required parameters:

- `manufacturer_name`: alphabet letters and spaces only.

Success message: `Manufacturer was added successfully.`

Failure examples:

- Invalid characters: `Manufacturer name may only contain alphabet letters and spaces.`
- Duplicate name: `That manufacturer already exists.`

## Search Equipment By Device Type

Endpoint: `GET /equipment.php`

Required parameters:

- `search_mode=device_type`
- `device_type_id`: valid device type ID.

Optional parameters:

- `manufacturer_id`: valid manufacturer ID or `all`.
- `status`: `active`, `inactive`, or `all`.

Success message: `Equipment search returned successfully. Results are limited to 1000 records.`

No results message: `No equipment matched the search criteria.`

Failure examples:

- Invalid device type: `Invalid search data: device type was not found.`
- Invalid manufacturer: `Invalid search data: manufacturer was not found.`

## Search Equipment By Manufacturer

Endpoint: `GET /equipment.php`

Required parameters:

- `search_mode=manufacturer`
- `manufacturer_id`: valid manufacturer ID.

Optional parameters:

- `device_type_id`: valid device type ID or `all`.
- `status`: `active`, `inactive`, or `all`.

Success message: `Equipment search returned successfully. Results are limited to 1000 records.`

No results message: `No equipment matched the search criteria.`

Failure examples:

- Invalid manufacturer: `Invalid search data: manufacturer was not found.`
- Invalid device type: `Invalid search data: device type was not found.`

## Search Equipment By Serial Number

Endpoint: `GET /equipment.php`

Required parameters:

- `search_mode=serial_number`
- `serial_number`: must match `SN-` followed by 64 uppercase hex characters.

Optional parameters:

- `status`: `active`, `inactive`, or `all`.

Success message: `Equipment search returned successfully. Results are limited to 1000 records.`

No results message: `No equipment matched the search criteria.`

Failure example: `Invalid search data: serial number must match SN- followed by 64 uppercase hex characters.`

## List Equipment For Selection

Endpoint: `GET /equipment.php`

Parameters:

- `search_mode=all`
- `status=active`

Output: up to 1000 equipment rows for external-site selection lists.

## View Single Equipment Entry

Endpoint: `GET /equipment.php`

Required parameters:

- `device_id`: valid equipment ID.

Success output includes:

- `type_name`
- `manufacturer_name`
- `serial_number`
- `status`

Failure example: `Equipment was not found for the provided device_id.`

## Modify Equipment

Endpoint: `PUT /equipment.php`

Required parameters:

- `device_id`
- `device_type_id`
- `manufacturer_id`
- `serial_number`
- `status`: `active` or `inactive`.

Success message: `Equipment was updated successfully.`

Failure examples:

- Duplicate serial number: `That serial number already exists for another equipment record.`
- Invalid serial number: `Serial number must match SN- followed by 64 uppercase hex characters.`
- Inactive device type: `Selected device type is not active.`
- Inactive manufacturer: `Selected manufacturer is not active.`

## Modify Device Type

Endpoint: `PUT /device_types.php`

Required parameters:

- `device_type_id`
- `type_name`
- `status`: `active` or `inactive`.

Success message: `Device type was updated successfully.`

Failure examples:

- Duplicate new name: `That device type name already belongs to another row.`
- Invalid characters: `Device type name may only contain alphabet letters and spaces.`

## Modify Manufacturer

Endpoint: `PUT /manufacturers.php`

Required parameters:

- `manufacturer_id`
- `manufacturer_name`
- `status`: `active` or `inactive`.

Success message: `Manufacturer was updated successfully.`

Failure examples:

- Duplicate new name: `That manufacturer name already belongs to another row.`
- Invalid characters: `Manufacturer name may only contain alphabet letters and spaces.`

## Active Device Type Selection Data

Endpoint: `GET /device_types.php?status=active`

Output: active device types for external-site dropdowns.

## Active Manufacturer Selection Data

Endpoint: `GET /manufacturers.php?status=active`

Output: active manufacturers for external-site dropdowns.
