FROM node:16 as build

WORKDIR /app
COPY package.json yarn.lock ./

# install dependencies
RUN yarn install --frozen-lockfile

COPY resources/fontawesome ./resources/fontawesome
RUN find resources/fontawesome -type f -name '*.tgz' | sed s/^/file:/ | xargs yarn add-no-save

COPY . .
# build stuff
RUN yarn run build:app
RUN yarn run build:server

# server dependencies step
FROM node:16 as prod-dependencies

WORKDIR /app

COPY package.json yarn.lock ./

# install dependencies
RUN yarn install --production --frozen-lockfile

FROM node:16-slim

LABEL maintainer="Kacper Donat <kacper@kadet.net>"

WORKDIR /app

COPY --from=build /app/package.json package.json
COPY --from=build /app/build/ build
COPY --from=build /app/resources/ resources
COPY --from=prod-dependencies /app/node_modules/ node_modules

EXPOSE 3000

ARG COJEDZIE_REVISION
ARG COJEDZIE_VERSION
ENV COJEDZIE_REVISION="${COJEDZIE_REVISION}" COJEDZIE_VERSION="${COJEDZIE_VERSION}"

CMD ["node", "build/server.js"]
