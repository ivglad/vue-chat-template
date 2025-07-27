# Этап сборки
FROM node:22-alpine AS builder

WORKDIR /build

RUN npm install -g npm@latest

COPY frontend/package*.json ./

RUN npm install --no-audit --no-fund && npm cache clean --force

FROM node:22-alpine

RUN apk add --no-cache --virtual .build-deps tini curl

RUN npm install -g npm@latest

WORKDIR /var/www/frontend
RUN mkdir -p /var/www/frontend && \
    chown -R node:node /var/www/frontend

COPY --from=builder --chown=node:node /build/node_modules ./node_modules
COPY --chown=node:node frontend/package*.json ./

USER node

HEALTHCHECK --interval=10s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:3000 || exit 1

ENTRYPOINT ["/sbin/tini", "--"]

CMD ["npm", "run", "dev"]