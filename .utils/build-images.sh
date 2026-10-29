#!/bin/bash

TAGS=$*
BUILD=$(dirname $0)/../build
ROOT=$BUILD/..

BUILD_REGISTRY="docker.io"
# images are copied to every publish registry, the first one is used to report digests
PUBLISH_REGISTRIES=()
TAGS=()
DRY=0
PUSH=0
BUILD_BASE=1
DEFAULT_ONLY=0
DIGESTS=""
REST=()

BUILT_TAGS=()
# image name => its main tag (default variant or the only one), used to report digests
declare -A MAIN_TAGS

export DOCKER_BUILDKIT=1

# release name used by the images and by Sentry
VERSION=$(git describe --tags)

usage () {
  echo "usage: $0 [-h|--help] [-d|--dry] [--no-base|-B] [--default-only|-D] [-p|--push] [-r|--registry registry] [--build-registry registry] [--publish-registry registry]... [-t|--tag tag] [--cache-to cache] [--cache-from cache] [--digests file] -- images...";
}

run () {
  echo "$@"
  if [[ $DRY == 1 ]]; then
    echo "$@"
  else
    "$@"
  fi
}

# usage: build [-d|--default] [-v|--variant variant] [-R|--no-register] <image> <context>
build () {
  ARGS=()
  IS_DEFAULT=0
  SUFFIX=""
  VARIANT=""
  REGISTER=1
  FORCE_PUSH=1

  options=$(getopt -l "default,variant:,no-register,cache-from:,cache-to:,push" -o "dv:Rp" -- "$@")
  eval set -- "$options"

  while true;
  do
    case "$1" in
      -d|--default)
        IS_DEFAULT=1
        shift
        ;;
      -v|--variant)
        VARIANT="$2"
        shift 2
        ;;
      -R|--no-register)
        REGISTER=0
        shift
        ;;
      -p|--push)
        FORCE_PUSH=1
        shift
        ;;
      --cache-from|--cache-to)
        ARGS+=("$1" "$2")
        shift 2
        ;;
      --)
        shift
        break
        ;;
      *)
        echo "build: unknown option $1"
        exit 1
    esac
  done

  IMAGE=$1
  CONTEXT=$2
  shift 2;

  # check for variant
  if [[ -z "$VARIANT" ]]; then
    ARGS+=("--file" "$BUILD/$IMAGE/Dockerfile")
  else
    ARGS+=("--file" "$BUILD/$IMAGE/$VARIANT.Dockerfile")
    SUFFIX="-$VARIANT"
  fi

  for TAG in "${TAGS[@]}"; do
    ARGS+=("--tag" "$BUILD_REGISTRY/cojedzie/$IMAGE:$TAG$SUFFIX")
    [[ $REGISTER -eq 1 ]] && BUILT_TAGS+=("$BUILD_REGISTRY/cojedzie/$IMAGE:$TAG$SUFFIX")

    if [[ $IS_DEFAULT == 1 ]]; then
      ARGS+=("--tag" "$BUILD_REGISTRY/cojedzie/$IMAGE:$TAG")
      [[ $REGISTER -eq 1 ]] && BUILT_TAGS+=("$BUILD_REGISTRY/cojedzie/$IMAGE:$TAG")
    fi
  done

  if [[ $REGISTER -eq 1 ]] && { [[ $IS_DEFAULT -eq 1 ]] || [[ -z "$VARIANT" ]]; }; then
    MAIN_TAGS[$IMAGE]="${TAGS[0]}"
  fi

  if [[ ! -z "$SENTRY_AUTH_TOKEN" ]]; then
    ARGS+=("--secret" "id=sentry-auth-token,env=SENTRY_AUTH_TOKEN")
  fi

  if { [[ $PUSH -eq 1 ]] && [[ $REGISTER -eq 1 ]]; } || [[ "$BUILD_REGISTRY" != "$PUBLISH_REGISTRY" ]]; then
    ARGS+=("--push")
  fi

  # GitHub Actions cache keeps one entry per scope, so every image needs its own
  CACHE_ARGS=()
  for ARG in "${REST[@]}"; do
    if [[ "$ARG" == type=gha* ]] && [[ "$ARG" != *scope=* ]]; then
      ARG="$ARG,scope=$IMAGE$SUFFIX"
    fi
    CACHE_ARGS+=("$ARG")
  done

  echo "Building $IMAGE $VARIANT"
  run docker buildx build \
    --build-arg "BASE_VERSION=${TAGS[0]}" \
    --build-arg "REGISTRY=$BUILD_REGISTRY" \
    --build-arg "COJEDZIE_VERSION=$VERSION" \
    --build-arg "COJEDZIE_REVISION=$(git rev-parse HEAD)" \
    "${CACHE_ARGS[@]}" "${ARGS[@]}" "$@" "$CONTEXT"
}

options=$(getopt -l "help,dry,registry:,tag:,push,no-base,default-only,digests:,cache-from:,cache-to:,build-registry:,publish-registry:" -o "hdr:t:pBD" -- "$@")
eval set -- "$options"

while true;
do
  case "$1" in
    -h|--help)
      usage
      exit 0
      ;;
    -t|--tag)
      TAGS+=("$2")
      shift 2
      ;;
    -p|--push)
      PUSH=1
      shift
      ;;
    -B|--no-base)
      BUILD_BASE=0
      shift
      ;;
    -D|--default-only)
      DEFAULT_ONLY=1
      shift
      ;;
    --digests)
      DIGESTS="$2"
      shift 2
      ;;
    -r|--registry)
      BUILD_REGISTRY="$2"
      PUBLISH_REGISTRIES=("$2")
      shift 2
      ;;
    --build-registry)
      BUILD_REGISTRY="$2"
      shift 2
      ;;
    --publish-registry)
      PUBLISH_REGISTRIES+=("$2")
      shift 2
      ;;
    -d|--dry)
      DRY=1
      shift
      ;;
    --cache-to|--cache-from)
      REST+=("$1" "$2")
      shift 2
      ;;
    --)
      shift
      break;
  esac
done

if [ ${#PUBLISH_REGISTRIES[@]} -eq 0 ]; then
  PUBLISH_REGISTRIES=("$BUILD_REGISTRY")
fi
PUBLISH_REGISTRY=${PUBLISH_REGISTRIES[0]}

# set default tags if user have not provided any
if [ ${#TAGS[@]} -eq 0 ]; then
    TAGS=("latest")
fi

if [ $# -eq 0 ]; then
  set -- api standalone worker front
fi

if [ $BUILD_BASE -eq 1 ]; then
  build --no-register base $ROOT/api/ || exit 1
  [[ $DEFAULT_ONLY -eq 0 ]] && { build --no-register --variant fpm base $ROOT/api/ || exit 1; }
  build --no-register --variant cli base $ROOT/api/ || exit 1
fi

while [ $# -gt 0 ]
do
  case "$1" in
    api)
      build api $BUILD/api/ --variant rr --default || exit 1
      [[ $DEFAULT_ONLY -eq 0 ]] && { build api $BUILD/api/ --variant fpm || exit 1; }
      ;;
    standalone)
      build standalone $BUILD/standalone/ --variant rr --default || exit 1
      ;;
    worker)
      build worker $BUILD/worker/ || exit 1
      ;;
    front)
      build front $ROOT/front/ || exit 1
      ;;
    *)
      >&2 echo "$1 is not a valid image to build"
      exit 1
  esac
  shift
done

if [[ "${PUBLISH_REGISTRIES[*]}" != "$BUILD_REGISTRY" ]] && [[ $PUSH -eq 1 ]]; then
  for REGISTRY in "${PUBLISH_REGISTRIES[@]}"; do
    [[ "$REGISTRY" == "$BUILD_REGISTRY" ]] && continue

    for BUILT_TAG in "${BUILT_TAGS[@]}"; do
      PUBLISH_TAG=${BUILT_TAG//$BUILD_REGISTRY/$REGISTRY}
      # images built with buildx live only in the build registry, so copy them between registries
      run docker buildx imagetools create --tag "$PUBLISH_TAG" "$BUILT_TAG" || exit 1
    done
  done
else
  echo "Created tags:"
  printf " - %s\n" "${BUILT_TAGS[@]}"
fi

# write <image>=<tag>@<digest> of every pushed image, so deployments can pin the exact build,
# and version=<version> they were built as, so deployments can be reported to Sentry
if [[ -n "$DIGESTS" ]] && [[ $PUSH -eq 1 ]] && [[ $DRY -eq 0 ]]; then
  echo "version=$VERSION" | tee -a "$DIGESTS"
  for IMAGE in "${!MAIN_TAGS[@]}"; do
    TAG=${MAIN_TAGS[$IMAGE]}
    DIGEST=$(docker buildx imagetools inspect "$PUBLISH_REGISTRY/cojedzie/$IMAGE:$TAG" --format '{{ .Manifest.Digest }}') || exit 1
    echo "$IMAGE=$TAG@$DIGEST" | tee -a "$DIGESTS"
  done
fi
