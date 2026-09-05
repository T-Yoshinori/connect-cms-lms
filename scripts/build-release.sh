#!/usr/bin/env bash
set -euo pipefail

version="${1:-0.9.0-beta.3}"
project_name="connect-cms-lms"
repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
build_root="$(mktemp -d)"
package_name="${project_name}-${version}"
package_root="${build_root}/${package_name}"

cleanup() {
    rm -rf "${build_root}"
}
trap cleanup EXIT

mkdir -p "${package_root}"
cp -R "${repo_root}/app" "${package_root}/"
cp -R "${repo_root}/database" "${package_root}/"
cp -R "${repo_root}/resources" "${package_root}/"
cp "${repo_root}/LICENSE" "${package_root}/LICENSE"
cp "${repo_root}/README.md" "${package_root}/README.md"

find "${package_root}" -type f ! -name MANIFEST.txt -print \
    | sed "s#${package_root}/##" \
    | LC_ALL=C sort > "${package_root}/MANIFEST.txt"

mkdir -p "${repo_root}/downloads"
(
    cd "${build_root}"
    zip -qr "${repo_root}/downloads/${package_name}.zip" "${package_name}"
)

echo "Created: ${repo_root}/downloads/${package_name}.zip"
