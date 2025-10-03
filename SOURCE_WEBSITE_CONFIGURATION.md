# Source Website Configuration Feature

## Overview

This feature adds a flexible, per-export-type configuration system for the "Source Website" field in WooCommerce S3 Export Pro. You can now enable/disable the source website field for any export type and optionally specify custom values (e.g., "Funds Online" instead of the full URL).

## Features

### ✅ Per-Export-Type Configuration
- Enable or disable the source website field independently for each export type
- No need to include source website in all exports if not needed

### ✅ Custom Value Support
- Use site URL automatically (default behavior)
- OR specify a custom value like "Funds Online", "My Store", etc.
- OR use any custom URL

### ✅ Admin Interface
- User-friendly toggle switches for enable/disable
- Clear visual indicators showing what value will be used
- Real-time preview of current behavior
- No code changes required - all configuration through WordPress admin

### ✅ Backward Compatible
- Existing exports continue to work without changes
- If not configured, falls back to site URL (existing behavior)
- No breaking changes to existing field mappings

## How It Works

### 1. Configuration Storage
- Settings are stored in WordPress options table as `wc_s3_export_pro_source_website_config`
- Structure:
  ```php
  [
      'Export Type Name' => [
          'enabled' => true/false,          // Whether to include source_website field
          'use_custom' => true/false,       // Whether to use custom value
          'value' => 'Custom Value'         // The custom value (if use_custom is true)
      ]
  ]
  ```

### 2. Export Processing
- When generating CSV, the system checks if source website is configured for that export type
- Priority order:
  1. Check if enabled for this export type → if not, field is NOT included
  2. Check if custom value is set → use custom value
  3. Fall back to WordPress site URL

### 3. UI Flow
1. Navigate to **WooCommerce → S3 Export Pro** in WordPress admin
2. Scroll to **"Source Website Configuration"** section
3. For each export type:
   - ✅ Check "Enable Source Website field" to include it in exports
   - ✅ Check "Use custom value" to specify a custom value
   - Enter your custom value (e.g., "Funds Online")
4. Click "Save Source Website Configuration"
5. Configuration is saved and applied to future exports

## Usage Examples

### Example 1: Use "Funds Online" for Web Sales Orders
```
Export Type: Web Sales Orders
✅ Enable Source Website field
✅ Use custom value instead of site URL
Custom Value: "Funds Online"

Result: All exports will have source_website = "Funds Online"
```

### Example 2: Use Site URL for Customer Exports
```
Export Type: Customer Export
✅ Enable Source Website field
❌ Use custom value instead of site URL

Result: All exports will have source_website = "https://fundsonline.co.uk"
```

### Example 3: Disable Source Website for Internal Reports
```
Export Type: Internal Sales Report
❌ Enable Source Website field

Result: source_website field will NOT be included in exports
```

## Modified Files

### 1. `includes/Settings.php`
**Added:**
- `SOURCE_WEBSITE_CONFIG_OPTION` constant
- `get_source_website_config()` - Retrieve all source website configurations
- `update_source_website_config()` - Save source website configurations
- `sanitize_source_website_config()` - Sanitize input data
- `get_source_website_for_export_type()` - Get configured value for specific export type

### 2. `includes/CSV_Generator.php`
**Added:**
- `$current_export_type` property to track the export type being processed

**Modified:**
- `generate_csv()` - Now stores current export type for reference
- `get_source_website()` - Now checks configuration first before falling back to site URL
  - If configured and enabled: uses custom value or site URL per configuration
  - If not configured: uses site URL (backward compatible)
- `create_csv_file()` - **Automatically injects Source Website field as the LAST column**
  - Checks if Source Website is configured for the export type
  - Adds `source_website` to field mappings and headers arrays
  - Detects and removes any incorrect "Source Website" field mappings
  - Ensures proper column alignment in CSV output

**Key Behavior:**
- ✅ Source Website field is **automatically added** to CSV exports when configured
- ✅ **No need to include it in field mappings** - handled automatically by the system
- ✅ Always appears as the **LAST column** in the CSV file
- ✅ Prevents duplicate or misplaced Source Website columns

### 3. `includes/Export_Manager.php`
**Added:**
- AJAX action hook: `wp_ajax_wc_s3_save_source_website_config`
- `ajax_save_source_website_config()` - Handler for saving source website configuration via AJAX

### 4. `admin/views/admin-page.php`
**Added:**
- New "Source Website Configuration" section in admin UI
- Form for configuring source website per export type
- Enable/disable toggles with visual feedback
- Custom value input fields
- Real-time preview of current behavior
- JavaScript handlers for toggle behavior and form submission

## Technical Implementation Details

### Data Flow
```
1. User configures in Admin UI
   ↓
2. JavaScript captures form submission
   ↓
3. AJAX sends data to Export_Manager::ajax_save_source_website_config()
   ↓
4. Settings::update_source_website_config() sanitizes and saves
   ↓
5. CSV_Generator::get_source_website() checks configuration during export
   ↓
6. Appropriate value included in CSV output
```

### Security
- AJAX nonce verification on all requests
- User capability check (`manage_woocommerce` required)
- Input sanitization using WordPress functions:
  - `sanitize_key()` for export type names
  - `esc_url_raw()` for URL values
  - Strict boolean casting for flags

### Performance
- Configuration loaded once per export session
- Minimal database queries (single option read)
- No impact on existing exports that don't use this feature

## Testing Checklist

- [ ] Configure source website for one export type, verify it appears in exports
- [ ] Test with custom value "Funds Online" - should show exactly that text
- [ ] Test with site URL option - should show full WordPress site URL
- [ ] Disable source website for an export type - field should not appear
- [ ] Test multiple export types with different configurations
- [ ] Verify backward compatibility - unconfigured exports should still use site URL
- [ ] Test form save/reload - configuration should persist
- [ ] Test toggle behavior - options should show/hide correctly
- [ ] Verify security - unauthorized users cannot access configuration

## Migration Notes

**Existing Installations:**
- No migration required
- Existing exports will continue using site URL
- Source website field will be included by default (if it was before)
- Configuration is opt-in - no changes to existing behavior until configured

**New Installations:**
- Source website field available in orders export by default
- Can be configured per export type as needed

## Support & Troubleshooting

### ⚠️ Important: Do NOT Add Source Website to Field Mappings

**The Source Website field is automatically injected** into CSV exports when configured in the Source Website Configuration section. You should **NOT** manually add it to field mappings.

**Why?**
- The system automatically adds Source Website as the last column
- Manual field mapping will cause duplicate columns
- Manual mapping may use incorrect data source

**Correct Workflow:**
1. ✅ Configure Source Website in the "Source Website Configuration" section
2. ✅ Enable it for the export types you need
3. ✅ Set custom value if needed
4. ❌ **DO NOT** add `source_website` to Field Mappings Configuration

### Issue: Source website field not appearing
**Solution:** Check that:
1. Export type has source website **enabled** in the "Source Website Configuration" section
2. ~~Field mapping includes `source_website` field~~ ← **NOT NEEDED - Auto-injected**
3. Export type configuration was saved successfully
4. Run a new export (old exports won't be updated)

### Issue: Duplicate "Source Website" columns
**Solution:**
1. Check Field Mappings Configuration for any manual `source_website` entries
2. Remove any manual Source Website field mappings
3. The system will automatically add it as the last column
4. Run fresh export to verify

### Issue: Source Website showing wrong data (e.g., order IDs)
**Solution:**
1. This happens when there's an incorrect field mapping (e.g., `order_id` → `Source Website`)
2. Edit the export type Field Mappings
3. Remove or fix the incorrect mapping
4. Let the system auto-inject the correct Source Website field
5. Run fresh export to verify

### Issue: Custom value not being used
**Solution:** Verify:
1. "Use custom value" is checked
2. Custom value field is not empty
3. Configuration was saved (check browser network tab for AJAX response)

### Issue: Configuration not saving
**Solution:** Check:
1. User has `manage_woocommerce` capability
2. Browser console for JavaScript errors
3. WordPress debug.log for PHP errors
4. AJAX nonce is valid

## Future Enhancements

Potential improvements for future versions:
- [ ] Bulk configuration for multiple export types
- [ ] Import/export configuration settings
- [ ] Template system for common configurations
- [ ] Per-export-schedule overrides
- [ ] Dynamic value support (e.g., use different values based on order data)

## Version History

**v2.1.0** (Current Implementation)
- Added per-export-type source website configuration
- Auto-injection of Source Website field as last column
- Removed source_website from default field mappings and data sources
- Fixed duplicate Source Website column issues
- Added proper column alignment handling
- Updated Export History to show human-readable export type names

**Features:**
- Source Website Configuration admin UI section
- Enable/disable per export type
- Custom value support (e.g., "Funds Online")
- Automatic field injection (no manual field mapping needed)
- Proper handling of incorrect/duplicate mappings
- Added admin UI for configuration management
- Added AJAX handlers for saving configuration
- Updated CSV generator to use configuration
- Maintained backward compatibility

---

**Implementation Date:** October 2, 2025
**Developer:** AI Assistant with Joshua C. Adumchimma
**Status:** ✅ Complete and Ready for Testing
