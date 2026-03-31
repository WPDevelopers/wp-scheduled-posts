#!/bin/bash
# Runs phpcs-changed: only checks lines changed in the current git staged diff.
# Exits 0 (pass) if no new violations, exits 1 (fail) with a clean summary.

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PHPCS_CHANGED="$PLUGIN_DIR/vendor/bin/phpcs-changed"
PHPCS_WRAPPER="$PLUGIN_DIR/scripts/phpcs-wrapper.sh"

# Collect all staged PHP files passed by lint-staged
FILES=("$@")

if [ ${#FILES[@]} -eq 0 ]; then
  exit 0
fi

OUTPUT=$(php -d auto_prepend_file="$PLUGIN_DIR/phpcs-bootstrap.php" "$PHPCS_CHANGED" \
  --git-staged \
  --phpcs-path="$PHPCS_WRAPPER" \
  --standard=phpcs.xml \
  --report=full \
  "${FILES[@]}" 2>&1)

EXIT_CODE=$?

if [ $EXIT_CODE -ne 0 ]; then
  echo ""
  echo "╔══════════════════════════════════════════════════════════╗"
  echo "║        WPCS: New coding standard violations found        ║"
  echo "╚══════════════════════════════════════════════════════════╝"
  echo ""
  echo "$OUTPUT" | grep -E "^FILE:|ERROR|WARNING|^\s+[0-9]+" | sed \
    -e 's/^FILE:/📄 File:/' \
    -e 's/| ERROR   |/  ❌/' \
    -e 's/| WARNING |/  ⚠️ /'
  echo ""
  echo "  Fix the issues above, then try committing again."
  echo "  Tip: run 'composer phpcbf -- <file>' to auto-fix some errors."
  echo ""
  exit 1
fi

exit 0
