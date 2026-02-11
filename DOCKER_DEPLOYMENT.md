# Hope Nurse - Docker Deployment Guide

This guide explains how to deploy the Hope Nurse Exam System using Docker and Docker Compose.

## Architecture

The application consists of:
- **PHP-FPM 8.2** - Running the PHP application
- **Nginx** - Web server (internal to container)
- **MySQL 8.0** - Database server
- **Nginx Proxy** - Reverse proxy (handles external traffic)

## Prerequisites

- Docker 20.10+
- Docker Compose 2.0+
- Domain name configured (hope-nurse.kiyoo.live)

## Configuration Files

### 1. Dockerfile
Located at: `/root/kiyoo/hope-nurse/Dockerfile`

Creates a container with:
- PHP 8.2 FPM
- MySQL extensions
- Nginx web server
- Supervisor for process management

### 2. Docker Configuration Files
Located in: `/root/kiyoo/hope-nurse/docker/`

- `nginx.conf` - Main Nginx configuration
- `default.conf` - Site-specific configuration
- `supervisord.conf` - Process management

### 3. Database Configuration
Located at: `/root/kiyoo/hope-nurse/config/database.php`

Uses environment variables:
- `DB_HOST` - MySQL host (default: mysql)
- `DB_PORT` - MySQL port (default: 3306)
- `DB_USER` - Database user
- `DB_PASS` - Database password
- `DB_NAME` - Database name (default: exam_system)

## Deployment Steps

### 1. Prepare MySQL Data (First Time Only)

The MySQL container will automatically import the database dump on first startup:
```bash
# The SQL file is already configured in docker-compose.yml
# Location: ./mydb_new_2026-01-29_231237.sql
```

### 2. Build and Start Services

```bash
# Navigate to project root
cd /root/kiyoo

# Build all services
docker-compose build

# Start all services
docker-compose up -d

# Or start only hope-nurse and its dependencies
docker-compose up -d mysql hope-nurse nginx
```

### 3. Verify Services

```bash
# Check service status
docker-compose ps

# Check hope-nurse logs
docker-compose logs -f hope-nurse

# Check MySQL logs
docker-compose logs -f mysql

# Check nginx proxy logs
docker-compose logs -f nginx
```

### 4. Access the Application

- Local: http://localhost (configure nginx to route to hope-nurse)
- Production: http://hope-nurse.kiyoo.live

## Service Configuration

### Hope Nurse Service

```yaml
hope-nurse:
  build: ./hope-nurse
  expose:
    - "80"
  environment:
    - DB_HOST=mysql
    - DB_PORT=3306
    - DB_USER=kiyoonewton
    - DB_PASS=Olaoluwa@41
    - DB_NAME=exam_system
  depends_on:
    - mysql
```

### MySQL Service

```yaml
mysql:
  image: mysql:8.0
  ports:
    - "3306:3306"
  environment:
    - MYSQL_ROOT_PASSWORD=Olaoluwa@41
    - MYSQL_DATABASE=exam_system
    - MYSQL_USER=kiyoonewton
    - MYSQL_PASSWORD=Olaoluwa@41
  volumes:
    - ./db/mysql_data:/var/lib/mysql
    - ./mydb_new_2026-01-29_231237.sql:/docker-entrypoint-initdb.d/init.sql
```

## Health Checks

All services include health checks:

**Hope Nurse:**
```bash
curl -f http://localhost/
```

**MySQL:**
```bash
mysqladmin ping -h localhost -u root -pOlaoluwa@41
```

## Troubleshooting

### Container Won't Start

```bash
# Check container logs
docker-compose logs hope-nurse

# Check if port is in use
netstat -tulpn | grep :80

# Rebuild the container
docker-compose build --no-cache hope-nurse
docker-compose up -d hope-nurse
```

### Database Connection Issues

```bash
# Connect to hope-nurse container
docker exec -it hope-nurse sh

# Test MySQL connection
mysql -h mysql -u kiyoonewton -pOlaoluwa@41 exam_system

# Check database configuration
cat /var/www/html/config/database.php
```

### Permission Issues

```bash
# Fix file permissions inside container
docker exec -it hope-nurse chown -R www-data:www-data /var/www/html
docker exec -it hope-nurse chmod -R 755 /var/www/html
```

### Nginx Issues

```bash
# Check nginx configuration
docker exec -it hope-nurse nginx -t

# Restart nginx
docker exec -it hope-nurse supervisorctl restart nginx

# Check PHP-FPM
docker exec -it hope-nurse supervisorctl status php-fpm
```

## Updating the Application

```bash
# Pull latest changes (if using git)
cd /root/kiyoo/hope-nurse
git pull

# Rebuild and restart
docker-compose build hope-nurse
docker-compose up -d hope-nurse

# Or use recreate
docker-compose up -d --force-recreate hope-nurse
```

## Database Management

### Backup Database

```bash
# Export database
docker exec mysql_db mysqldump -u kiyoonewton -pOlaoluwa@41 exam_system > backup_$(date +%Y%m%d).sql
```

### Restore Database

```bash
# Import database
docker exec -i mysql_db mysql -u kiyoonewton -pOlaoluwa@41 exam_system < backup.sql
```

### Access MySQL CLI

```bash
# Connect to MySQL
docker exec -it mysql_db mysql -u kiyoonewton -pOlaoluwa@41 exam_system
```

## SSL Configuration (Production)

To enable HTTPS:

1. Uncomment the HTTPS server block in `/root/kiyoo/webserver/nginx/conf.d/hope-nurse.conf`
2. Generate SSL certificates using Let's Encrypt:
   ```bash
   cd /root/kiyoo/webserver
   ./setup-ssl.sh hope-nurse.kiyoo.live
   ```
3. Restart nginx:
   ```bash
   docker-compose restart nginx
   ```

## Monitoring

### View Real-time Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f hope-nurse
docker-compose logs -f mysql
```

### Resource Usage

```bash
# Container stats
docker stats hope-nurse mysql_db

# Disk usage
docker system df
```

## Stopping Services

```bash
# Stop all services
docker-compose down

# Stop and remove volumes (WARNING: deletes data)
docker-compose down -v

# Stop specific service
docker-compose stop hope-nurse
```

## Network Architecture

```
Internet
   ↓
Nginx Proxy (port 80/443)
   ↓
Hope Nurse Container (port 80)
   ↓ (PHP-FPM on port 9000)
   ↓
MySQL Container (port 3306)
```

All services communicate on the `subtrackr-network` Docker network.

## Security Considerations

1. **Change default passwords** in production
2. **Enable HTTPS** using SSL certificates
3. **Restrict MySQL port** (3306) access
4. **Use secrets management** for sensitive data
5. **Regular security updates** for base images
6. **Implement firewall rules** on the host

## Environment Variables

Create a `.env` file for sensitive data:

```env
# Database
MYSQL_ROOT_PASSWORD=your_secure_password
MYSQL_USER=your_user
MYSQL_PASSWORD=your_secure_password
MYSQL_DATABASE=exam_system

# Application
DB_HOST=mysql
DB_PORT=3306
DB_USER=your_user
DB_PASS=your_secure_password
DB_NAME=exam_system
```

Update `docker-compose.yml` to use:
```yaml
env_file:
  - .env
```

## Support

For issues or questions:
- Check logs: `docker-compose logs hope-nurse`
- Review configuration files
- Check Docker network: `docker network inspect subtrackr-network`
