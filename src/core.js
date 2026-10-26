import crypto from 'node:crypto';

export const hashKey = key => crypto.createHash('sha256').update(key).digest('hex');
export const issueKey = () => `uru_${crypto.randomBytes(32).toString('base64url')}`;
export const safeName = value => value.toLowerCase().replace(/[^a-z0-9_-]/g, '_');
export const publicToolName = (server, tool) => `${safeName(server)}__${tool}`;
export const splitToolName = value => {
  const i = value.indexOf('__');
  if (i < 1) throw new Error('Invalid tool name');
  return [value.slice(0, i), value.slice(i + 2)];
};
export function assertInternalUrl(input) {
  const url = new URL(input);
  if (url.protocol !== 'http:') throw new Error('Upstream URL must use cluster-internal HTTP');
  if (!/^[a-z0-9-]+(\.[a-z0-9-]+)*\.svc\.cluster\.local$/i.test(url.hostname)) {
    throw new Error('Upstream must be a Kubernetes service DNS name');
  }
  return url.toString();
}
