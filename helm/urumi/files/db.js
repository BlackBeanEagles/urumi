import { DatabaseSync } from 'node:sqlite';
import crypto from 'node:crypto';
import { hashKey, issueKey } from './core.js';

const db = new DatabaseSync(process.env.DB_PATH || './urumi.db');
db.exec('PRAGMA journal_mode = WAL');
db.exec(`
CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, username TEXT UNIQUE NOT NULL, role TEXT NOT NULL CHECK(role IN ('admin','member')), password_hash TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS api_keys (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id), key_hash TEXT UNIQUE NOT NULL, label TEXT NOT NULL, revoked_at TEXT);
CREATE TABLE IF NOT EXISTS servers (id INTEGER PRIMARY KEY, name TEXT UNIQUE NOT NULL, url TEXT NOT NULL, credential TEXT, enabled INTEGER NOT NULL DEFAULT 1, status TEXT NOT NULL DEFAULT 'Unreachable');
CREATE TABLE IF NOT EXISTS audit (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL, server TEXT NOT NULL, tool TEXT NOT NULL, at TEXT NOT NULL, duration_ms INTEGER NOT NULL, outcome TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS chat_approvals (id TEXT PRIMARY KEY, user_id INTEGER NOT NULL, payload TEXT NOT NULL, expires_at INTEGER NOT NULL);
`);
if (!db.prepare('PRAGMA table_info(audit)').all().some(column => column.name === 'request_id')) {
  db.exec('ALTER TABLE audit ADD COLUMN request_id TEXT');
}
db.exec('CREATE UNIQUE INDEX IF NOT EXISTS audit_request_id ON audit(request_id) WHERE request_id IS NOT NULL');

export function createUser(username, password, role = 'member') {
  const salt = cryptoSalt();
  const digest = passwordDigest(password, salt);
  return Number(db.prepare('INSERT INTO users (username,role,password_hash) VALUES (?,?,?)').run(username, role, `${salt}:${digest}`).lastInsertRowid);
}
function cryptoSalt() { return crypto.randomBytes(16).toString('hex'); }
function passwordDigest(password, salt) { return crypto.scryptSync(password, salt, 64).toString('hex'); }
export function authenticatePassword(username, password) {
  const user = db.prepare('SELECT * FROM users WHERE username=?').get(username);
  if (!user) return null;
  const [salt, digest] = user.password_hash.split(':');
  const actual = Buffer.from(passwordDigest(password, salt),'hex');
  const expected = Buffer.from(digest,'hex');
  return actual.length === expected.length && crypto.timingSafeEqual(actual,expected) ? user : null;
}
export function authenticateKey(key) {
  if (!key?.startsWith('uru_')) return null;
  return db.prepare(`SELECT u.id,u.username,u.role,k.id AS key_id FROM api_keys k JOIN users u ON u.id=k.user_id WHERE k.key_hash=? AND k.revoked_at IS NULL`).get(hashKey(key));
}
export function createKey(userId, label) {
  const key = issueKey();
  const id = Number(db.prepare('INSERT INTO api_keys (user_id,key_hash,label) VALUES (?,?,?)').run(userId, hashKey(key), label).lastInsertRowid);
  return { id, key };
}
export const listKeys = userId => db.prepare('SELECT id,label,revoked_at FROM api_keys WHERE user_id=?').all(userId);
export const revokeKey = (id, userId) => db.prepare("UPDATE api_keys SET revoked_at=datetime('now') WHERE id=? AND user_id=? AND revoked_at IS NULL").run(id, userId).changes;
export const revokeKeyAsAdmin = id => db.prepare("UPDATE api_keys SET revoked_at=datetime('now') WHERE id=? AND revoked_at IS NULL").run(id).changes;
export const listAllKeys = () => db.prepare('SELECT k.id,k.label,k.revoked_at,u.username FROM api_keys k JOIN users u ON u.id=k.user_id ORDER BY k.id DESC').all();
export const createKeyForUser = (userId,label) => {
  const exists=db.prepare('SELECT id FROM users WHERE id=?').get(userId);
  if(!exists) throw new Error('User not found');
  return createKey(userId,label);
};
export const users = () => db.prepare('SELECT id,username,role FROM users').all();
export const servers = () => db.prepare('SELECT id,name,url,enabled,status FROM servers ORDER BY id').all();
export const server = name => db.prepare('SELECT * FROM servers WHERE name=?').get(name);
export const saveServer = (name,url,credential,enabled=1) => db.prepare('INSERT INTO servers (name,url,credential,enabled) VALUES (?,?,?,?) ON CONFLICT(name) DO UPDATE SET url=excluded.url,credential=excluded.credential,enabled=excluded.enabled').run(name,url,credential,enabled);
export const setEnabled = (name,enabled) => db.prepare('UPDATE servers SET enabled=? WHERE name=?').run(enabled,name).changes;
export const deleteServer = name => db.prepare('DELETE FROM servers WHERE name=?').run(name).changes;
export const setStatus = (name,status) => db.prepare('UPDATE servers SET status=? WHERE name=?').run(status,name);
export const recordCall = (userId,server,tool,duration,outcome,requestId) => db.prepare("INSERT INTO audit (user_id,server,tool,at,duration_ms,outcome,request_id) VALUES (?,?,?,?,?,?,?)").run(userId,server,tool,new Date().toISOString(),duration,outcome,requestId).lastInsertRowid;
export const audit = () => db.prepare('SELECT a.*,u.username FROM audit a JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 500').all();
export const auditByRequestId = requestId => db.prepare('SELECT a.*,u.username FROM audit a JOIN users u ON u.id=a.user_id WHERE a.request_id=?').get(requestId);
export const userCount = () => db.prepare('SELECT COUNT(*) AS n FROM users').get().n;
export function createApproval(userId,payload) {
  const id=crypto.randomUUID();
  db.prepare('INSERT INTO chat_approvals (id,user_id,payload,expires_at) VALUES (?,?,?,?)').run(id,userId,JSON.stringify(payload),Date.now()+10*60*1000);
  return id;
}
export function consumeApproval(userId,id) {
  const row=db.prepare('DELETE FROM chat_approvals WHERE id=? AND user_id=? RETURNING payload,expires_at').get(id,userId);
  if(!row || row.expires_at<Date.now()) return null;
  return JSON.parse(row.payload);
}
