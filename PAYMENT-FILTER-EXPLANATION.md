# Payment Method Filter - How It Works

## Date: October 9, 2025

---

## ✅ **100% User-Controlled - NO HARDCODING**

The payment method filter is now **completely flexible** and controlled through the admin UI. There is **NO hardcoding** of excluded payment methods.

---

## 🔄 How It Works (Step by Step)

### 1. **Admin UI Configuration**
**Location**: WooCommerce → S3 Export Pro → Export Types Configuration

When creating or editing an export type, administrators see a section called **"Excluded Payment Methods"** with checkboxes for:
- **No Payment Method (Manual Orders)** - Unchecked by default
- **Pay with WorldPay (worldpay)** - Unchecked by default  
- **Pay by bank (gocardless)** - Unchecked by default
- **Pay by Invoice (cheque)** - Unchecked by default
- **Pay by Invoice (cod)** - Unchecked by default
- **Direct bank transfer (bacs)** - Unchecked by default

**All checkboxes start UNCHECKED** - meaning by default, ALL orders (including manual ones) will be exported.

---

### 2. **User Makes Selection**
The administrator can check any payment methods they want to **exclude** from the export.

**Examples**:

**Scenario A**: User wants to exclude only manual orders
- ✅ Check "No Payment Method (Manual Orders)"
- ⬜ Leave all others unchecked
- **Result**: Only orders without a payment method will be excluded

**Scenario B**: User wants to exclude manual orders AND cheque payments
- ✅ Check "No Payment Method (Manual Orders)"
- ✅ Check "Pay by Invoice (cheque)"
- ⬜ Leave all others unchecked
- **Result**: Orders without payment method AND cheque orders will be excluded

**Scenario C**: User wants to export ALL orders (including manual)
- ⬜ Leave ALL checkboxes unchecked
- **Result**: ALL orders will be exported, regardless of payment method

---

### 3. **Save Configuration**
When the user clicks "Save Export Types Configuration":

**File**: `includes/Export_Manager.php`  
**Function**: `ajax_save_export_types_config()` (Line ~641)

```php
'excluded_payment_methods' => isset($type['excluded_payment_methods']) && is_array($type['excluded_payment_methods']) 
    ? array_map('sanitize_text_field', $type['excluded_payment_methods']) 
    : array(),  // <-- Empty array if nothing is selected
```

**What happens**:
- If user checked some payment methods → Saves those as an array (e.g., `['', 'cheque']`)
- If user unchecked all → Saves an empty array (`[]`)
- **NO DEFAULT VALUES ARE FORCED**

---

### 4. **Export Execution**
When an export runs (manual or automated):

**File**: `includes/CSV_Generator.php`  
**Function**: `extract_orders_data()` (Line ~218)

```php
// Get the excluded payment methods from configuration
$excluded_payment_methods = isset($export_type['excluded_payment_methods']) 
    ? $export_type['excluded_payment_methods'] 
    : array();  // <-- If not set, defaults to empty array (exclude nothing)

// Check if this order's payment method should be excluded
$payment_method = $order->get_payment_method();

if (in_array($payment_method, $excluded_payment_methods) || 
    (empty($payment_method) && in_array('', $excluded_payment_methods))) {
    // Skip this order
    $this->log("Skipping order ID: {$order_id} with excluded payment method: " . ($payment_method ?: 'none/manual'));
    continue;
}
```

**Logic**:
1. Get the `excluded_payment_methods` array from the export type configuration
2. If the array is empty → Export ALL orders (no exclusions)
3. If the array has values → Check each order:
   - If order's payment method is in the excluded list → Skip it
   - If order has no payment method AND `''` (empty string) is in the excluded list → Skip it
   - Otherwise → Include it in the export

---

## 📊 Real-World Examples

### Example 1: Default Configuration (Nothing Excluded)
```
Configuration: excluded_payment_methods = []

Orders in system:
1. Order #100 - WorldPay → ✅ EXPORTED
2. Order #101 - No payment method (manual) → ✅ EXPORTED
3. Order #102 - Cheque → ✅ EXPORTED
4. Order #103 - BACS → ✅ EXPORTED

Result: ALL 4 orders exported
```

### Example 2: Exclude Manual Orders Only
```
Configuration: excluded_payment_methods = ['']

Orders in system:
1. Order #100 - WorldPay → ✅ EXPORTED
2. Order #101 - No payment method (manual) → ❌ EXCLUDED
3. Order #102 - Cheque → ✅ EXPORTED
4. Order #103 - BACS → ✅ EXPORTED

Result: 3 orders exported (order #101 excluded)
```

### Example 3: Exclude Manual and Cheque
```
Configuration: excluded_payment_methods = ['', 'cheque']

Orders in system:
1. Order #100 - WorldPay → ✅ EXPORTED
2. Order #101 - No payment method (manual) → ❌ EXCLUDED
3. Order #102 - Cheque → ❌ EXCLUDED
4. Order #103 - BACS → ✅ EXPORTED

Result: 2 orders exported (orders #101 and #102 excluded)
```

### Example 4: Exclude Everything Except WorldPay
```
Configuration: excluded_payment_methods = ['', 'cheque', 'cod', 'bacs', 'gocardless']

Orders in system:
1. Order #100 - WorldPay → ✅ EXPORTED
2. Order #101 - No payment method (manual) → ❌ EXCLUDED
3. Order #102 - Cheque → ❌ EXCLUDED
4. Order #103 - BACS → ❌ EXCLUDED

Result: 1 order exported (only WorldPay orders)
```

---

## 🎯 Key Points

### ✅ What's Good:
1. **100% User Control**: No hardcoded defaults
2. **Flexible**: Can exclude any combination of payment methods
3. **Clear**: UI shows all available payment gateways dynamically
4. **Transparent**: Export logs show which orders are being skipped and why
5. **Per Export Type**: Each export type can have different exclusion rules

### ℹ️ Important Notes:
1. **Empty String (`''`)**: Represents orders with no payment method (manual orders)
2. **Default Behavior**: If no payment methods are selected for exclusion, ALL orders are exported
3. **Multiple Export Types**: Different export types can have different exclusion rules
4. **Dynamic List**: Payment methods list is generated from active WooCommerce payment gateways

---

## 🔧 Technical Details

### Database Storage
Excluded payment methods are stored as an array in the `wc_s3_export_pro_export_types` option:

```php
array(
    'id' => 'websales_12345',
    'name' => 'WebSales',
    'type' => 'orders',
    // ... other settings ...
    'excluded_payment_methods' => array('', 'cheque'),  // <-- Stored here
)
```

### UI Implementation
- **Existing Export Types**: Checkboxes dynamically reflect saved configuration
- **New Export Types**: All checkboxes start unchecked (no defaults)
- **JavaScript**: Shows/hides section based on export type (only visible for "Orders")

### Export Logic
- **No Config Set**: Defaults to empty array (exclude nothing)
- **Empty Array Saved**: Exports all orders
- **Array With Values**: Excludes orders matching those payment methods

---

## 🚀 Benefits for Client

1. **Full Control**: Client decides which payment methods to exclude
2. **No Surprises**: No hidden defaults or hardcoded exclusions
3. **Easy to Change**: Just check/uncheck boxes and save
4. **Clear Documentation**: UI text explains what each option does
5. **Audit Trail**: Export logs show exactly what was excluded and why

---

## 📝 Summary

**The payment method filter is now a pure UI-driven feature with ZERO hardcoding. The system respects whatever the user configures in the admin panel, with no forced defaults.**

If the client wants to:
- **Exclude manual orders** → Check "No Payment Method"
- **Include manual orders** → Leave it unchecked
- **Exclude specific gateways** → Check those gateways
- **Export everything** → Leave all unchecked

**It's that simple!** 🎉
