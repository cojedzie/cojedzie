variable "REGISTRIES" {
  default = "docker.io"
}

# The first tag is the main one.
variable "TAGS" {
  default = "latest"
}

variable "COJEDZIE_VERSION" {
  default = "dev"
}

variable "COJEDZIE_REVISION" {
  default = "dev"
}

variable "GHA_CACHE" {
  default = "false"
}

variable "SENTRY_AUTH_TOKEN" {
  default = ""
}

# <registry>/cojedzie/<image>:<tag><suffix> for every registry, tag and suffix.
function "tags" {
  params = [image, suffixes]
  result = [
    for combination in setproduct(split(",", REGISTRIES), split(",", TAGS), suffixes) :
    "${combination[0]}/cojedzie/${image}:${combination[1]}${combination[2]}"
  ]
}

function "cache_from" {
  params = [name]
  result = GHA_CACHE == "true" ? ["type=gha,scope=${name}"] : []
}

# A failed cache export only makes the next build slower, it does not fail this one.
function "cache_to" {
  params = [name]
  result = GHA_CACHE == "true" ? ["type=gha,mode=max,scope=${name},ignore-error=true"] : []
}

target "_common" {
  args = {
    COJEDZIE_VERSION  = COJEDZIE_VERSION
    COJEDZIE_REVISION = COJEDZIE_REVISION
  }
}

# Not published, only the base of the worker image.
target "api-cli" {
  inherits   = ["_common"]
  context    = "./api"
  dockerfile = "Dockerfile"
  target     = "cli"
  cache-from = cache_from("api-cli")
  cache-to   = cache_to("api-cli")
}

target "api" {
  inherits   = ["_common"]
  context    = "./api"
  dockerfile = "Dockerfile"
  target     = "rr"
  tags       = tags("api", ["", "-rr"])
  cache-from = cache_from("api")
  cache-to   = cache_to("api")
}

target "api-fpm" {
  inherits   = ["_common"]
  context    = "./api"
  dockerfile = "Dockerfile"
  target     = "fpm"
  tags       = tags("api", ["-fpm"])
  cache-from = cache_from("api-fpm")
  cache-to   = cache_to("api-fpm")
}

target "standalone" {
  inherits   = ["_common"]
  context    = "./standalone"
  contexts   = { api = "target:api" }
  tags       = tags("standalone", ["", "-rr"])
  cache-from = cache_from("standalone")
  cache-to   = cache_to("standalone")
}

target "worker" {
  inherits   = ["_common"]
  context    = "./worker"
  contexts   = { api = "target:api-cli" }
  tags       = tags("worker", [""])
  cache-from = cache_from("worker")
  cache-to   = cache_to("worker")
}

target "front" {
  inherits   = ["_common"]
  context    = "./front"
  dockerfile = "Dockerfile"
  target     = "prod"
  tags       = tags("front", [""])
  cache-from = cache_from("front")
  cache-to   = cache_to("front")
  # Only passed when set, the build stage skips the Sentry upload otherwise.
  secret     = SENTRY_AUTH_TOKEN != "" ? ["id=sentry-auth-token,env=SENTRY_AUTH_TOKEN"] : []
}

group "ci" {
  targets = ["api", "worker", "front"]
}

group "release" {
  targets = ["api", "api-fpm", "standalone", "worker", "front"]
}

group "default" {
  targets = ["release"]
}
