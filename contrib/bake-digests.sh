#!/usr/bin/env bash

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

# Usage: bake-digests.sh <metadata.json> <version> <main tag> <bake target>...
# Prints the version and "<image>=<main tag>@<digest>" for every target of a bake metadata file.
set -euo pipefail

if [[ $# -lt 4 ]]; then
  echo "Usage: $0 <metadata.json> <version> <main tag> <bake target>..." >&2
  exit 2
fi

metadata=$1
version=$2
tag=$3
shift 3

echo "version=$version"

for target in "$@"; do
  digest=$(jq -r --arg target "$target" '.[$target]["containerimage.digest"] // empty' "$metadata")
  if [[ -z "$digest" ]]; then
    echo "No containerimage.digest for target $target in $metadata" >&2
    exit 1
  fi
  image=$target
  echo "$image=$tag@$digest"
done
