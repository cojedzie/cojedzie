FROM node:15.2.1 as build

WORKDIR /app
COPY . .

# install dependencies
RUN yarn install
RUN find resources/fontawesome -type f -name '*.tgz' | sed s/^/file:/ | xargs yarn add-no-save

# build stuff
RUN yarn run build:app
RUN yarn run build:server

# server dependencies step
FROM node:15.2.1 as prod-dependencies

WORKDIR /app

COPY . .

# install dependencies
RUN yarn install --production

FROM node:15.2.1-slim

LABEL maintainer="Kacper Donat <kacper@kadet.net>"

WORKDIR /app

COPY --from=build /app/package.json package.json
COPY --from=build /app/build/ build
COPY --from=build /app/resources/ resources
COPY --from=prod-dependencies /app/node_modules/ node_modules

EXPOSE 3000

CMD ["node", "build/server.js"]
