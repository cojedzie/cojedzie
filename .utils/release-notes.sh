#!/bin/bash

#
# Copyright (C) 2026 Kacper Donat
#
# @author Kacper Donat <kacper@kadet.net>
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published
# by the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
#

# usage: release-notes.sh <from> <to>
# Prints Markdown release notes from Conventional Commits between two revisions.

FROM=$1
TO=${2:-HEAD}

if [[ -z "$FROM" ]]; then
  echo "usage: $0 <from> [<to>]" >&2
  exit 1
fi

section () {
  TITLE=$1
  TYPE=$2

  # subject: type(scope)!: description
  COMMITS=$(git log --no-merges --format='%h%x09%s' "$FROM..$TO" | grep -P "^\w+\t$TYPE(\([^)]+\))?!?: ")
  [[ -z "$COMMITS" ]] && return

  echo "### $TITLE"
  echo
  while IFS=$'\t' read -r HASH SUBJECT; do
    SCOPE=$(sed -nE 's/^[a-z]+\(([^)]+)\).*/\1/p' <<< "$SUBJECT")
    DESCRIPTION=${SUBJECT#*: }
    echo "- ${SCOPE:+**$SCOPE:** }$DESCRIPTION ($HASH)"
  done <<< "$COMMITS"
  echo
}

section "Security" "security"
section "Features" "feat"
section "Bug fixes" "fix"
section "Performance" "perf"

echo "**Full changelog:** https://github.com/cojedzie/cojedzie/compare/$FROM...$TO"
