# Integration Module

## Overview

The Integration package provides a standardized way to manage third-party integrations within a Laravel application. It supports external systems such as Zoho Books and WooCommerce, allowing you to map, sync, and store data from these platforms.

## Features

- Connect and configure third-party integrations
- Standardized data mapping from external systems
- Sync and store external system data locally
- API configuration management per integration
- Support for multiple integration providers

## Supported Integrations

| Provider | Type | Description |
|----------|------|-------------|
| Zoho Books | Accounting | Sync financial and invoice data |
| WooCommerce | E-commerce | Sync products, orders, and customers |

## Endpoints

- `GET /api/integration/list` — List all configured integrations
- `GET /api/integration/show/{uid}` — Show a specific integration
- `POST /api/integration/store` — Add a new integration
- `PUT /api/integration/update/{uid}` — Update an integration configuration
- `DELETE /api/integration/delete/{uid}` — Remove an integration

## Data Structure

Each integration record contains the following fields:

| Field | Type | Description |
|-------|------|-------------|
| `uid` | string | Unique identifier |
| `name` | string | Integration name |
| `provider` | string | Third-party provider name |
| `status` | string | active / inactive |
| `config` | json | Provider-specific configuration keys |
| `created_at` | timestamp | Creation date |
| `updated_at` | timestamp | Last updated date |

## Usage

### Adding an Integration

Send a `POST` request to `/api/integration/store` with the following payload:

```json
{
    "name": "Zoho Books",
    "provider": "zoho-books",
    "status": "active",
    "config": {
        "client_id": "your-client-id",
        "client_secret": "your-client-secret",
        "redirect_uri": "https://yourdomain.com/callback"
    }
}
```

### Syncing Data

Once an integration is configured, data sync is triggered through the provider-specific sync endpoint:

```json
{
    "integration_uid": "01KEH18TZWDA2FFPRXA3F60951",
    "sync_type": "full"
}
```

## Configuration

Integration credentials and API keys are stored in the `config` meta field and are encrypted at rest. Each provider requires different configuration keys — refer to the provider's API documentation for the required fields.

## Response Format

All responses follow the standardized API response format:

```json
{
    "status": 200,
    "message": "Request successful",
    "response_schema": {
        "data": []
    }
}
```

## Error Handling

| Code | Meaning |
|------|---------|
| `400` | Bad Request — invalid input data |
| `404` | Not Found — integration does not exist |
| `409` | Conflict — duplicate integration entry |
| `422` | Unprocessable Entity — validation or config error |
