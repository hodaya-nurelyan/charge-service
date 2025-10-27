# Charge API – Laravel Home Assignment

This project implements a **Payment Charge API** built with **Laravel 11**,  
following **SOLID** principles and the **Strategy Pattern**.  
It demonstrates clean, extensible code architecture without any database dependency.

---

##  Features

- **No Database** — all logic is in-memory  
- **Validation** via `ChargeRequest` (Form Request)  
- **Strategy Pattern** for flexible gateway handling  
- **3 Gateways Implemented:** Amex, Diners, Mastercard  
- **Retry Logic:** 2 retries with exponential backoff (100ms → 200ms)  
- **Unified API Responses** (200 / 402 / 502 / 504)  
- **Feature Tests** using `Http::fake()` and `Http::sequence()`  
- **Clean, extensible, production-style code**

---

##  Installation

```bash
# Clone repository
git clone https://github.com/hodaya-nurelyan/charge-service.git
cd charge-service


# Create bootstrap cache directory if it doesn't exist
mkdir -p bootstrap/cache
chmod -R 775 bootstrap/cache

# Install dependencies
composer install

# Copy example environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Serve the app
php artisan serve
```

The API will be available at:  
  **http://127.0.0.1:8000/api/charge**

---

##  API Usage Example

### Request
```bash
curl -X POST http://127.0.0.1:8000/api/charge   -H "Content-Type: application/json"   -d '{
    "ride_id": "RIDE-123",
    "price": 11.5,
    "currency": "ILS",
    "payment_method_type": "amex",
    "card_token": "tok_test"
  }'
```

###  Success Response
```json
{
  "status": "approved",
  "ride_id": "RIDE-123",
  "amount": 11.5,
  "currency": "ILS",
  "provider": "GatewayA",
  "provider_reference": "TXN_9876",
  "authorized_at": "2025-10-25T12:30:00Z"
}
```

###  Declined Response
```json
{
  "status": "declined",
  "ride_id": "RIDE-123",
  "code": "insufficient_funds",
  "message": "Card declined",
  "provider": "GatewayA",
  "card_token": "tok_test"
}
```

---

## Tests

Feature tests simulate provider responses with `Http::fake()` and `Http::sequence()`.

```bash
php artisan test
```

### Test Scenarios
-  Success flow for each provider  
-  Decline → 402  
-  Timeout → retry → success  
-  Repeated 5xx → 502  

---


## Author

**Hodaya Nurelyan**  
Full Stack Developer – PHP, Laravel, React, AWS  
  hodaya.yd@gmail.com
 

---

