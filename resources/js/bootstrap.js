import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// API Base URL Configuration
// This will automatically use the current domain
window.API_BASE_URL = window.location.origin + '/api';
