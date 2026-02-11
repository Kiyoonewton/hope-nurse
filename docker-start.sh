#!/bin/bash

# Hope Nurse - Docker Deployment Script
# This script helps deploy the Hope Nurse Exam System

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

print_status "Docker and Docker Compose are installed"

# Navigate to project root
cd "$(dirname "$0")/.."

print_status "Building Hope Nurse container..."
docker-compose build hope-nurse

print_status "Starting MySQL database..."
docker-compose up -d mysql

# Wait for MySQL to be healthy
print_warning "Waiting for MySQL to be ready..."
timeout=60
counter=0
while [ $counter -lt $timeout ]; do
    if docker-compose exec -T mysql mysqladmin ping -h localhost -u root -pOlaoluwa@41 --silent &> /dev/null; then
        print_status "MySQL is ready!"
        break
    fi
    counter=$((counter + 1))
    sleep 1
    if [ $counter -eq $timeout ]; then
        print_error "MySQL failed to start within ${timeout} seconds"
        exit 1
    fi
done

print_status "Starting Hope Nurse application..."
docker-compose up -d hope-nurse

# Wait for Hope Nurse to be healthy
print_warning "Waiting for Hope Nurse to be ready..."
sleep 5

print_status "Starting Nginx reverse proxy..."
docker-compose up -d nginx

echo ""
print_status "Deployment complete!"
echo ""
echo "Access the application at:"
echo "  - Local: http://localhost"
echo "  - Production: http://hope-nurse.kiyoo.live"
echo ""
echo "Useful commands:"
echo "  - View logs: docker-compose logs -f hope-nurse"
echo "  - Check status: docker-compose ps"
echo "  - Stop services: docker-compose down"
echo "  - Access MySQL: docker exec -it mysql_db mysql -u kiyoonewton -pOlaoluwa@41 exam_system"
echo ""
