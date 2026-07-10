import axios from 'axios';
window.axios = axios;

const readCookie = (name) => {
	const value = `; ${document.cookie}`;
	const parts = value.split(`; ${name}=`);

	if (parts.length === 2) {
		return parts.pop()?.split(';').shift() ?? null;
	}

	return null;
};

const getCsrfToken = () => {
	const cookieToken = readCookie('XSRF-TOKEN');
	if (cookieToken) {
		return decodeURIComponent(cookieToken);
	}

	return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? null;
};

window.getCsrfToken = getCsrfToken;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
window.axios.defaults.withCredentials = true;

window.axios.interceptors.request.use((config) => {
	const token = getCsrfToken();

	if (token) {
		config.headers = config.headers ?? {};
		config.headers['X-CSRF-TOKEN'] = token;
		config.headers['X-XSRF-TOKEN'] = token;
	}

	return config;
});
