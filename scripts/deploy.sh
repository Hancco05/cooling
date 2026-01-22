#!/bin/bash

# Script de deployment para Cooling System
set -e

echo "🚀 INICIANDO DEPLOYMENT DE COOLING SYSTEM 🚀"
echo "============================================"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Variables
ENV=${1:-production}
COMPOSE_FILE="docker-compose.$ENV.yml"
BACKUP_DIR="./backups/$(date +%Y%m%d_%H%M%S)"

# Funciones
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Verificar archivo de entorno
if [ ! -f ".env.$ENV" ]; then
    print_error "Archivo .env.$ENV no encontrado"
    exit 1
fi

# Cargar variables de entorno
export $(cat .env.$ENV | xargs)

# Paso 1: Backup de la base de datos
print_warning "Realizando backup de la base de datos..."
mkdir -p $BACKUP_DIR

if docker-compose -f $COMPOSE_FILE exec -T cooling-db \
    mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > $BACKUP_DIR/backup.sql; then
    print_success "Backup completado: $BACKUP_DIR/backup.sql"
else
    print_error "Error en el backup"
    exit 1
fi

# Paso 2: Detener servicios
print_warning "Deteniendo servicios..."
docker-compose -f $COMPOSE_FILE down

# Paso 3: Actualizar código
print_warning "Actualizando código..."
git pull origin main

# Paso 4: Construir imágenes
print_warning "Construyendo imágenes Docker..."
docker-compose -f $COMPOSE_FILE build --no-cache

# Paso 5: Iniciar servicios
print_warning "Iniciando servicios..."
docker-compose -f $COMPOSE_FILE up -d

# Paso 6: Esperar a que los servicios estén listos
print_warning "Esperando a que los servicios inicien..."
sleep 30

# Paso 7: Ejecutar migraciones
print_warning "Ejecutando migraciones de base de datos..."
docker-compose -f $COMPOSE_FILE exec php1 php spark migrate --force

# Paso 8: Limpiar cache
print_warning "Limpiando cache..."
docker-compose -f $COMPOSE_FILE exec php1 php spark cache:clear

# Paso 9: Verificar estado
print_warning "Verificando estado de los servicios..."
docker-compose -f $COMPOSE_FILE ps

# Paso 10: Verificar salud
print_warning "Verificando salud de la aplicación..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://$DOMAIN/health)
if [ "$HTTP_STATUS" = "200" ]; then
    print_success "✅ Aplicación funcionando correctamente"
else
    print_error "❌ La aplicación responde con código: $HTTP_STATUS"
    exit 1
fi

# Paso 11: Notificación de éxito
print_success "🎉 DEPLOYMENT COMPLETADO EXITOSAMENTE!"
echo ""
echo "📊 Información del deployment:"
echo "   Entorno: $ENV"
echo "   Fecha: $(date)"
echo "   Dominio: https://$DOMAIN"
echo "   Backup: $BACKUP_DIR/backup.sql"
echo ""
echo "🔧 Servicios desplegados:"
echo "   - Aplicación PHP (2 instancias)"
echo "   - Base de datos MySQL"
echo "   - Redis Cache"
echo "   - WebSocket Server"
echo "   - Queue Worker"
echo "   - Monitoring (Prometheus + Grafana)"
echo ""
echo "📈 URLs de monitoreo:"
echo "   - Aplicación: https://$DOMAIN"
echo "   - API: https://$DOMAIN/api"
echo "   - Grafana: https://$DOMAIN:3000"
echo "   - Prometheus: https://$DOMAIN:9090"
echo ""
echo "🔄 Comandos útiles:"
echo "   Ver logs: docker-compose -f $COMPOSE_FILE logs -f"
echo "   Scale PHP: docker-compose -f $COMPOSE_FILE up -d --scale php=4"
echo "   Backup manual: ./scripts/backup.sh"
echo "   Rollback: ./scripts/rollback.sh $BACKUP_DIR"

# Enviar notificación (opcional)
if [ ! -z "$SLACK_WEBHOOK" ]; then
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"✅ Deployment completado en $ENV\n• Aplicación: https://$DOMAIN\n• Fecha: $(date)\"}" \
        $SLACK_WEBHOOK
fi