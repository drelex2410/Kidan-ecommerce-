#!/usr/bin/env bash
set -euo pipefail

PLIST_DST="$HOME/Library/LaunchAgents/com.kidan.local-staging.plist"
DOMAIN="gui/$(id -u)"
LABEL="com.kidan.local-staging"

launchctl bootout "$DOMAIN/$LABEL" >/dev/null 2>&1 || true
launchctl disable "$DOMAIN/$LABEL" >/dev/null 2>&1 || true
rm -f "$PLIST_DST"

echo "Stopped and removed $LABEL"
