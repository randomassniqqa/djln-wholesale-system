#!/bin/bash

###############################################################################
# TaskFlow Universal Lab Setup Script
# Purpose: Automated single-command deployment for any lab environment
# Usage: bash setup.sh (or ./setup.sh if executable)
###############################################################################

set -e  # Exit on first error

echo "╔════════════════════════════════════════════════════════════════════╗"
echo "║                  TaskFlow Lab Setup Script                         ║"
echo "║              Sovereign Project Management System v2.0              ║"
echo "╚════════════════════════════════════════════════════════════════════╝"
echo ""

# Color codes for terminal output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print status
print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# Check for required commands
check_requirements() {
    echo ""
    echo "Checking system requirements..."
    
    if ! command -v php &> /dev/null; then
        print_error "PHP not found. Please install PHP 8.3+"
        exit 1
    fi
    print_status "PHP found: $(php -v | head -n1)"
    
    if ! command -v composer &> /dev/null; then
        print_error "Composer not found. Please install Composer"
        exit 1
    fi
    print_status "Composer found: $(composer --version | head -n1)"
    
    if ! command -v node &> /dev/null; then
        print_error "Node.js not found. Please install Node.js 18+"
        exit 1
    fi
    print_status "Node.js found: $(node -v)"
    
    if ! command -v npm &> /dev/null; then
        print_error "npm not found. Please install npm 9+"
        exit 1
    fi
    print_status "npm found: $(npm -v)"
}

# Step 1: Install PHP dependencies
install_php_deps() {
    echo ""
    echo "Step 1/5: Installing PHP dependencies..."
    
    if [ ! -d "vendor" ]; then
        composer install --no-interaction --prefer-dist
        print_status "PHP dependencies installed"
    else
        print_warning "vendor/ already exists, skipping composer install"
    fi
}

# Step 2: Setup environment
setup_environment() {
    echo ""
    echo "Step 2/5: Setting up environment..."
    
    if [ ! -f ".env" ]; then
        cp .env.example .env
        print_status ".env created from template"
    else
        print_warning ".env already exists, skipping"
    fi
    
    if grep -q "^APP_KEY=$" .env; then
        php artisan key:generate --force
        print_status "APP_KEY generated"
    else
        print_warning "APP_KEY already set, skipping generation"
    fi
}

# Step 3: Setup database
setup_database() {
    echo ""
    echo "Step 3/5: Setting up database..."
    
    # Ensure storage directory is writable
    chmod -R 775 storage/ bootstrap/cache/ 2>/dev/null || true
    
    php artisan migrate:fresh --seed --force
    print_status "Database migrated and seeded with test data"
}

# Step 4: Install frontend dependencies
install_npm_deps() {
    echo ""
    echo "Step 4/5: Installing frontend dependencies..."
    
    if [ ! -d "node_modules" ]; then
        npm install --prefer-offline
        print_status "npm dependencies installed"
    else
        print_warning "node_modules/ already exists, skipping npm install"
    fi
}

# Step 5: Build frontend assets
build_assets() {
    echo ""
    echo "Step 5/5: Building frontend assets..."
    
    npm run build
    print_status "Assets compiled successfully"
}

# Main execution
main() {
    check_requirements
    install_php_deps
    setup_environment
    setup_database
    install_npm_deps
    build_assets
    
    echo ""
    echo "╔════════════════════════════════════════════════════════════════════╗"
    echo "║               ✓ Setup Complete - Ready to Deploy                   ║"
    echo "╚════════════════════════════════════════════════════════════════════╝"
    echo ""
    echo "Next steps:"
    echo "  1. Start development server:"
    echo "     ${GREEN}php artisan serve${NC}"
    echo ""
    echo "  2. Open in browser:"
    echo "     ${GREEN}http://localhost:8000${NC}"
    echo ""
    echo "  3. Login with test credentials:"
    echo "     Email: ${GREEN}admin@test.com${NC}"
    echo "     Password: ${GREEN}password${NC}"
    echo ""
    echo "For development with live reload, in separate terminal run:"
    echo "     ${GREEN}npm run dev${NC}"
    echo ""
}

main
