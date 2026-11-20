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

# usage: supported-line.sh
# Prints, as GITHUB_OUTPUT lines, the supported release line (ADR 0040): the line of the newest v* tag, that tag
# (also the newest release of the line) and the line's release branch.

set -euo pipefail

tag=$(git tag --list 'v*' --sort=-version:refname | head -n 1)
if [[ ! "$tag" =~ ^v([0-9]{4}\.[0-9]+)\.[0-9]+$ ]]; then
  echo "No release tag to tell the supported line from, newest is '$tag'" >&2
  exit 1
fi

echo "line=${BASH_REMATCH[1]}"
echo "tag=$tag"
echo "branch=release/${BASH_REMATCH[1]}"
