#!/usr/bin/env bash
#
# Install the XenForo add-on development reference as a Claude Code skill.
#
# This copies the reference (SKILL.md + xenforo.md + docs/ + cheatsheets/ +
# examples/) into a self-contained skill directory that Claude Code auto-loads.
#
# Usage:
#   ./install.sh                 # install for the current user (~/.claude)
#   ./install.sh --user          # same as above (explicit)
#   ./install.sh --project .     # install into ./.claude of a project
#   ./install.sh --project /path/to/your-xenforo-addon
#
# After installing, start Claude Code and ask it to build a XenForo add-on —
# it will discover the "xenforo-addon-dev" skill automatically.
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SKILL_NAME="xenforo-addon-dev"

SCOPE="user"
PROJECT_DIR="."

usage() {
  grep '^#' "$0" | sed 's/^# \{0,1\}//'
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --user)    SCOPE="user"; shift ;;
    --project) SCOPE="project"; PROJECT_DIR="${2:-.}"; shift 2 ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown argument: $1" >&2; usage; exit 1 ;;
  esac
done

if [[ "$SCOPE" == "user" ]]; then
  DEST="${HOME}/.claude/skills/${SKILL_NAME}"
else
  if [[ ! -d "$PROJECT_DIR" ]]; then
    echo "Project directory does not exist: $PROJECT_DIR" >&2
    exit 1
  fi
  DEST="$(cd "$PROJECT_DIR" && pwd)/.claude/skills/${SKILL_NAME}"
fi

echo "Installing XenForo reference skill -> $DEST"
rm -rf "$DEST"
mkdir -p "$DEST"

cp    "$SCRIPT_DIR/skills/${SKILL_NAME}/SKILL.md" "$DEST/SKILL.md"
cp    "$SCRIPT_DIR/xenforo.md"                    "$DEST/xenforo.md"
cp -R "$SCRIPT_DIR/docs"                          "$DEST/docs"
cp -R "$SCRIPT_DIR/cheatsheets"                   "$DEST/cheatsheets"
cp -R "$SCRIPT_DIR/examples"                      "$DEST/examples"

echo "Done. Files installed:"
echo "  $DEST/SKILL.md"
echo "  $DEST/xenforo.md"
echo "  $DEST/docs/         ($(find "$DEST/docs" -name '*.md' | wc -l | tr -d ' ') files)"
echo "  $DEST/cheatsheets/  ($(find "$DEST/cheatsheets" -name '*.md' | wc -l | tr -d ' ') files)"
echo "  $DEST/examples/"
echo
echo "Restart Claude Code (or run /context) so it picks up the new skill."
