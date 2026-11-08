#!/usr/bin/env bash
# FILE:      tests/e2e/run.sh
# CREATED:   2026-11-08 08:34 +03
# TASK:      whmcs-e2e (Boss 2026-11-08 08:26: test all client-side events, save the tests for easy redo)
# AUTHOR:    M Alhassoun
# WHAT:      End-to-end test of the kwtSMS module on a throwaway WHMCS: install WHMCS from its zip, install the module
#            the way the release zip is built, configure it through the admin area, fire every client-side event
#            through real WHMCS flows, check each SMS log row, optionally upgrade from an older release first.
# WHY:       Unit tests cannot see WHMCS changes (9.1 broke the admin token); this reruns the whole flow in one command.
# USAGE:     tests/e2e/run.sh -h
# DEPENDS:   docker compose, python3, composer, unzip, a WHMCS zip and licence, a kwtSMS API login.
# CHANGELOG:
#   2026-11-08 08:34 +03  M Alhassoun  whmcs-e2e  first version
set -euo pipefail

usage() {
  cat <<'EOF'
Usage: tests/e2e/run.sh [-h] [--upgrade-from VERSION] [--keep] WHMCS_ZIP ENV_FILE

Runs the kwtSMS WHMCS end-to-end tests on a fresh WHMCS in Docker (http://localhost:8080).

  WHMCS_ZIP              WHMCS full release zip (root folder "whmcs/"), e.g. whmcs_v910_full.zip
  ENV_FILE               file with KEY=value lines (never printed):
                           WHMCS_LICENSE       WHMCS licence key (a dev licence bound to localhost works)
                           KWTSMS_USERNAME     kwtSMS API username
                           KWTSMS_PASSWORD     kwtSMS API password
                           KWTSMS_TEST_NUMBER  the only number allowed to receive (test=1 sends only)
  --upgrade-from VERSION install that GitHub release (e.g. 1.0.0) first, configure it, then upgrade to this
                         checkout and check the settings survived
  --keep                 leave the stack running afterwards (default: docker compose down -v)
  -h, --help             this help; does nothing else

Environment: WORK=<dir> (default: a new temp dir) holds WHMCS and the module build.
Every send runs in test mode (test=1) and a guard in the test build refuses any other recipient.
Each send holds 1 credit until the test message is deleted from the kwtSMS Sending Queue.
Exit code 0 = every case passed.
EOF
}

UPGRADE_FROM=""; KEEP=0; ARGS=()
while [ $# -gt 0 ]; do
  case "$1" in
    -h|--help) usage; exit 0 ;;
    --upgrade-from) UPGRADE_FROM="${2:?version}"; shift 2 ;;
    --keep) KEEP=1; shift ;;
    *) ARGS+=("$1"); shift ;;
  esac
done
[ ${#ARGS[@]} -eq 2 ] || { usage; exit 2; }
WHMCS_ZIP=$(readlink -f "${ARGS[0]}"); ENV_FILE=$(readlink -f "${ARGS[1]}")
HERE=$(cd "$(dirname "$0")" && pwd); REPO=$(cd "$HERE/../.." && pwd)
WORK=${WORK:-$(mktemp -d)}; mkdir -p "$WORK"; export WORK
for k in WHMCS_LICENSE KWTSMS_USERNAME KWTSMS_PASSWORD KWTSMS_TEST_NUMBER; do
  grep -qE "^$k=" "$ENV_FILE" || { echo "missing $k in $ENV_FILE"; exit 2; }
done
KWTSMS_TEST_NUMBER=$(grep -E '^KWTSMS_TEST_NUMBER=' "$ENV_FILE" | head -1 | cut -d= -f2- | tr -d '"'"'"' ')
export KWTSMS_TEST_NUMBER
DC=(docker compose -f "$HERE/compose.yml")
step() { echo "== $(date '+%H:%M:%S') $*"; }

cleanup() {
  [ "$KEEP" = 1 ] && return
  step "removing the stack"
  # files WHMCS wrote belong to www-data: hand them back to the host user so WORK can be deleted
  "${DC[@]}" exec -T web chown -R "$(id -u):$(id -g)" /var/www/html >/dev/null 2>&1 || true
  "${DC[@]}" down -v >/dev/null 2>&1 || true
}
trap cleanup EXIT

# Module build: same steps and excludes as .github/workflows/release.yml, plus the test-only send guard.
build_module() {  # $1 = source tree with modules/addons/kwtsms, $2 = output dir
  local src=$1 out=$2
  (cd "$src/modules/addons/kwtsms" && composer install -q --no-dev --optimize-autoloader --no-interaction)
  rm -rf "$out.tmp" && mkdir -p "$out.tmp"
  (cd "$src" && zip -qr "$out.tmp/m.zip" modules/addons/kwtsms/ \
     --exclude "*/vendor/*/examples/*" "*/.env.example" "*/.php-cs-fixer*" "*/vendor/*/CONTRIBUTING.md" \
               "*/vendor/*/SECURITY.md" "*/vendor/*/CHANGELOG.md" "*/vendor/*/test*" "*/vendor/*/tests*" \
               "*/vendor/*/Tests*" "*/vendor/bin/*" "*/phpstan.neon" "*/phpstan-bootstrap.php" "*/phpcs.xml" \
               "*/phpunit.xml" "*/tests/*" "*/.phpunit.cache/*" "*/composer.lock" "*/.git*" "*/.claude/*")
  (cd "$out.tmp" && unzip -q m.zip)
  python3 "$HERE/guard.py" "$out.tmp/modules/addons/kwtsms/lib/SmsHelper.php"
  mkdir -p "$out" && find "$out" -mindepth 1 -delete
  cp -a "$out.tmp/modules/addons/kwtsms/." "$out/" && rm -rf "$out.tmp"
}

"${DC[@]}" exec -T web chown -R "$(id -u):$(id -g)" /var/www/html >/dev/null 2>&1 || true   # a stack left by --keep
"${DC[@]}" down -v >/dev/null 2>&1 || true
if [ -d "$WORK/site" ] && docker image inspect kwtsms-whmcs-e2e-web >/dev/null 2>&1; then
  docker run --rm -v "$WORK:/w" --entrypoint chown kwtsms-whmcs-e2e-web -R "$(id -u):$(id -g)" /w
fi
step "WHMCS files"
rm -rf "$WORK/site" && mkdir -p "$WORK/site" && unzip -q "$WHMCS_ZIP" -d "$WORK/site"
[ -f "$WORK/site/whmcs/init.php" ] || { echo "zip has no whmcs/init.php"; exit 2; }

if [ -n "$UPGRADE_FROM" ]; then
  step "module $UPGRADE_FROM from its GitHub release"
  rm -rf "$WORK/old" && mkdir -p "$WORK/old"
  curl -sfL -o "$WORK/old/old.zip" "https://github.com/kwtsms/kwtsms-whmcs/releases/download/v$UPGRADE_FROM/kwtsms-whmcs-$UPGRADE_FROM.zip"
  (cd "$WORK/old" && unzip -q old.zip)
  mkdir -p "$WORK/module" && find "$WORK/module" -mindepth 1 -delete
  cp -a "$WORK/old/modules/addons/kwtsms/." "$WORK/module/"
  python3 "$HERE/guard.py" "$WORK/module/lib/SmsHelper.php"
else
  step "module from this checkout"
  rm -rf "$WORK/src" && mkdir -p "$WORK/src" && git -C "$REPO" archive HEAD modules/addons/kwtsms | tar -x -C "$WORK/src"
  build_module "$WORK/src" "$WORK/module"
fi

cp "$HERE/events.php" "$WORK/events.php" && chmod 644 "$WORK/events.php"
chmod -R a+rwX "$WORK/site/whmcs" "$WORK/module"   # web runs as www-data; files stay owned by the host user
step "stack up"
"${DC[@]}" up -d --build --quiet-pull >/dev/null 2>&1 || "${DC[@]}" up -d --build
"${DC[@]}" exec -T web rm -f /var/www/html/configuration.php

step "WHMCS install (CLI installer)"
(umask 077; python3 "$HERE/install_json.py" "$ENV_FILE" "$WORK/admin.pass" > "$WORK/install.json")
"${DC[@]}" exec -T -u www-data web php /var/www/html/install/bin/installer.php -i -n -c < "$WORK/install.json" > "$WORK/install.log" 2>&1 || true
grep -q "Install Completed Successfully" "$WORK/install.log" || { echo "WHMCS install failed, see $WORK/install.log"; exit 1; }
rm -f "$WORK/install.json"
"${DC[@]}" exec -T web rm -rf /var/www/html/install
# 9.1 sends every admin page to a getting-started wizard that ends in a MarketConnect login; mark it done.
"${DC[@]}" exec -T db mysql -uwhmcs -pe2epass whmcs -e "UPDATE tblconfiguration SET value='0' WHERE setting='MarketConnectOnboardingRequired'" 2>/dev/null

step "admin: activate, connect, configure"
if [ -n "$UPGRADE_FROM" ]; then python3 "$HERE/admin.py" setup_old "$WORK/admin.pass" "$ENV_FILE"
else python3 "$HERE/admin.py" setup "$WORK/admin.pass" "$ENV_FILE"; fi

if [ -n "$UPGRADE_FROM" ]; then
  python3 "$HERE/admin.py" snapshot "$WORK/admin.pass" "$ENV_FILE" > "$WORK/settings.before"
  step "upgrade to this checkout"
  rm -rf "$WORK/src" && mkdir -p "$WORK/src" && git -C "$REPO" archive HEAD modules/addons/kwtsms | tar -x -C "$WORK/src"
  build_module "$WORK/src" "$WORK/module"
  chmod -R a+rwX "$WORK/module"   # the web server (www-data) must read the swapped files
  E2E_EXPECT_VERSION=$(sed -nE "s/.*const VERSION = '([^']+)'.*/\1/p" "$WORK/module/lib/GatewayManager.php") \
    python3 "$HERE/admin.py" upgrade "$WORK/admin.pass" "$ENV_FILE"
  python3 "$HERE/admin.py" snapshot "$WORK/admin.pass" "$ENV_FILE" > "$WORK/settings.after"
  if diff -q "$WORK/settings.before" "$WORK/settings.after" >/dev/null; then echo "PASS upgrade: settings unchanged"
  else echo "FAIL upgrade: settings changed"; diff "$WORK/settings.before" "$WORK/settings.after" | sed 's/[0-9]\{8,\}/<number>/g'; exit 1; fi
fi

step "events"
python3 "$HERE/admin.py" events "$WORK/admin.pass" "$ENV_FILE"
