const DEFAULT_REDIRECT_PATH = '/';

function isAllowedRedirect(value: string): boolean {
  try {
    const target = new URL(value, window.location.origin);
    return target.origin === window.location.origin && target.pathname !== '/login' && target.pathname !== '/register';
  } catch {
    return false;
  }
}

export function getAuthRedirectTarget(): string {
  const value = new URLSearchParams(window.location.search).get('redirect');
  return value && isAllowedRedirect(value) ? value : DEFAULT_REDIRECT_PATH;
}

export function buildRootAuthUrl(path: '/login' | '/register'): string {
  const url = new URL(path, window.location.origin);
  const currentRedirect = getAuthRedirectTarget();
  if (currentRedirect !== DEFAULT_REDIRECT_PATH || window.location.pathname !== path) {
    url.searchParams.set('redirect', currentRedirect);
  }
  return url.toString();
}
