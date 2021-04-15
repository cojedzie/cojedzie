#!/bin/bash

TAGS=$*
BUILD=$(dirname $0)
ROOT=$BUILD/..

REGISTRY="docker.io"
TAGS=()
DRY=0
PUSH=0

BUILT_TAGS=()

export DOCKER_BUILDKIT=1

usage () {
  echo "usage: $0 [-h|--help] [-d|--dry] [-r|--registry registry] [-t|--tag tag] [-p|--push] -- images...";
}

run () {
  if [[ $DRY == 1 ]]; then
    echo "$@"
  else
    "$@"
  fi
}

# usage: build [-d|--default] [-v|--variant variant] <image> <context>
build () {
  ARGS=()
  IS_DEFAULT=0
  SUFFIX=""
  VARIANT=""

  options=$(getopt -l "default,variant:" -o "dv:" -- "$@")
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
    ARGS+=("-f" "$BUILD/$IMAGE/Dockerfile")
  else
    ARGS+=("-f" "$BUILD/$IMAGE/$VARIANT.Dockerfile")
    SUFFIX="-$VARIANT"
  fi

  for TAG in "${TAGS[@]}"; do
    ARGS+=("-t" "$REGISTRY/cojedzie/$IMAGE:$TAG$SUFFIX")
    BUILT_TAGS+=("$REGISTRY/cojedzie/$IMAGE:$TAG$SUFFIX")

    if [[ $IS_DEFAULT == 1 ]]; then
      ARGS+=("-t" "$REGISTRY/cojedzie/$IMAGE:$TAG")
      BUILT_TAGS+=("$REGISTRY/cojedzie/$IMAGE:$TAG")
    fi
  done

  run docker build $CONTEXT "${ARGS[@]}" "$@"
}

options=$(getopt -l "help,dry,registry:,tag:,push" -o "hdr:t:p" -- "$@")
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
    -r|--registry)
      REGISTRY="$2"
      shift 2
      ;;
    -d|--dry)
      DRY=1
      shift
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
  set -- api standalone worker front
fi

while [ $# -gt 0 ]
do
  case "$1" in
    api)
      build api $ROOT/api/ --variant rr --default
      build api $ROOT/api/ --variant fpm
      ;;
    standalone)
      build standalone $ROOT/api/ --variant rr --default
      ;;
    worker)
      build worker $ROOT/api/
      ;;
    front)
      build front $ROOT/front/
      ;;
    *)
      >&2 echo "$1 is not a valid image to build"
  esac
  shift
done

if [ $PUSH -eq 1 ]; then
  for TAG in "${BUILT_TAGS[@]}"; do
    docker push $TAG
  done
else
  echo "Created tags:"
  printf " - %s\n" "${BUILT_TAGS[@]}"
fi
