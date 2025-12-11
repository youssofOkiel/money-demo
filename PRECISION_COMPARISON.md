# Real Example: Precision Comparison Between Configurations

## Setup

Let's use 3 transactions with these values:

**Transaction 1:**
- Price: `1.23` EGP
- Quantity: `2.5`

**Transaction 2:**
- Price: `3.45` EGP  
- Quantity: `1.333`

**Transaction 3:**
- Price: `4.56` EGP
- Quantity: `2.7`

---

## Configuration A: `decimalPlaces = 6`, `smallestUnit = 1000000`

### Transaction 1:
```php
// Price stored: 1230000 (1.23 * 1000000)
// Multiply: 1230000 * 2.5
$result = 1230000 * 2.5;
// PHP floating point: 3075000.0 (exact in this case)
// Round: 3075000
// Result: 3.075000 EGP
```

### Transaction 2:
```php
// Price stored: 3450000 (3.45 * 1000000)
// Multiply: 3450000 * 1.333
$result = 3450000 * 1.333;
// PHP floating point: 4598850.0 (might be 4598849.999999999 or 4598850.000000001)
// Round: 4598850 (or 4598849 if precision error)
// Result: 4.598850 EGP (or 4.598849 if error)
```

### Transaction 3:
```php
// Price stored: 4560000 (4.56 * 1000000)
// Multiply: 4560000 * 2.7
$result = 4560000 * 2.7;
// PHP floating point: 12312000.0 (might have tiny precision error)
// Round: 12312000
// Result: 12.312000 EGP
```

### Total Price × Quantity:
```
3.075000 + 4.598850 + 12.312000 = 19.985850 EGP
(Or with errors: 3.075000 + 4.598849 + 12.312000 = 19.985849 EGP)
```

### Total Cost (from factory, rounded to 2 decimals - **Real-World Money Simulation**):
```
Transaction 1: round(1.23 * 2.5, 2) = 3.08
Transaction 2: round(3.45 * 1.333, 2) = 4.60
Transaction 3: round(4.56 * 2.7, 2) = 12.31
Total: 3.08 + 4.60 + 12.31 = 19.99 EGP
```

> **Note:** The factory rounds to 2 decimals intentionally to simulate real-world money transactions. In reality, when you calculate `price * quantity`, the result is typically rounded to the currency's standard precision (2 decimals for EGP/SAR). This is the actual amount of money that would be charged/paid in a real transaction.

### Difference:
```
19.99 - 19.985850 = 0.004150 EGP (or 0.004151 with errors)
```

**Breakdown of the difference:**
- **Expected difference from real-world rounding**: ~0.004150 EGP (this is normal - real money is rounded to 2 decimals)
- **Additional error from floating-point precision**: ±0.000001 per transaction (this accumulates and is the problem)

---

## Configuration B: `decimalPlaces = 2`, `smallestUnit = 100`

### Transaction 1:
```php
// Price stored: 123 (1.23 * 100)
// Multiply: 123 * 2.5
$result = 123 * 2.5;
// PHP floating point: 307.5 (exact)
// Round: 308
// Result: 3.08 EGP
```

### Transaction 2:
```php
// Price stored: 345 (3.45 * 100)
// Multiply: 345 * 1.333
$result = 345 * 1.333;
// PHP floating point: 459.885 (might be 459.88499999999994)
// Round: 460
// Result: 4.60 EGP
```

### Transaction 3:
```php
// Price stored: 456 (4.56 * 100)
// Multiply: 456 * 2.7
$result = 456 * 2.7;
// PHP floating point: 1231.2 (exact)
// Round: 1231
// Result: 12.31 EGP
```

### Total Price × Quantity:
```
3.08 + 4.60 + 12.31 = 19.99 EGP
```

### Total Cost (from factory, rounded to 2 decimals - **Real-World Money Simulation**):
```
Transaction 1: round(1.23 * 2.5, 2) = 3.08
Transaction 2: round(3.45 * 1.333, 2) = 4.60
Transaction 3: round(4.56 * 2.7, 2) = 12.31
Total: 3.08 + 4.60 + 12.31 = 19.99 EGP
```

> **Note:** The factory rounds to 2 decimals intentionally to simulate real-world money transactions. This matches the actual amount of money that would be charged/paid in a real transaction.

### Difference:
```
19.99 - 19.99 = 0.00 EGP (or tiny 0.000001 if precision error)
```

**Breakdown of the difference:**
- **Expected difference from real-world rounding**: 0.00 EGP (no difference because both are rounded to 2 decimals)
- **Additional error from floating-point precision**: ±0.000001 per transaction (minimal with smaller integers)

---

## Why the Difference is Bigger with Configuration A

### 1. **Larger Integers Amplify Floating Point Errors**
   - With `smallestUnit = 1000000`: `3450000 * 1.333` can produce `4598849.999999999`
   - With `smallestUnit = 100`: `345 * 1.333` produces `459.885` (smaller error)

### 2. **Errors Accumulate**
   - **Configuration A**: Each transaction can have a ±0.000001 error, which accumulates
   - **Configuration B**: Errors are smaller and often cancel out

### 3. **Real-World Rounding vs High-Precision Calculation**
   - **Factory behavior (intentional)**: Rounds `cost` to 2 decimals to simulate real-world money transactions
     - In real life, when you calculate `price * quantity`, the result is rounded to the currency's standard precision (2 decimals for EGP/SAR)
     - This is the actual amount of money that would be charged/paid
   - **Configuration A**: Calculates `price * quantity` with 6 decimal precision
     - This shows what the calculation would be before real-world rounding
   - **The difference**: Part of it is expected (real-world rounding), but floating-point errors make it larger than it should be

### Real Impact with 10,000 Transactions:
- **Configuration A**: Difference can be ±0.01 to ±0.10 EGP or more
- **Configuration B**: Difference is typically ±0.00 to ±0.01 EGP

---

## Summary

This is why you see a bigger difference with `smallestUnit = 1000000` compared to `smallestUnit = 100`. The larger integers used for storage amplify floating-point precision errors during multiplication operations, and these errors accumulate across many transactions.

### Key Takeaways:

1. **Factory Rounding is Intentional and Correct**
   - The factory rounds `cost` to 2 decimals to simulate real-world money behavior
   - In real transactions, amounts are rounded to the currency's standard precision (2 decimals for EGP/SAR)
   - This is not a bug - it's simulating how money actually works in the real world

2. **The Real Problem: Floating-Point Precision Errors**
   - When using `smallestUnit = 1000000`, floating-point arithmetic introduces precision errors
   - These errors accumulate across many transactions
   - The solution: Use integer arithmetic in `multiply()` to avoid floating-point errors

3. **Expected vs Unexpected Differences**
   - **Expected difference**: The difference between real-world rounded money (2 decimals) and high-precision calculation (6 decimals)
   - **Unexpected difference**: Additional errors from floating-point precision that accumulate
   - With proper integer arithmetic, you should only see the expected difference from rounding

4. **When using `smallestUnit = 1000000` (6 decimal places):**
   - Use integer arithmetic in `multiply()` to avoid floating-point errors
   - Understand that some difference is expected due to real-world rounding (factory behavior)
   - The goal is to minimize the unexpected floating-point precision errors
