#!/usr/bin/env bash
set -e

# TaskFlow: Laravel -> Apidog Sync Workflow
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LARAVEL_DIR="$PROJECT_ROOT/laravel"

echo "============================================="
echo " TaskFlow: Laravel -> Apidog Sync Workflow   "
echo "============================================="

FORCE=false
OVERWRITE=false

while [[ "$#" -gt 0 ]]; do
    case $1 in
        --force) FORCE=true ;;
        --overwrite) OVERWRITE=true ;;
        --project-id=*) PROJECT_ID="${1#*=}" ;;
        --token=*) TOKEN="${1#*=}" ;;
        *) echo "Unknown parameter: $1"; exit 1 ;;
    esac
    shift
done

PROJECT_ID="${PROJECT_ID:-$APIDOG_PROJECT_ID}"
TOKEN="${TOKEN:-$APIDOG_ACCESS_TOKEN}"

# Step 1: Generate specification
echo -e "\n[1/3] Generating OpenAPI 3.1.0 specification..."
cd "$LARAVEL_DIR"
php artisan openapi:generate

# Step 2: Validate specification
SPEC_PATH="$PROJECT_ROOT/docs/openapi.json"
echo -e "\n[2/3] Validating OpenAPI specification at $SPEC_PATH..."
if [ ! -f "$SPEC_PATH" ]; then
    echo "Error: Specification file not found at $SPEC_PATH!"
    exit 1
fi

php -r "
    \$content = file_get_contents('$SPEC_PATH');
    \$data = json_decode(\$content, true);
    if (!\$data || !isset(\$data['openapi'], \$data['paths'])) {
        fwrite(STDERR, 'Invalid OpenAPI JSON specification' . PHP_EOL);
        exit(1);
    }
    echo '✓ Specification is valid JSON (OpenAPI ' . \$data['openapi'] . ', ' . count(\$data['paths']) . ' paths).' . PHP_EOL;
"

# Step 3: Sync to Apidog
echo -e "\n[3/3] Synchronizing with Apidog..."
ARGS=("artisan" "apidog:sync")
if [ "$FORCE" = true ]; then
    ARGS+=("--force")
else
    ARGS+=("--dry-run")
fi

if [ "$OVERWRITE" = true ]; then
    ARGS+=("--overwrite")
fi

if [ -n "$PROJECT_ID" ]; then
    ARGS+=("--project-id=$PROJECT_ID")
fi

if [ -n "$TOKEN" ]; then
    ARGS+=("--token=$TOKEN")
fi

php "${ARGS[@]}"

