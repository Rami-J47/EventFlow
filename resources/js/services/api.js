async function request(path, options = {}) {
    const response = await fetch(`/api${path}`, {headers: {Accept: 'application/json', 'Content-Type': 'application/json', ...options.headers}, ...options});
    const body = await response.json().catch(() => ({}));
    if (!response.ok) { const error = new Error(body.message || 'Something went wrong.'); error.errors = body.errors || {}; throw error; }
    return body.data ?? body;
}
export const api = { listEvents: () => request('/events'), getEvent: (id) => request(`/events/${id}`), register: (id, data) => request(`/events/${id}/registrations`, {method: 'POST', body: JSON.stringify(data)}), getRegistration: (ref) => request(`/registrations/${ref}`), confirmDemoTicket: (ref) => request(`/registrations/${ref}/demo-confirmation`, {method: 'POST'}) };
