#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "${BLUE}🔧 Setting up Git Hooks for Laravel Vue Inertia Challenge...${NC}"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "${RED}❌ Error: Not in Laravel project root directory!${NC}"
    exit 1
fi

# Install Husky if not already installed
if [ ! -d "node_modules/husky" ]; then
    echo "${YELLOW}📦 Installing Husky...${NC}"
    npm install --save-dev husky
fi

# Initialize Husky
echo "${YELLOW}🔧 Initializing Husky...${NC}"
npx husky init

# Make hooks executable
echo "${YELLOW}🔐 Making hooks executable...${NC}"
chmod +x .husky/pre-commit
chmod +x .husky/pre-push

# Test the hooks
echo "${YELLOW}🧪 Testing hooks setup...${NC}"

# Check if Laravel Pint is available
if [ ! -f "vendor/bin/pint" ]; then
    echo "${RED}❌ Laravel Pint not found. Installing...${NC}"
    composer require laravel/pint --dev
fi

# Check if Pest is available
if [ ! -f "vendor/bin/pest" ]; then
    echo "${RED}❌ Pest not found. Installing...${NC}"
    composer require pestphp/pest --dev
fi

echo "${GREEN}✅ Git hooks setup complete!${NC}"
echo ""
echo "${BLUE}📋 What happens now:${NC}"
echo "  • Pre-commit hook will run Laravel Pint, PHP tests, ESLint, and Prettier on staged files"
echo "  • Pre-push hook will run comprehensive checks on all files"
echo "  • You can bypass hooks with --no-verify flag if needed"
echo ""
echo "${BLUE}🚀 Quick commands:${NC}"
echo "  • Fix PHP formatting: ${YELLOW}./vendor/bin/pint${NC}"
echo "  • Fix JS formatting: ${YELLOW}npm run format${NC}"
echo "  • Run tests: ${YELLOW}php artisan test && npm test${NC}"
echo ""
echo "${GREEN}🎉 Happy coding!${NC}" 