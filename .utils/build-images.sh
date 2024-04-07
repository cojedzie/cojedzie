#!/bin/bash

TAGS=$*
BUILD=$(dirname $0)/../build
ROOT=$BUILD/..

BUILD_REGISTRY="docker.io"
PUBLISH_REGISTRY="docker.io"
TAGS=()
DRY=0
PUSH=0
BUILD_BASE=1
REST=()

BUILT_TAGS=()

export DOCKER_BUILDKIT=1

usage () {
  echo "usage: $0 [-h|--help] [-d|--dry] [--no-base|-B] [-p|--push] [-r|--registry registry] [--temp-registry registry] [-t|--tag tag] [--cache-to cache] [--cache-from cache] -- images...";
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

  if [[ ! -z "$SENTRY_AUTH_TOKEN" ]]; then
    ARGS+=("--secret" "id=sentry-auth-token,env=SENTRY_AUTH_TOKEN")
  fi

  if { [[ $PUSH -eq 1 ]] && [[ $REGISTER -eq 1 ]]; } || [[ "$BUILD_REGISTRY" != "$PUBLISH_REGISTRY" ]]; then
    ARGS+=("--push")
  fi

  echo "Building $IMAGE $VARIANT"
  run docker buildx build \
    --build-arg "BASE_VERSION=${TAGS[0]}" \
    --build-arg "REGISTRY=$BUILD_REGISTRY" \
    --build-arg "COJEDZIE_VERSION=$(git describe --tags)" \
    --build-arg "COJEDZIE_REVISION=$(git rev-parse HEAD)" \
    "${REST[@]}" "${ARGS[@]}" "$@" "$CONTEXT"
}

options=$(getopt -l "help,dry,registry:,tag:,push,no-base,cache-from:,cache-to:,build-registry:,publish-registry:" -o "hdr:t:pB" -- "$@")
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
    -r|--registry)
      BUILD_REGISTRY="$2"
      PUBLISH_REGISTRY="$2"
      shift 2
      ;;
    --build-registry)
      BUILD_REGISTRY="$2"
      shift 2
      ;;
    --publish-registry)
      PUBLISH_REGISTRY="$2"
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

# set default tags if user have not provided any
if [ ${#TAGS[@]} -eq 0 ]; then
    TAGS=("latest")
fi

if [ $# -eq 0 ]; then
  set -- api standalone worker front cron
fi

if [ $BUILD_BASE -eq 1 ]; then
  build --no-register base $ROOT/api/ || exit 1
  build --no-register --variant fpm base $ROOT/api/ || exit 1
  build --no-register --variant cli base $ROOT/api/ || exit 1
fi

while [ $# -gt 0 ]
do
  case "$1" in
    api)
      build api $BUILD/api/ --variant rr --default || exit 1
      build api $BUILD/api/ --variant fpm || exit 1
      ;;
    standalone)
      build standalone $BUILD/standalone/ --variant rr --default || exit 1
      ;;
    worker)
      build worker $BUILD/worker/ || exit 1
      ;;
    cron)
      build cron $BUILD/cron/ || exit 1
      ;;
    front)
      build front $ROOT/front/ || exit 1
      ;;
    *)
      >&2 echo "$1 is not a valid image to build"
  esac
  shift
done

if [[ "$BUILD_REGISTRY" != "$PUBLISH_REGISTRY" ]] && [[ $PUSH -eq 1 ]]; then
  for BUILT_TAG in "${BUILT_TAGS[@]}"; do
    PUBLISH_TAG=${BUILT_TAG//$BUILD_REGISTRY/$PUBLISH_REGISTRY}
    run docker tag "$BUILT_TAG" "$PUBLISH_TAG"
    run docker push "$BUILT_TAG"
  done
else
  echo "Created tags:"
  printf " - %s\n" "${BUILT_TAGS[@]}"
fi
