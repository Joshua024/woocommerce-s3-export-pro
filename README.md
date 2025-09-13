# WooCommerce S3 Export Pro

🚀 **Professional WooCommerce CSV export automation with S3 upload capabilities. Perfect for businesses needing automated data exports to Amazon S3 with zero technical knowledge required.**

## ✨ Why Choose WooCommerce S3 Export Pro?

### 🎯 **Built for Non-Technical Users**
- **One-Click Setup**: Get your exports running in under 5 minutes
- **Beautiful Dashboard**: Modern, intuitive interface that anyone can use
- **No Coding Required**: Everything is point-and-click
- **Smart Defaults**: Works out of the box with sensible settings

### 🔥 **Professional Features**
- **Automated Daily Exports**: Set it once, forget about it
- **S3 Upload Integration**: Automatic upload to Amazon S3
- **Multiple Export Types**: Orders, customers, products, and more
- **Real-time Monitoring**: See what's happening at a glance
- **Email Notifications**: Get alerted when exports complete or fail
- **Export History Tracking**: Complete audit trail of all exports
- **Manual & Automatic Export Support**: Both trigger types supported
- **Professional Loading States**: Beautiful UI feedback during operations

### 🛡️ **Enterprise-Grade Reliability**
- **Automatic Recovery**: Self-healing when things go wrong
- **Comprehensive Logging**: Detailed logs for troubleshooting
- **Health Monitoring**: Proactive system checks
- **Backup Systems**: Multiple fallback mechanisms
- **System Status Monitoring**: Real-time health checks for all components

## 🚀 Quick Start (5 Minutes)

### Step 1: Install & Activate
1. Upload the plugin to your WordPress site
2. Run the setup script: `./install-dependencies.sh`
3. Activate the plugin in WordPress Admin

### Step 2: Configure S3 (2 minutes)
1. Go to **WooCommerce → S3 Export Pro**
2. Click **"Setup S3 Connection"**
3. Enter your AWS credentials
4. Click **"Test Connection"**

### Step 3: Start Exporting (1 minute)
1. Click **"Create Export Schedule"**
2. Choose what to export (orders, customers, etc.)
3. Set frequency (daily, weekly, monthly)
4. Click **"Start Automation"**

**That's it!** Your exports will now run automatically. 🎉

## 🎨 Beautiful User Interface

### Modern Dashboard
- **Real-time Status**: See export health at a glance with color-coded status cards
- **Quick Actions**: One-click export testing and manual triggers
- **Visual Progress**: Beautiful progress bars and status indicators
- **Smart Notifications**: In-app alerts and email notifications
- **Professional Loading States**: Smooth animations and feedback during operations
- **Export History**: Complete audit trail with filtering and search capabilities

### Intuitive Settings
- **Wizard Interface**: Step-by-step setup guides
- **Smart Defaults**: Pre-configured for common use cases
- **Visual Feedback**: Clear success/error messages with professional styling
- **Context Help**: Built-in help tooltips
- **System Status Monitoring**: Real-time health checks for WooCommerce, CSV Export Plugin, and Action Scheduler

## 📊 What You Can Export

### 📦 **Order Data**
- Complete order details
- Order line items
- Customer information
- Payment details
- Shipping information
- **Source Website** (for Salesforce integration)

### 👥 **Customer Data**
- Customer profiles
- Purchase history
- Contact information
- Account details

### 🛍️ **Product Data**
- Product catalog
- Inventory levels
- Pricing information
- Product categories

### 📈 **Analytics Data**
- Sales reports
- Revenue data
- Performance metrics
- Custom reports

## 🔧 Advanced Features

### 🎛️ **Flexible Scheduling**
- **Daily Exports**: Perfect for daily business reports
- **Weekly Exports**: Great for weekly summaries
- **Monthly Exports**: Ideal for monthly analytics
- **Custom Schedules**: Set your own timing

### 📁 **Smart File Management**
- **Automatic Organization**: Files organized by date and type
- **Version Control**: Keep historical exports
- **Cleanup Rules**: Automatic old file removal
- **Compression**: Save storage space

### 📊 **Export History & Tracking**
- **Complete Audit Trail**: Track all manual and automatic exports
- **Filtering & Search**: Filter exports by date, type, and trigger
- **Export Status Tracking**: Monitor success/failure of each export
- **Trigger Type Distinction**: Distinguish between manual and automatic exports

### 🔐 **Security & Privacy**
- **Encrypted Transfers**: Secure S3 uploads
- **Access Control**: Restrict who can access exports
- **Audit Logging**: Track all export activities
- **GDPR Compliant**: Respects data privacy

## 🛠️ Installation

### Prerequisites
- WordPress 6.0+
- WooCommerce 8.0+
- PHP 8.0+
- Composer (for dependency management)

### Quick Installation

1. **Upload the Plugin**
   ```bash
   cp -r woocommerce-s3-export-pro /path/to/wordpress/wp-content/plugins/
   ```

2. **Install Dependencies**
   ```bash
   cd /path/to/wordpress/wp-content/plugins/woocommerce-s3-export-pro/
   ./install-dependencies.sh
   ```

3. **Activate & Configure**
   - Go to WordPress Admin → Plugins
   - Activate "WooCommerce S3 Export Pro"
   - Follow the setup wizard

## 🎯 Perfect For

### 🏢 **E-commerce Businesses**
- Daily order processing
- Customer data analysis
- Inventory management
- Sales reporting

### 📊 **Data Analysts**
- Automated data collection
- Regular reporting
- Data warehousing
- Business intelligence

### 🚀 **Agencies & Consultants**
- Client data management
- Automated reporting
- Multi-site management
- White-label solutions

### 🏭 **Enterprise Companies**
- Large-scale data exports
- Compliance reporting
- System integration
- Data backup

## 💡 Use Cases

### 📈 **Daily Sales Reports**
- Export all orders from the previous day
- Upload to S3 for analysis
- Generate automated reports
- Send to stakeholders

### 👥 **Customer Analytics**
- Export customer data weekly
- Analyze purchasing patterns
- Identify high-value customers
- Create targeted marketing campaigns

### 📦 **Inventory Management**
- Daily product updates
- Stock level monitoring
- Supplier integration
- Automated reordering

### 💰 **Financial Reporting**
- Revenue tracking
- Tax reporting
- Expense analysis
- Profit margin calculations

## 🔧 Configuration

### S3 Setup
```bash
# Using WP-CLI (recommended)
wp wc-s3 setup_s3_config YOUR_ACCESS_KEY YOUR_SECRET_KEY

# Or via WordPress Admin
# Go to WooCommerce → S3 Export Pro → Settings
```

### Export Settings
- **Export Frequency**: Daily, weekly, or monthly
- **Export Time**: Choose when exports run
- **S3 Bucket**: Target S3 bucket for uploads
- **S3 Region**: AWS region for your bucket
- **Notifications**: Email alerts for success/failure
- **Export Types**: Configure which data types to export (orders, customers, products, coupons)
- **Date Range Selection**: Manual export for specific date ranges
- **Force Export Option**: Skip duplicate checks when needed

## 🎮 Usage

### WordPress Admin Interface

1. **Access Dashboard**
   - Go to WooCommerce → S3 Export Pro
   - View real-time system status with color-coded health indicators
   - Monitor export health for WooCommerce, CSV Export Plugin, and Action Scheduler

2. **Quick Actions**
   - Test S3 connection with professional loading states
   - Run manual exports with date range selection
   - Export for specific dates with export type configuration
   - View export history with filtering capabilities
   - Access detailed logs for troubleshooting

3. **Settings Management**
   - Configure S3 credentials with connection testing
   - Set export schedules and automation
   - Manage notifications and alerts
   - Configure export types and options

### WP-CLI Commands

```bash
# System monitoring
wp wc-s3 monitor_exports
wp wc-s3 check_scheduler
wp wc-s3 validate_export_system

# Export operations
wp wc-s3 export_orders
wp wc-s3 backfill_export 2025-01-15
wp wc-s3 emergency_recovery 7

# S3 management
wp wc-s3 setup_s3_config ACCESS_KEY SECRET_KEY
wp wc-s3 check_s3_config
```

## 🆘 Support

### Getting Help
1. **Check the Dashboard**: Real-time status and error messages
2. **Review Logs**: Detailed logs for troubleshooting
3. **Run Diagnostics**: Built-in system health checks
4. **Contact Support**: Professional support available

### Common Issues
- **S3 Connection Failed**: Check credentials and permissions
- **Exports Not Running**: Verify scheduling and dependencies
- **Missing Data**: Check export configuration and filters
- **System Status Issues**: Monitor WooCommerce, CSV Export Plugin, and Action Scheduler health
- **Export History Not Showing**: Check if exports are being triggered properly

## 🏆 Why Users Love It

> "Finally, a plugin that just works! No technical knowledge needed, and it saves me hours every week." - *Sarah M., E-commerce Owner*

> "The interface is beautiful and intuitive. Set it up in 5 minutes and never looked back." - *Mike R., Digital Agency*

> "Professional-grade automation that actually works reliably. Perfect for our enterprise needs." - *Jennifer L., Operations Manager*

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 👨‍💻 Author

**Joshua C. Adumchimma**
- GitHub: [@Joshua024](https://github.com/Joshua024)
- Portfolio: [dev-joshua-web-developer.pantheonsite.io](https://dev-joshua-web-developer.pantheonsite.io/)
- Professional WordPress developer specializing in WooCommerce automation

## 🚀 Get Started Today

Ready to automate your WooCommerce exports? 

1. **Download** the plugin
2. **Install** dependencies
3. **Configure** S3 connection
4. **Start** automating!

**No technical knowledge required. Just point, click, and automate!** 🎯

## 📋 Recent Updates

### 🔧 v2.0.3 - Critical Stability Fixes

#### 🚨 **Critical Bug Fixes**
- **Fixed PHP Warning**: Resolved undefined array index warning in admin settings (`$ucisettings` array access)
- **Fixed Export Hanging**: Corrected critical array access errors causing infinite loops during order processing
- **Fixed WooCommerce Object Access**: Updated deprecated array access patterns to proper WooCommerce object methods
- **Fixed Infinite Loop in Refunds**: Removed recursive `get_order_meta($refund)` call causing system hangs
- **Fixed Order Notes Processing**: Enhanced safety checks for customer notes and proper field access
- **Added Missing Method**: Implemented `format_download_permissions()` method for complete field mapping

#### 🎯 **Performance Improvements**
- **Eliminated Export Hangs**: All 9 export methods now execute without hanging or infinite loops
- **Enhanced Data Accuracy**: Export now shows real-time order statuses vs outdated cached data
- **Improved Order Discovery**: Enhanced query logic finds more complete order datasets
- **Better Error Handling**: Added comprehensive safety checks throughout the extraction process

#### ✅ **Validation & Testing**
- **Comprehensive Method Testing**: All 9 methods individually tested and validated
- **Reference Data Comparison**: Export accuracy verified against historical baseline data
- **Database Consistency**: Real-time status verification against WordPress database
- **Field Mapping Verification**: All 48 export fields properly mapped and populated

#### 🏗️ **Technical Details**
- Fixed `$item['product_id']` → `$item->get_product_id()` pattern throughout codebase
- Removed problematic `get_order_meta($refund)` recursive calls
- Enhanced `get_order_notes()` with proper field existence checks
- Implemented missing `format_download_permissions()` following established patterns
- Added `isset()` validation for admin settings array access

### 📋 v2.0.1 - Previous Updates

#### ✨ New Features
- **Source Website Field**: Added source_website field to order exports for Salesforce integration
- **Salesforce Compatibility**: Ensures proper automation in Salesforce by including required source website data
- **Export History Tracking**: Complete audit trail of all exports with filtering
- **Manual & Automatic Export Support**: Both trigger types properly tracked
- **Professional Loading States**: Beautiful UI feedback during operations
- **Enhanced System Status Monitoring**: Real-time health checks for all components
- **Improved Error Handling**: Better error messages and recovery mechanisms

#### 🎨 UI Improvements
- **Color-coded Status Cards**: Visual health indicators for system components
- **Professional Loading Spinners**: Smooth animations during operations
- **Export History Modal**: Comprehensive export tracking with filters
- **Better Visual Feedback**: Enhanced success/error message styling

#### 🔧 Technical Enhancements
- **Trigger Type Distinction**: Distinguish between manual and automatic exports
- **Enhanced Error Recovery**: Better handling of failed exports
- **Improved Logging**: More detailed export activity tracking
- **Better S3 Integration**: Enhanced connection testing and error handling

---

**WooCommerce S3 Export Pro** - Professional automation for modern businesses. 