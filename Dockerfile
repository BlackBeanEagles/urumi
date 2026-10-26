FROM node:24-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY src ./src
COPY web ./web
RUN npm run build:web && npm prune --omit=dev
USER node
EXPOSE 3000
CMD ["node","src/main.js"]
