/**
 * token 存取。前后台各用各的 key，避免同一浏览器登录两边互相覆盖。
 */
const TOKEN_KEY = "rbac.index.token";

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken(): void {
  localStorage.removeItem(TOKEN_KEY);
}
