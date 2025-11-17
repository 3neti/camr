#!/bin/bash
#
# Setup Table Columns Configuration
#
# This script copies the example table column configuration to create
# an environment-specific config file.
#
# Usage:
#   ./scripts/setup-table-config.sh [--force]
#

set -e

CONFIG_FILE="resources/js/config/tableColumns.ts"
EXAMPLE_FILE="resources/js/config/tableColumns.example.ts"

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if example file exists
if [ ! -f "$EXAMPLE_FILE" ]; then
    echo -e "${RED}Error: Example file not found: $EXAMPLE_FILE${NC}"
    exit 1
fi

# Check if config already exists
if [ -f "$CONFIG_FILE" ]; then
    if [ "$1" != "--force" ]; then
        echo -e "${YELLOW}Configuration file already exists: $CONFIG_FILE${NC}"
        echo ""
        echo "The application will use your existing configuration."
        echo ""
        echo "To overwrite with the example config, run:"
        echo "  $0 --force"
        echo ""
        exit 0
    else
        echo -e "${YELLOW}Overwriting existing configuration...${NC}"
    fi
fi

# Copy example to config
cp "$EXAMPLE_FILE" "$CONFIG_FILE"

echo -e "${GREEN}✓${NC} Table columns configuration created: $CONFIG_FILE"
echo ""
echo "Next steps:"
echo "  1. Edit $CONFIG_FILE to customize for your environment"
echo "  2. Rebuild frontend assets: npm run build"
echo ""
echo "Note: This file is ignored by Git and won't be committed."
echo ""
