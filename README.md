# Money Demo

A Laravel application demonstrating efficient transaction processing with concurrent operations and money calculations using a custom Money value object.

## Features

- **Custom Money Value Object**: Handles multiple currencies (EGP, SAR, KWD) with proper rounding and precision
- **Concurrent Processing**: Uses Laravel's Concurrency facade for parallel transaction processing
- **Efficient Seeding**: Command to seed millions of transaction records using factory and bulk inserts
- **Transaction Reports**: Generate reports with total cost, price×quantity calculations, and difference analysis
- **Docker Setup**: Complete Docker environment with PostgreSQL, PHP-FPM, and Nginx

## Requirements

- Docker and Docker Compose
- PHP 8.4+
- PostgreSQL 15+

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd money-demo
```

2. Copy environment file:
```bash
cp .env.example .env
```

3. Start Docker containers:
```bash
docker-compose up -d
```

4. Install dependencies:
```bash
docker-compose exec app composer install
```

5. Generate application key:
```bash
docker-compose exec app php artisan key:generate
```

6. Run migrations:
```bash
docker-compose exec app php artisan migrate
```

## Docker Commands

### Start services
```bash
docker-compose up -d
```

### Stop services
```bash
docker-compose down
```

### View logs
```bash
docker-compose logs -f app
```

### Execute commands in container
```bash
docker-compose exec app <command>
```

## Artisan Commands

### Seed Transactions

Seed transaction records efficiently using factory and concurrent processing.

**Usage:**
```bash
docker-compose exec app php artisan transactions:seed [options]
```

**Options:**
- `--count`: Number of records to seed (default: 100,000)
- `--workers`: Number of concurrent workers (default: 10)

**Examples:**
```bash
# Seed 100,000 records (default)
docker-compose exec app php artisan transactions:seed

# Seed 1 million records with 15 concurrent workers
docker-compose exec app php artisan transactions:seed --count=1000000 --workers=15

# Seed 500,000 records
docker-compose exec app php artisan transactions:seed --count=500000
```

**How it works:**
- Uses `TransactionFactory` to generate data
- Processes records in chunks (default: 10,000 per chunk)
- Uses Laravel's `Concurrency` facade for parallel processing
- Performs bulk inserts for optimal performance
- Shows progress bar and performance metrics

### Generate Transaction Report

Generate a comprehensive report of all transactions with total cost, price×quantity, and difference calculations.

**Usage:**
```bash
docker-compose exec app php artisan transactions:report [options]
```

**Options:**
- `--count`: Number of records to report (default: all transactions)
- `--workers`: Number of concurrent workers (default: 10)

**Examples:**
```bash
# Generate report for all transactions
docker-compose exec app php artisan transactions:report

# Generate report for first 50,000 transactions
docker-compose exec app php artisan transactions:report --count=50000

# Generate report with 15 concurrent workers
docker-compose exec app php artisan transactions:report --workers=15
```

**Report Output:**
The report displays:
- **Total Cost**: Sum of all transaction costs
- **Total Price × Quantity**: Sum of price multiplied by quantity for each transaction
- **Difference**: Difference between total cost and total price×quantity
- **Statistics**: Total transactions, chunk size, processing time, etc.

**How it works:**
- Processes transactions in chunks using concurrent workers
- Calculates totals using Money value objects
- Uses `chunkById` for memory-efficient processing
- Displays results in a formatted table

## Database

### PostgreSQL Query for Report

You can also generate the same report directly from PostgreSQL:

```sql
SELECT
    count(id),
    sum(t.cost) / 100.0 as cost,
    sum(round(t.price * t.quantity)) / 100.0 as "price * quantity",
    (sum(t.cost) - sum(round(t.price * t.quantity))) / 100.0 as "diff"
FROM transactions t;
```

**Note:** The query matches the PHP calculation by:
- Rounding `price * quantity` per transaction (matching PHP's `multiply()` method)
- Summing the rounded values
- Dividing by 100 to convert from smallest units (cents) to EGP

### Database Connection

- **Host**: `localhost` (or `postgres` from within Docker network)
- **Port**: `5432`
- **Database**: `laravel` (default, configurable via `.env`)
- **Username**: `laravel` (default, configurable via `.env`)
- **Password**: `laravel` (default, configurable via `.env`)

## Money Value Object

The application uses a custom `Money` value object that:
- Supports multiple currencies (EGP, SAR, KWD)
- Stores amounts in smallest currency units (e.g., 100 = 1.00 EGP)
- Handles rounding consistently across operations
- Provides formatted output with currency labels

### Supported Currencies

- **EGP** (Egyptian Pound): Smallest unit = 100, Decimal places = 2
- **SAR** (Saudi Riyal): Smallest unit = 100, Decimal places = 2
- **KWD** (Kuwaiti Dinar): Smallest unit = 1000, Decimal places = 3

## API Endpoints

### Get Transaction Report
```
GET /api/transactions
```

Returns JSON response with transaction report data.

## Project Structure

```
app/
├── Console/
│   └── Commands/
│       ├── SeedTransactions.php      # Seed command
│       └── TransactionsReport.php   # Report command
├── Http/
│   └── Controllers/
│       └── TransactionController.php
├── Models/
│   └── Transaction.php
└── Support/
    └── Money/
        ├── Money.php                 # Money value object
        ├── Enums/
        │   └── Currency.php          # Currency enum
        └── Casts/
            └── Money.php             # Eloquent cast
```

## Performance Notes

- **Seeding**: Can seed 1 million records in approximately 30-60 seconds (depending on hardware)
- **Reporting**: Processes transactions in chunks to avoid memory issues
- **Concurrency**: Uses Laravel's Concurrency facade for parallel processing
- **Database**: Uses bulk inserts and chunked queries for optimal performance

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
