FROM node:24 AS dev

WORKDIR /app

CMD ["node", "build/server/server.js"]

EXPOSE 3000 3001

FROM node:24 AS build

WORKDIR /app
COPY package.json package-lock.json ./

# install dependencies, npm's download cache stays out of the layer
RUN --mount=type=cache,target=/root/.npm npm ci --no-audit --no-fund

COPY . .

ARG COJEDZIE_REVISION
ARG COJEDZIE_VERSION
ENV COJEDZIE_REVISION="${COJEDZIE_REVISION}" COJEDZIE_VERSION="${COJEDZIE_VERSION}"

# build stuff
RUN --mount=type=secret,id=sentry-auth-token npm run build:app
RUN npm run build:server

# server dependencies step
FROM node:24 AS prod-dependencies

WORKDIR /app

COPY package.json package-lock.json ./

# install dependencies, npm's download cache stays out of the layer
RUN --mount=type=cache,target=/root/.npm npm ci --omit=dev --no-audit --no-fund

FROM node:24-slim AS prod

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

CMD ["node", "build/server/server.js"]
