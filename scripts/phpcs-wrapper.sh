#!/bin/bash
# Wrapper around vendor/bin/phpcs that prepends the WordPress stubs bootstrap.
# Used as --phpcs-path for phpcs-changed so no hardcoded paths are needed.
PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
php -d auto_prepend_file="$PLUGIN_DIR/phpcs-bootstrap.php" "$PLUGIN_DIR/vendor/bin/phpcs" "$@"
