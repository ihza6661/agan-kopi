/**
 * Get the current CSRF token from the meta tag.
 * If the token is empty, it may indicate a stale session.
 */
export function getCsrfToken(): string {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    return meta?.content || '';
}

/**
 * Create headers object for fetch requests with CSRF token.
 */
export function createFetchHeaders(additionalHeaders: Record<string, string> = {}): Record<string, string> {
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        ...additionalHeaders,
    };
}

/**
 * Wrapper for fetch that automatically includes CSRF token and handles common errors.
 */
export async function fetchWithCsrf(
    url: string,
    options: RequestInit = {}
): Promise<Response> {
    const headers = createFetchHeaders(
        options.headers as Record<string, string> || {}
    );

    const response = await fetch(url, {
        ...options,
        headers,
        credentials: 'same-origin', // Ensure cookies are sent
    });

    // If we get a 419 (CSRF mismatch), reload the page to get fresh token
    if (response.status === 419) {
        const confirmed = window.confirm(
            'Sesi Anda telah kedaluwarsa. Halaman akan dimuat ulang untuk menyegarkan sesi.'
        );
        if (confirmed) {
            window.location.reload();
        }
        throw new Error('CSRF token mismatch - session expired');
    }

    return response;
}
