#!/bin/bash

# WooCommerce S3 Export Pro - Dependency Installation Script
# This script installs the required dependencies for the plugin

echo "🚀 === WooCommerce S3 Export Pro - Installing Dependencies ==="

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first:"
    echo "   https://getcomposer.org/download/"
    echo ""
    echo "💡 Quick install on macOS/Linux:"
    echo "   curl -sS https://getcomposer.org/installer | php"
    echo "   sudo mv composer.phar /usr/local/bin/composer"
    exit 1
fi

# Navigate to plugin directory
cd "$(dirname "$0")"

echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Dependencies installed successfully!"
    echo ""
    echo "🎉 The plugin is now ready to activate!"
    echo ""
    echo "📋 Next Steps:"
    echo "   1. Go to WordPress Admin → Plugins"
    echo "   2. Find 'WooCommerce S3 Export Pro' and click 'Activate'"
    echo "   3. Follow the setup wizard to configure S3"
    echo "   4. Start automating your exports!"
    echo ""
    echo "💡 Need help? Check the README.md file for detailed instructions."
else
    echo ""
    echo "❌ Failed to install dependencies. Please check the error messages above."
    echo ""
    echo "🔧 Troubleshooting:"
    echo "   - Make sure you have PHP 7.4+ installed"
    echo "   - Check that Composer is properly installed"
    echo "   - Ensure you have write permissions in this directory"
    exit 1
fi
