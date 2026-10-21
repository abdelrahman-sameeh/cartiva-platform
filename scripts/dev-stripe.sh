#!/usr/bin/env bash

set -Eeuo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
server_host="${DEV_SERVER_HOST:-127.0.0.1}"
server_port="${DEV_SERVER_PORT:-8000}"
base_url="http://${server_host}:${server_port}"
runtime_dir=''
process_ids=()
cleaned_up=false

usage() {
    cat <<'EOF'
Usage: composer dev:stripe

Starts the local Laravel development stack in one terminal:
  - Laravel server
  - Vite development server
  - Stripe Accounts v2 listener for seller onboarding
  - Stripe Checkout listener for order payments

Optional environment variables:
  DEV_SERVER_HOST  Laravel host (default: 127.0.0.1)
  DEV_SERVER_PORT  Laravel port (default: 8000)
EOF
}

if [[ "${1:-}" == '--help' || "${1:-}" == '-h' ]]; then
    usage
    exit 0
fi

for command_name in php npm stripe; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Missing required command: ${command_name}" >&2
        exit 1
    fi
done

if [[ ! -f "${project_dir}/vendor/autoload.php" ]]; then
    echo 'Composer dependencies are missing. Run: composer install' >&2
    exit 1
fi

if [[ ! -d "${project_dir}/node_modules" ]]; then
    echo 'Node dependencies are missing. Run: npm install' >&2
    exit 1
fi

cleanup() {
    if [[ "$cleaned_up" == true ]]; then
        return
    fi

    cleaned_up=true
    trap - EXIT INT TERM

    for process_id in "${process_ids[@]:-}"; do
        pkill -TERM -P "$process_id" 2>/dev/null || true
        kill -TERM "$process_id" 2>/dev/null || true
    done

    for process_id in "${process_ids[@]:-}"; do
        wait "$process_id" 2>/dev/null || true
    done

    if [[ -n "$runtime_dir" && -d "$runtime_dir" ]]; then
        find "$runtime_dir" -maxdepth 1 -type f -delete
        rmdir "$runtime_dir" 2>/dev/null || true
    fi
}

trap cleanup EXIT
trap 'exit 130' INT TERM

cd "$project_dir"

php artisan config:clear --quiet

stripe_secret="$(
    php -r '
        require "vendor/autoload.php";
        $app = require "bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $secret = config("services.stripe.secret");
        if (! is_string($secret) || $secret === "") {
            fwrite(STDERR, "STRIPE_SECRET is not configured.\n");
            exit(1);
        }
        echo $secret;
    '
)"

runtime_dir="$(mktemp -d "${TMPDIR:-/tmp}/cativa-dev.XXXXXX")"
chmod 700 "$runtime_dir"
connect_log="${runtime_dir}/stripe-connect.log"
checkout_log="${runtime_dir}/stripe-checkout.log"

start_logged_process() {
    local label="$1"
    local log_file="$2"
    shift 2

    (
        "$@" 2>&1 | while IFS= read -r line; do
            printf '%s\n' "$line" >> "$log_file"

            if [[ "$line" != *'webhook signing secret'* ]]; then
                printf '[%s] %s\n' "$label" "$line"
            fi
        done
    ) &

    process_ids+=("$!")
}

wait_for_webhook_secret() {
    local label="$1"
    local log_file="$2"
    local process_id="$3"
    local secret=''

    for ((attempt = 0; attempt < 150; attempt++)); do
        secret="$(sed -nE 's/.*(whsec_[A-Za-z0-9]+).*/\1/p' "$log_file" 2>/dev/null | head -n 1)"

        if [[ -n "$secret" ]]; then
            printf '%s' "$secret"
            return 0
        fi

        if ! kill -0 "$process_id" 2>/dev/null; then
            echo "${label} listener stopped before it became ready." >&2
            sed -E 's/(whsec_|sk_(test|live)_)[A-Za-z0-9]+/[REDACTED]/g' "$log_file" >&2 || true
            return 1
        fi

        sleep 0.1
    done

    echo "Timed out while starting the ${label} listener." >&2
    return 1
}

start_logged_process \
    'stripe-connect' \
    "$connect_log" \
    env STRIPE_API_KEY="$stripe_secret" stripe listen \
        --all-thin \
        --events-from @accounts \
        --forward-to "${base_url}/api/webhooks/stripe/connect"
connect_process_id="${process_ids[-1]}"

start_logged_process \
    'stripe-checkout' \
    "$checkout_log" \
    env STRIPE_API_KEY="$stripe_secret" stripe listen \
        --events checkout.session.completed,checkout.session.async_payment_succeeded,checkout.session.async_payment_failed,checkout.session.expired \
        --forward-to "${base_url}/api/webhooks/stripe/checkout"
checkout_process_id="${process_ids[-1]}"

connect_webhook_secret="$(wait_for_webhook_secret 'Connect' "$connect_log" "$connect_process_id")"
checkout_webhook_secret="$(wait_for_webhook_secret 'Checkout' "$checkout_log" "$checkout_process_id")"

start_logged_process \
    'laravel' \
    "${runtime_dir}/laravel.log" \
    env \
        STRIPE_CONNECT_WEBHOOK_SECRET="$connect_webhook_secret" \
        STRIPE_CHECKOUT_WEBHOOK_SECRET="$checkout_webhook_secret" \
        php artisan serve --host="$server_host" --port="$server_port"

start_logged_process \
    'vite' \
    "${runtime_dir}/vite.log" \
    npm run dev

echo
echo "Cativa is running at ${base_url}"
echo 'Stripe Connect and Checkout webhooks are forwarding locally.'
echo 'Press Ctrl+C to stop all processes.'
echo

set +e
wait -n "${process_ids[@]}"
exit_code=$?
set -e

echo 'A development process stopped; shutting down the remaining processes.' >&2
exit "$exit_code"
